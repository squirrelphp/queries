<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Queries\DBPassToLowerLayerTrait;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * Make sure all function calls are passed to the lower layer with the trait, by default
 */
class DBPassToLowerLayerTest extends \PHPUnit\Framework\TestCase
{
    private DBRawInterface&MockInterface $dbRawObject;
    private DBRawInterface $dbLowerLayerObject;
    private DBSelectQueryInterface $dbSelectQuery;

    protected function setUp(): void
    {
        $this->dbRawObject = \Mockery::mock(DBRawInterface::class);

        $this->dbLowerLayerObject = new class implements DBRawInterface {
            use DBPassToLowerLayerTrait;
        };
        $this->dbLowerLayerObject->setLowerLayer($this->dbRawObject);

        $this->dbSelectQuery = new class implements DBSelectQueryInterface {
        };
    }

    public function testTransaction(): void
    {
        // Function to execute within a transaction, to test the execution and return value
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };
        $a = 3;
        $b = 10;
        $c = 107;

        $this->dbRawObject
            ->shouldReceive('transaction')
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(120);

        $return = $this->dbLowerLayerObject->transaction($func, $a, $b, $c);

        $this->assertSame(120, $return);
    }

    public function testInTransaction(): void
    {
        $this->dbRawObject
            ->shouldReceive('inTransaction')
            ->andReturn(true);

        $return = $this->dbLowerLayerObject->inTransaction();

        $this->assertSame(true, $return);
    }

    public function testSelect(): void
    {
        $query = 'SELECT';
        $vars = [0, 3, 9];

        $this->dbRawObject
            ->shouldReceive('select')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($this->dbSelectQuery);

        $return = $this->dbLowerLayerObject->select($query, $vars);

        $this->assertSame($this->dbSelectQuery, $return);
    }

    public function testFetch(): void
    {
        $expected = ['dada' => 5];

        $this->dbRawObject
            ->shouldReceive('fetch')
            ->with(IsEqual::equalTo($this->dbSelectQuery))
            ->andReturn($expected);

        $return = $this->dbLowerLayerObject->fetch($this->dbSelectQuery);

        $this->assertSame($expected, $return);
    }

    public function testClear(): void
    {
        $this->dbRawObject
            ->shouldReceive('clear')
            ->with(IsEqual::equalTo($this->dbSelectQuery));

        $this->dbLowerLayerObject->clear($this->dbSelectQuery);

        $this->assertTrue(true);
    }

    public function testFetchOne(): void
    {
        $query = 'SELECT';
        $vars = [0, 3, 9];

        $expected = ['dada' => 5];

        $this->dbRawObject
            ->shouldReceive('fetchOne')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        $return = $this->dbLowerLayerObject->fetchOne($query, $vars);

        $this->assertSame($expected, $return);
    }

    public function testFetchAll(): void
    {
        $query = 'SELECT';
        $vars = [0, 3, 9];

        $expected = [['dada' => 5]];

        $this->dbRawObject
            ->shouldReceive('fetchAll')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        $return = $this->dbLowerLayerObject->fetchAll($query, $vars);

        $this->assertSame($expected, $return);
    }

    public function testFetchAllAndFlatten(): void
    {
        $query = 'SELECT';
        $vars = [0, 3, 9];

        $expected = [['dada' => 5]];

        $this->dbRawObject
            ->shouldReceive('fetchAllAndFlatten')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        $return = $this->dbLowerLayerObject->fetchAllAndFlatten($query, $vars);

        $this->assertSame($expected, $return);
    }

    public function testInsert(): void
    {
        $tableName = 'users';
        $row = [
            'userId' => 1,
            'userName' => 'Liam',
            'createDate' => 1048309248,
        ];

        $this->dbRawObject
            ->shouldReceive('insert')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($row), '');

        $this->dbLowerLayerObject->insert($tableName, $row);

        $this->assertTrue(true);
    }

    public function testUpsert(): void
    {
        $tableName = 'users';
        $row = [
            'userId' => 1,
            'userName' => 'Liam',
            'lastUpdate' => 1048309248,
        ];
        $indexColumns = [
            'userId',
        ];
        $rowUpdates = [
            'lastUpdate' => 1048309248,
        ];

        $this->dbRawObject
            ->shouldReceive('insertOrUpdate')
            ->with(
                IsEqual::equalTo($tableName),
                IsEqual::equalTo($row),
                IsEqual::equalTo($indexColumns),
                IsEqual::equalTo($rowUpdates),
            );

        $this->dbLowerLayerObject->insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);

        $this->assertTrue(true);
    }

    public function testUpdate(): void
    {
        $this->dbRawObject
            ->shouldReceive('update')
            ->with('dada', ['dada' => 5], ['mumu' => 7])
            ->andReturn(7);

        $return = $this->dbLowerLayerObject->update('dada', ['dada' => 5], ['mumu' => 7]);

        $this->assertSame(7, $return);
    }

    public function testDelete(): void
    {
        $tableName = 'dadaism';
        $query = [
            'table' => 'dada',
        ];

        $this->dbRawObject
            ->shouldReceive('delete')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($query))
            ->andReturn(7);

        $return = $this->dbLowerLayerObject->delete($tableName, $query);

        $this->assertSame(7, $return);
    }

    public function testLastInsertId(): void
    {
        $tableName = 'users';
        $row = [
            'userId' => 1,
            'userName' => 'Liam',
            'createDate' => 1048309248,
        ];

        $this->dbRawObject
            ->shouldReceive('insert')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($row), 'userId')
            ->andReturn(7);

        $return = $this->dbLowerLayerObject->insert($tableName, $row, 'userId');

        $this->assertSame('7', $return);
    }

    public function testChange(): void
    {
        $query = 'SELECT';
        $vars = [0, 3, 9];

        $this->dbRawObject
            ->shouldReceive('change')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn(7);

        $return = $this->dbLowerLayerObject->change($query, $vars);

        $this->assertSame(7, $return);
    }

    public function testQuoteIdentifier(): void
    {
        $this->dbRawObject
            ->shouldReceive('quoteIdentifier')
            ->with(IsEqual::equalTo('dada'))
            ->andReturn('"dada"');

        $return = $this->dbLowerLayerObject->quoteIdentifier('dada');

        $this->assertSame('"dada"', $return);
    }

    public function testQuoteExpression(): void
    {
        $this->dbRawObject
            ->shouldReceive('quoteExpression')
            ->with(IsEqual::equalTo('WHERE :dada:'))
            ->andReturn('WHERE "dada"');

        $return = $this->dbLowerLayerObject->quoteExpression('WHERE :dada:');

        $this->assertSame('WHERE "dada"', $return);
    }

    public function testChangeTransaction(): void
    {
        $this->dbRawObject
            ->shouldReceive('setTransaction')
            ->with(IsEqual::equalTo(true));

        $this->dbLowerLayerObject->setTransaction(true);

        $this->assertTrue(true);
    }

    public function testGetConnection(): void
    {
        $connection = \Mockery::mock(ConnectionInterface::class);

        $this->dbRawObject
            ->shouldReceive('getConnection')
            ->andReturn($connection);

        $return = $this->dbLowerLayerObject->getConnection();

        $this->assertSame($connection, $return);
    }
}
