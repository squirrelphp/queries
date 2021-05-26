<?php

namespace Squirrel\Queries\Doctrine;

use Squirrel\Debug\Debug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * DB SQLite implementation using Doctrine DBAL with custom upsert functionality
 *
 * This is equal to the Postgres version, as SQLite uses the same syntax
 */
class DBSQLiteImplementation extends DBPostgreSQLImplementation
{
    private ?float $sqliteVersion = null;

    /**
     * @codeCoverageIgnore
     */
    public function insertOrUpdate(
        string $table,
        array $row = [],
        array $index = [],
        ?array $update = null,
    ): void {
        if ($this->sqliteVersion === null) {
            $connection = $this->getConnection();

            $statement = $connection->prepare('select sqlite_version() AS "v"');
            $statementResult = $statement->executeQuery();
            $result = $statementResult->fetchAssociative();
            $statementResult->free();

            if (!isset($result['v'])) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    DBInterface::class,
                    'SQLite version could not be retrieved',
                );
            }

            $this->sqliteVersion = \floatval($result['v']);
        }

        // SQLite below version 3.24 does not offer native upsert, so emulate it
        if ($this->sqliteVersion < 3.24) {
            $this->insertOrUpdateEmulation($table, $row, $index, $update);
        } else {
            parent::insertOrUpdate($table, $row, $index, $update);
        }
    }

    protected function convertStructuredSelectToQuery(array $select): array
    {
        [$sql, $queryValues] = parent::convertStructuredSelectToQuery($select);

        // SQLite does not support ... FOR UPDATE and it is not needed, so we remove it
        if (($select['lock'] ?? false) === true) {
            $sql = \substr($sql, 0, -11);
        }

        return [$sql, $queryValues];
    }
}
