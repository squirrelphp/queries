<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\Exception\ConnectionException;
use Squirrel\Connection\Exception\DeadlockException;
use Squirrel\Connection\Exception\DriverException;
use Squirrel\Queries\DB\ErrorHandler;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Exception\DBConnectionException;
use Squirrel\Queries\Exception\DBDriverException;
use Squirrel\Queries\Exception\DBLockException;

/**
 * Test our error handler based on squirrelphp/connection
 */
class ConnectionErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that a new transaction is correctly forwarded to the lower layer
     */
    public function testNewTransaction(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->andReturn(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    /**
     * Test that the transaction is not forwarded to lower layer if a transaction is already active
     */
    public function testTransactionWhenActiveTransactionExists(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // Transaction is reported as active, so we call the function directly, no lower layer
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testSelectPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn($selectQueryResult);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->select('SELECT * FROM table');

        $this->assertSame($selectQueryResult, $result);
    }

    public function testFetchPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQueryResult)
            ->andReturn(['dada' => '55']);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetch($selectQueryResult);

        $this->assertSame(['dada' => '55'], $result);
    }

    public function testClearPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('clear')
            ->once()
            ->with($selectQueryResult);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $errorHandler->clear($selectQueryResult);

        $this->assertTrue(true);
    }

    public function testFetchOnePassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('fetchOne')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn(['dada' => '55']);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchOne('SELECT * FROM table');

        $this->assertSame(['dada' => '55'], $result);
    }

    public function testFetchAllPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('fetchAll')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn([['dada' => '55'], ['dada' => 33]]);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchAll('SELECT * FROM table');

        $this->assertSame([['dada' => '55'], ['dada' => 33]], $result);
    }

    public function testFetchAllAndFlattenPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $expected = [['dada' => '55'], ['dada' => 33]];

        $lowerLayer
            ->shouldReceive('fetchAllAndFlatten')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn($expected);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchAllAndFlatten('SELECT * FROM table');

        $this->assertSame($expected, $result);
    }

    public function testInsertPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('insert')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ])
            ->andReturn(33);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->insert('tableName', [
            'dada' => 33,
            'fufu' => true,
        ]);

        $this->assertSame('33', $result);
    }

    public function testUpsertPassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ], ['dada']);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $errorHandler->insertOrUpdate('tableName', [
            'dada' => 33,
            'fufu' => true,
        ], ['dada']);

        $this->assertTrue(true);
    }

    public function testUpdatePassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('update')
            ->once()
            ->with('blobs.aa_sexy', [
                'anyfieldname' => 'nicevalue',
            ], [
                'blabla' => 5,
            ])
            ->andReturn(7);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->update('blobs.aa_sexy', [
            'anyfieldname' => 'nicevalue',
        ], [
            'blabla' => 5,
        ]);

        $this->assertSame(7, $result);
    }

    public function testDeletePassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('delete')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ])
            ->andReturn(6);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->delete('tableName', [
            'dada' => 33,
            'fufu' => true,
        ]);

        $this->assertSame(6, $result);
    }

    public function testChangePassToLowerLayer(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('change')
            ->once()
            ->with(
                'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=? WHERE "blabla"=?',
                ['nicevalue', null, 5],
            )
            ->andReturn(9);

        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->change(
            'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=? WHERE "blabla"=?',
            ['nicevalue', null, 5],
        );

        $this->assertSame(9, $result);
    }

    public function testRedoTransactionAfterDeadlock(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new DeadlockException(
                    new \PDOException('pdo deadlock exception'),
                    'pdo deadlock exception',
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs()
            ->andThrow(new \Exception('some random rollback exception'));

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testRedoTransactionAfterConnectionProblem(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs()
            ->andThrow(new \Exception('some random rollback exception'));

        $connection
            ->shouldReceive('reconnect')
            ->once()
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testRedoTransactionAfterConnectionProblemMultipleAttempts(): void
    {
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs()
            ->andThrow(new \Exception('some random rollback exception'));

        $connection
            ->shouldReceive('reconnect')
            ->times(2)
            ->withNoArgs()
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $connection
            ->shouldReceive('reconnect')
            ->once()
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testExceptionNoRetriesTransactionAfterDeadlock(): void
    {
        $this->expectException(DBLockException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new DeadlockException(
                    new \PDOException('pdo deadlock exception'),
                    'pdo deadlock exception',
                ),
            );

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([]);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionNoRetriesTransactionAfterConnectionProblem(): void
    {
        $this->expectException(DBConnectionException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([]);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionFromDriverLikeBadSQL(): void
    {
        $this->expectException(DBDriverException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new DriverException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs()
            ->andThrow(new \Exception('some rollback exception'));

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testUnhandledException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // Example function to pass along
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        // Example variables to pass along
        $a = 5;
        $b = 13;
        $c = 155;

        // We only get the forwarding to lower layer if there is no transaction active
        $lowerLayer
            ->shouldReceive('inTransaction')
            ->withNoArgs()
            ->andReturn(false);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andThrow(
                new \InvalidArgumentException('some weird exception'),
            );

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('rollBack')
            ->once()
            ->withNoArgs()
            ->andThrow(new \Exception('some rollback exception'));

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionSelectWithinTransactionDeadlock(): void
    {
        $this->expectException(DeadlockException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DeadlockException(
                    new \PDOException('pdo deadlock exception'),
                    'pdo deadlock exception',
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionNoRetriesSelectAfterDeadlock(): void
    {
        $this->expectException(DBLockException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->twice()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DeadlockException(
                    new \PDOException('pdo deadlock exception'),
                    'pdo deadlock exception',
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionSelectWithinTransactionConnectionProblem(): void
    {
        $this->expectException(ConnectionException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionNoRetriesSelectAfterConnectionProblem(): void
    {
        $this->expectException(DBConnectionException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->twice()
            ->with('SELECT * FROM table')
            ->andThrow(
                new ConnectionException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $connection = \Mockery::mock(ConnectionInterface::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('reconnect')
            ->once()
            ->withNoArgs();

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionSelectFromDriver(): void
    {
        $this->expectException(DBDriverException::class);

        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DriverException(
                    new \PDOException('MySQL server has gone away'),
                    'MySQL server has gone away',
                ),
            );

        // Error handler instantiation
        $errorHandler = new ErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }
}
