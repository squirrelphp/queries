<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Hamcrest\Core\IsEqual;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Doctrine\DBErrorHandler;
use Squirrel\Queries\Exception\DBConnectionException;
use Squirrel\Queries\Exception\DBDriverException;
use Squirrel\Queries\Exception\DBLockException;

/**
 * Test our error handler based on the Doctrine library
 */
class DoctrineErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that a new transaction is correctly forwarded to the lower layer
     */
    public function testNewTransaction(): void
    {
        // Lower layer mock
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
        $errorHandler = new DBErrorHandler();
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
        // Lower layer mock
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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testSelectPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn($selectQueryResult);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->select('SELECT * FROM table');

        $this->assertSame($selectQueryResult, $result);
    }

    public function testFetchPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQueryResult)
            ->andReturn(['dada' => '55']);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetch($selectQueryResult);

        $this->assertSame(['dada' => '55'], $result);
    }

    public function testClearPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $selectQueryResult = new class implements DBSelectQueryInterface {
        };

        $lowerLayer
            ->shouldReceive('clear')
            ->once()
            ->with($selectQueryResult);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $errorHandler->clear($selectQueryResult);

        $this->assertTrue(true);
    }

    public function testFetchOnePassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('fetchOne')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn(['dada' => '55']);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchOne('SELECT * FROM table');

        $this->assertSame(['dada' => '55'], $result);
    }

    public function testFetchAllPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('fetchAll')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn([['dada' => '55'], ['dada' => 33]]);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchAll('SELECT * FROM table');

        $this->assertSame([['dada' => '55'], ['dada' => 33]], $result);
    }

    public function testFetchAllAndFlattenPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $expected = [['dada' => '55'], ['dada' => 33]];

        $lowerLayer
            ->shouldReceive('fetchAllAndFlatten')
            ->once()
            ->with('SELECT * FROM table')
            ->andReturn($expected);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->fetchAllAndFlatten('SELECT * FROM table');

        $this->assertSame($expected, $result);
    }

    public function testInsertPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('insert')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ])
            ->andReturn(33);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->insert('tableName', [
            'dada' => 33,
            'fufu' => true,
        ]);

        $this->assertSame('33', $result);
    }

    public function testUpsertPassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ], ['dada']);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $errorHandler->insertOrUpdate('tableName', [
            'dada' => 33,
            'fufu' => true,
        ], ['dada']);

        $this->assertTrue(true);
    }

    public function testUpdatePassToLowerLayer(): void
    {
        // Lower layer mock
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

        $errorHandler = new DBErrorHandler();
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
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('delete')
            ->once()
            ->with('tableName', [
                'dada' => 33,
                'fufu' => true,
            ])
            ->andReturn(6);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->delete('tableName', [
            'dada' => 33,
            'fufu' => true,
        ]);

        $this->assertSame(6, $result);
    }

    public function testChangePassToLowerLayer(): void
    {
        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        $lowerLayer
            ->shouldReceive('change')
            ->once()
            ->with(
                'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=? WHERE "blabla"=?',
                ['nicevalue', null, 5],
            )
            ->andReturn(9);

        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        $result = $errorHandler->change(
            'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=? WHERE "blabla"=?',
            ['nicevalue', null, 5],
        );

        $this->assertSame(9, $result);
    }

    public function testRedoTransactionAfterDeadlock(): void
    {
        // Lower layer mock
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
                    PDOException::new(new \PDOException('pdo deadlock exception')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(Connection::class);

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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testRedoTransactionAfterConnectionProblem(): void
    {
        // Lower layer mock
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
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(Connection::class);

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
            ->shouldReceive('close')
            ->once()
            ->withNoArgs();

        $connection
            ->shouldReceive('connect')
            ->once()
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testRedoTransactionAfterConnectionProblemMultipleAttempts(): void
    {
        // Lower layer mock
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
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(Connection::class);

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
            ->shouldReceive('close')
            ->times(3)
            ->withNoArgs();

        $connection
            ->shouldReceive('connect')
            ->times(2)
            ->withNoArgs()
            ->andThrow(
                new ConnectionException(
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $connection
            ->shouldReceive('connect')
            ->times(1)
            ->withNoArgs();

        $lowerLayer
            ->shouldReceive('setTransaction')
            ->once()
            ->with(false);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $result = $errorHandler->transaction($func, $a, $b, $c);

        // Check that we got back the transaction result
        $this->assertEquals(173, $result);
    }

    public function testExceptionNoRetriesTransactionAfterDeadlock(): void
    {
        $this->expectException(DBLockException::class);

        // Lower layer mock
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
                    PDOException::new(new \PDOException('pdo deadlock exception')),
                    null,
                ),
            );

        $connection = \Mockery::mock(Connection::class);

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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([]);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionNoRetriesTransactionAfterConnectionProblem(): void
    {
        $this->expectException(DBConnectionException::class);

        // Lower layer mock
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
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(173);

        $connection = \Mockery::mock(Connection::class);

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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([]);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionFromDriverLikeBadSQL(): void
    {
        $this->expectException(DBDriverException::class);

        // Lower layer mock
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
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $connection = \Mockery::mock(Connection::class);

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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testUnhandledException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Lower layer mock
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

        $connection = \Mockery::mock(Connection::class);

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
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->transaction($func, $a, $b, $c);
    }

    public function testExceptionSelectWithinTransactionDeadlock(): void
    {
        $this->expectException(DeadlockException::class);

        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DeadlockException(
                    PDOException::new(new \PDOException('pdo deadlock exception')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionNoRetriesSelectAfterDeadlock(): void
    {
        $this->expectException(DBLockException::class);

        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->twice()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DeadlockException(
                    PDOException::new(new \PDOException('pdo deadlock exception')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setLockRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionSelectWithinTransactionConnectionProblem(): void
    {
        $this->expectException(ConnectionException::class);

        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new ConnectionException(
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionNoRetriesSelectAfterConnectionProblem(): void
    {
        $this->expectException(DBConnectionException::class);

        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->twice()
            ->with('SELECT * FROM table')
            ->andThrow(
                new ConnectionException(
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        $lowerLayer
            ->shouldReceive('inTransaction')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $connection = \Mockery::mock(Connection::class);

        $lowerLayer
            ->shouldReceive('getConnection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $connection
            ->shouldReceive('close')
            ->once()
            ->withNoArgs();

        $connection
            ->shouldReceive('connect')
            ->once()
            ->withNoArgs();

        $connection
            ->shouldReceive('ping')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);
        $errorHandler->setConnectionRetries([1]);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }

    public function testExceptionSelectFromDriver(): void
    {
        $this->expectException(DBDriverException::class);

        // Lower layer mock
        $lowerLayer = \Mockery::mock(DBRawInterface::class);

        // The call we are expecting to the lower layer
        $lowerLayer
            ->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM table')
            ->andThrow(
                new DriverException(
                    PDOException::new(new \PDOException('MySQL server has gone away')),
                    null,
                ),
            );

        // Error handler instantiation
        $errorHandler = new DBErrorHandler();
        $errorHandler->setLowerLayer($lowerLayer);

        // Do the transaction function
        $errorHandler->select('SELECT * FROM table');
    }
}
