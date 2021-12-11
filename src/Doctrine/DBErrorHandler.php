<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Squirrel\Debug\Debug;
use Squirrel\Queries\DBException;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBPassToLowerLayerTrait;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Exception\DBConnectionException;
use Squirrel\Queries\Exception\DBDriverException;
use Squirrel\Queries\Exception\DBLockException;

/**
 * Layer to handle query failures and attempt to repeat them if there was a:
 *
 * - deadlock or lock timeout
 * - connection problem to the database server
 *
 * For other specific DB errors we generate a richer exception so outer layers
 * know better what went wrong with our query
 */
class DBErrorHandler implements DBRawInterface
{
    // Default implementation of all DBRawInterface functions - pass to lower layer
    use DBPassToLowerLayerTrait;

    /**
     * How much time in microseconds we should wait before retrying
     *
     * Each value in this array adds one retry, but before retrying
     * it waits the defined number of microseconds. It makes sense
     * to increase the wait time after every attempt, to give the
     * database some breathing room to recover and makes it more likely
     * for the process to still finish successfully
     *
     * With the default configuration, we wait almost 30 seconds in total
     * - waiting longer does not have much advantages, as it will likely
     * trigger other timeouts from gateways, CDNs etc., so better to
     * fail on our end at that time
     *
     * @var int[]
     */
    private array $connectionRetries = [
        1000,     // 1ms
        10000,    // 10ms
        100000,   // 100ms
        1000000,  // 1s
        2000000,  // 2s
        3000000,  // 3s
        4000000,  // 4s
        5000000,  // 5s
        6000000,  // 6s
        7000000,  // 7s
    ]; // total: 28s 111ms - 10 attempts

    /**
     * How much time in microseconds we should wait before retrying
     *
     * Each value in this array adds one retry, but before retrying
     * it waits the defined number of microseconds. It makes sense
     * to increase the wait time after every attempt, to give the
     * database some breathing room to recover and makes it more likely
     * for the process to still finish successfully
     *
     * With the default configuration, we wait almost 30 seconds in total
     * - waiting longer does not have much advantages, as it will likely
     * trigger other timeouts from gateways, CDNs etc., so better to
     * fail on our end at that time
     *
     * @var int[]
     */
    private array $lockRetries = [
        1000,     // 1ms
        10000,    // 10ms
        100000,   // 100ms
        1000000,  // 1s
        2000000,  // 2s
        3000000,  // 3s
        4000000,  // 4s
        5000000,  // 5s
        6000000,  // 6s
        7000000,  // 7s
    ]; // total: 28s 111ms - 10 attempts

    /**
     * Change connection retries configuration
     */
    public function setConnectionRetries(array $connectionRetries): void
    {
        $this->connectionRetries = \array_map('intval', $connectionRetries);
    }

    /**
     * Change deadlock retries configuration
     */
    public function setLockRetries(array $lockRetries): void
    {
        $this->lockRetries = \array_map('intval', $lockRetries);
    }

    public function transaction(callable $func, mixed ...$arguments): mixed
    {
        // If we are already in a transaction we just run the function
        if ($this->lowerLayer->inTransaction() === true) {
            return $func(...$arguments);
        }

        // Do a full transaction and try to repeat it if necessary
        return $this->transactionExecute(
            $func,
            $arguments,
            $this->connectionRetries,
            $this->lockRetries,
        );
    }

    /**
     * Execute transaction - attempts to do it and repeats it if there was a problem
     *
     * @throws DBException
     */
    protected function transactionExecute(
        callable $func,
        array $arguments,
        array $connectionRetries,
        array $lockRetries,
    ): mixed {
        try {
            return $this->lowerLayer->transaction($func, ...$arguments);
        } catch (DeadlockException | LockWaitTimeoutException $e) { // Deadlock or lock timeout occured
            // Attempt to roll back
            try {
                /**
                 * @var Connection $connection
                 */
                $connection = $this->lowerLayer->getConnection();
                $connection->rollBack();
            } catch (\Exception $eNotUsed) {
            }

            // Set flag for "not in a transaction"
            $this->setTransaction(false);

            // We have exhaused all deadlock retries and it is time to give up
            if (count($lockRetries) === 0) {
                throw Debug::createException(
                    DBLockException::class, // exception class to create
                    $e->getMessage(),
                    ignoreClasses: DBInterface::class,
                    previousException: $e,
                );
            }

            // Wait for a certain amount of microseconds
            \usleep(\array_shift($lockRetries));

            // Repeat transaction
            return $this->transactionExecute($func, $arguments, $connectionRetries, $lockRetries);
        } catch (ConnectionException $e) { // Connection error occured
            // Attempt to roll back, suppress any possible exceptions
            try {
                /**
                 * @var Connection $connection
                 */
                $connection = $this->lowerLayer->getConnection();
                $connection->rollBack();
            } catch (\Exception $eNotUsed) {
            }

            // Set flag for "not in a transaction"
            $this->setTransaction(false);

            // Attempt to reconnect according to $connectionRetries
            $connectionRetries = $this->attemptReconnect($connectionRetries);

            // Reconnecting was unsuccessful
            if ($connectionRetries === null) {
                throw Debug::createException(
                    DBConnectionException::class, // exception class to create
                    $e->getMessage(),
                    ignoreClasses: DBInterface::class,
                    previousException: $e,
                );
            }

            // Repeat transaction
            return $this->transactionExecute($func, $arguments, $connectionRetries, $lockRetries);
        } catch (DriverException $e) { // Some other SQL related exception
            // Attempt to roll back, suppress any possible exceptions
            try {
                /**
                 * @var Connection $connection
                 */
                $connection = $this->lowerLayer->getConnection();
                $connection->rollBack();
            } catch (\Exception $eNotUsed) {
            }

            // Set flag for "not in a transaction"
            $this->setTransaction(false);

            // Throw DB exception for higher-up context to catch
            throw Debug::createException(
                DBDriverException::class, // exception class to create
                $e->getMessage(),
                ignoreClasses: DBInterface::class,
                previousException: $e,
            );
        } catch (\Exception | \Throwable $e) { // Other exception, throw it as is, we do not know how to deal with it
            // Attempt to roll back, suppress any possible exceptions
            try {
                /**
                 * @var Connection $connection
                 */
                $connection = $this->lowerLayer->getConnection();
                $connection->rollBack();
            } catch (\Exception $eNotUsed) {
            }

            // Set flag for "not in a transaction"
            $this->setTransaction(false);

            // Throw exception again for higher-up context to catch
            throw $e;
        }
    }

    public function select($query, array $vars = []): DBSelectQueryInterface
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function clear(DBSelectQueryInterface $selectQuery): void
    {
        $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function fetchOne($query, array $vars = []): ?array
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function fetchAll($query, array $vars = []): array
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function fetchAllAndFlatten($query, array $vars = []): array
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function insert(string $table, array $row = [], string $autoIncrement = ''): ?string
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function insertOrUpdate(string $table, array $row = [], array $index = [], ?array $update = null): void
    {
        $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function update(string $table, array $changes, array $where = []): int
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function delete(string $table, array $where = []): int
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    public function change(string $query, array $vars = []): int
    {
        return $this->internalCall(__FUNCTION__, \func_get_args(), $this->connectionRetries, $this->lockRetries);
    }

    /**
     * Pass through all calls to lower layer, and just add try-catch blocks so we can
     * catch and process connection and (dead)lock exceptions / repeat queries
     *
     * @throws DBException
     */
    protected function internalCall(
        string $name,
        array $arguments,
        array $connectionRetries,
        array $lockRetries,
    ): mixed {
        // Attempt to call the dbal function
        try {
            return $this->lowerLayer->$name(...$arguments);
        } catch (ConnectionException $e) {
            // If we are in a transaction we escalate it to transaction context
            if ($this->lowerLayer->inTransaction()) {
                throw $e;
            }

            // Attempt to reconnect according to $connectionRetries
            $connectionRetries = $this->attemptReconnect($connectionRetries);

            // Reconnecting was unsuccessful
            if ($connectionRetries === null) {
                throw Debug::createException(
                    DBConnectionException::class,
                    $e->getMessage(),
                    ignoreClasses: DBInterface::class,
                    previousException: $e,
                );
            }

            // Repeat our function
            return $this->internalCall($name, $arguments, $connectionRetries, $lockRetries);
        } catch (DeadlockException | LockWaitTimeoutException $e) {
            // If we are in a transaction we escalate it to transaction context
            if ($this->lowerLayer->inTransaction()) {
                throw $e;
            }

            // We have exhaused all deadlock retries and it is time to give up
            if (\count($lockRetries) === 0) {
                throw Debug::createException(
                    DBLockException::class,
                    $e->getMessage(),
                    ignoreClasses: DBInterface::class,
                    previousException: $e,
                );
            }

            // Wait for a certain amount of microseconds
            \usleep(\array_shift($lockRetries));

            // Repeat our function
            return $this->internalCall($name, $arguments, $connectionRetries, $lockRetries);
        } catch (DriverException $e) { // Some other SQL related exception
            throw Debug::createException(
                DBDriverException::class,
                $e->getMessage(),
                ignoreClasses: DBInterface::class,
                previousException: $e,
            );
        }
    }

    /**
     * Attempt to reconnect to DB server
     */
    protected function attemptReconnect(array $connectionRetries): ?array
    {
        // No more attempts left - return false to report back
        if (\count($connectionRetries) === 0) {
            return null;
        }

        // Wait for a certain amount of microseconds
        \usleep(\array_shift($connectionRetries));

        try {
            /**
             * @var Connection $connection
             */
            $connection = $this->lowerLayer->getConnection();
            // Close connection and establish a new connection
            $connection->close();
            $connection->connect();
        } catch (ConnectionException $e) { // Connection could not be established - try again
            return $this->attemptReconnect($connectionRetries);
        }

        // Go back to the previous context with our new connection
        return $connectionRetries;
    }
}
