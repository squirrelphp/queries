<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Queries\DBPassToLowerLayerTrait;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * Make sure all function calls are passed to the lower layer with the trait, by default
 */
class DBPassToLowerLayerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBRawInterface&MockInterface  */
    private DBRawInterface $dbRawObject;
    /** @var DBRawInterface&MockInterface */
    private $dbLowerLayerObject;
    private DBSelectQueryInterface $dbSelectQuery;

    /**
     * Initialize for every test in this class
     */
    protected function setUp(): void
    {
        // Raw DB object - where we expect the function calls
        $this->dbRawObject = \Mockery::mock(DBRawInterface::class)->makePartial();

        // Lower layer mock, where we check the function calls
        $this->dbLowerLayerObject = \Mockery::mock(DBPassToLowerLayerTrait::class)->makePartial();
        $this->dbLowerLayerObject->setLowerLayer($this->dbRawObject);

        $this->dbSelectQuery = new class implements DBSelectQueryInterface {
        };
    }

    public function testTransaction(): void
    {
        // Variables to pass to the function
        $func = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };
        $a = 3;
        $b = 10;
        $c = 107;

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('transaction')
            ->with(IsEqual::equalTo($func), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturn(120);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->transaction($func, $a, $b, $c);

        // Check the result
        $this->assertSame(120, $return);
    }

    public function testInTransaction(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('inTransaction')
            ->andReturn(true);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->inTransaction();

        // Check the result
        $this->assertSame(true, $return);
    }

    public function testSelect(): void
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('select')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($this->dbSelectQuery);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->select($query, $vars);

        // Check the result
        $this->assertSame($this->dbSelectQuery, $return);
    }

    public function testFetch(): void
    {
        // Expected return value
        $expected = ['dada' => 5];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetch')
            ->with(IsEqual::equalTo($this->dbSelectQuery))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetch($this->dbSelectQuery);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testClear(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('clear')
            ->with(IsEqual::equalTo($this->dbSelectQuery));

        // Make the trait function call
        $this->dbLowerLayerObject->clear($this->dbSelectQuery);

        // Check the result
        $this->assertTrue(true);
    }

    public function testFetchOne(): void
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = ['dada' => 5];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchOne')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchOne($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testFetchAll(): void
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = [['dada' => 5]];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchAll')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchAll($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testFetchAllAndFlatten(): void
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = [['dada' => 5]];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchAllAndFlatten')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchAllAndFlatten($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testInsert(): void
    {
        // Variables to pass to the function
        $tableName = 'users';
        $row = [
            'userId' => 1,
            'userName' => 'Liam',
            'createDate' => 1048309248,
        ];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('insert')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($row), '');

        // Make the trait function call
        $this->dbLowerLayerObject->insert($tableName, $row);

        $this->assertTrue(true);
    }

    public function testUpsert(): void
    {
        // Variables to pass to the function
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

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('insertOrUpdate')
            ->with(
                IsEqual::equalTo($tableName),
                IsEqual::equalTo($row),
                IsEqual::equalTo($indexColumns),
                IsEqual::equalTo($rowUpdates),
            );

        // Make the trait function call
        $this->dbLowerLayerObject->insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);

        $this->assertTrue(true);
    }

    public function testUpdate(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('update')
            ->with('dada', ['dada' => 5], ['mumu' => 7])
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->update('dada', ['dada' => 5], ['mumu' => 7]);

        // Check the result
        $this->assertSame(7, $return);
    }

    public function testDelete(): void
    {
        // Variables to pass to the function
        $tableName = 'dadaism';
        $query = [
            'table' => 'dada',
        ];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('delete')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($query))
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->delete($tableName, $query);

        // Check the result
        $this->assertSame(7, $return);
    }

    public function testLastInsertId(): void
    {
        // Variables to pass to the function
        $tableName = 'users';
        $row = [
            'userId' => 1,
            'userName' => 'Liam',
            'createDate' => 1048309248,
        ];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('insert')
            ->with(IsEqual::equalTo($tableName), IsEqual::equalTo($row), 'userId')
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->insert($tableName, $row, 'userId');

        // Check the result
        $this->assertSame('7', $return);
    }

    public function testChange(): void
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('change')
            ->with(IsEqual::equalTo($query), IsEqual::equalTo($vars))
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->change($query, $vars);

        // Check the result
        $this->assertSame(7, $return);
    }

    public function testQuoteIdentifier(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('quoteIdentifier')
            ->with(IsEqual::equalTo('dada'))
            ->andReturn('"dada"');

        // Make the trait function call
        $return = $this->dbLowerLayerObject->quoteIdentifier('dada');

        // Check the result
        $this->assertSame('"dada"', $return);
    }

    public function testQuoteExpression(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('quoteExpression')
            ->with(IsEqual::equalTo('WHERE :dada:'))
            ->andReturn('WHERE "dada"');

        // Make the trait function call
        $return = $this->dbLowerLayerObject->quoteExpression('WHERE :dada:');

        // Check the result
        $this->assertSame('WHERE "dada"', $return);
    }

    public function testChangeTransaction(): void
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('setTransaction')
            ->with(IsEqual::equalTo(true));

        // Make the trait function call
        $this->dbLowerLayerObject->setTransaction(true);

        // Check the result
        $this->assertTrue(true);
    }

    public function testGetConnection(): void
    {
        // Connection dummy class
        $connection = new \stdClass();

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('getConnection')
            ->andReturn($connection);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->getConnection();

        // Check the result
        $this->assertSame($connection, $return);
    }
}
