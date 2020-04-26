<?php

namespace Squirrel\Queries\Tests;

use Squirrel\Queries\DBPassToLowerLayerTrait;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\TestHelpers\DBSelectQueryForTests;

/**
 * Make sure all function calls are passed to the lower layer with the trait, by default
 */
class DBPassToLowerLayerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBRawInterface
     */
    private $dbRawObject;

    /**
     * @var DBPassToLowerLayerTrait
     */
    private $dbLowerLayerObject;

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
    }

    public function testTransaction()
    {
        // Variables to pass to the function
        $func = function ($a, $b, $c) {
            return $a + $b + $c;
        };
        $a = 3;
        $b = 10;
        $c = 107;

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('transaction')
            ->with(\Mockery::mustBe($func), \Mockery::mustBe($a), \Mockery::mustBe($b), \Mockery::mustBe($c))
            ->andReturn(120);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->transaction($func, $a, $b, $c);

        // Check the result
        $this->assertSame(120, $return);
    }

    public function testInTransaction()
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

    public function testSelect()
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Select query result
        $select = new DBSelectQueryForTests();

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('select')
            ->with(\Mockery::mustBe($query), \Mockery::mustBe($vars))
            ->andReturn($select);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->select($query, $vars);

        // Check the result
        $this->assertSame($select, $return);
    }

    public function testFetch()
    {
        // Select query result
        $select = new DBSelectQueryForTests();

        // Expected return value
        $expected = ['dada' => 5];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetch')
            ->with(\Mockery::mustBe($select))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetch($select);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testClear()
    {
        // Select query result
        $select = new DBSelectQueryForTests();

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('clear')
            ->with(\Mockery::mustBe($select));

        // Make the trait function call
        $this->dbLowerLayerObject->clear($select);

        // Check the result
        $this->assertTrue(true);
    }

    public function testFetchOne()
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = ['dada' => 5];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchOne')
            ->with(\Mockery::mustBe($query), \Mockery::mustBe($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchOne($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testFetchAll()
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = [['dada' => 5]];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchAll')
            ->with(\Mockery::mustBe($query), \Mockery::mustBe($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchAll($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testFetchAllAndFlatten()
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // Expected return value
        $expected = [['dada' => 5]];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('fetchAllAndFlatten')
            ->with(\Mockery::mustBe($query), \Mockery::mustBe($vars))
            ->andReturn($expected);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->fetchAllAndFlatten($query, $vars);

        // Check the result
        $this->assertSame($expected, $return);
    }

    public function testInsert()
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
            ->with(\Mockery::mustBe($tableName), \Mockery::mustBe($row), '');

        // Make the trait function call
        $this->dbLowerLayerObject->insert($tableName, $row);

        $this->assertTrue(true);
    }

    public function testUpsert()
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
                \Mockery::mustBe($tableName),
                \Mockery::mustBe($row),
                \Mockery::mustBe($indexColumns),
                \Mockery::mustBe($rowUpdates)
            );

        // Make the trait function call
        $this->dbLowerLayerObject->insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);

        $this->assertTrue(true);
    }

    public function testUpdate()
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

    public function testDelete()
    {
        // Variables to pass to the function
        $tableName = 'dadaism';
        $query = [
            'table' => 'dada',
        ];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('delete')
            ->with(\Mockery::mustBe($tableName), \Mockery::mustBe($query))
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->delete($tableName, $query);

        // Check the result
        $this->assertSame(7, $return);
    }

    public function testLastInsertId()
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
            ->with(\Mockery::mustBe($tableName), \Mockery::mustBe($row), 'userId')
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->insert($tableName, $row, 'userId');

        // Check the result
        $this->assertSame('7', $return);
    }

    public function testChange()
    {
        // Variables to pass to the function
        $query = 'SELECT';
        $vars = [0, 3, 9];

        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('change')
            ->with(\Mockery::mustBe($query), \Mockery::mustBe($vars))
            ->andReturn(7);

        // Make the trait function call
        $return = $this->dbLowerLayerObject->change($query, $vars);

        // Check the result
        $this->assertSame(7, $return);
    }

    public function testQuoteIdentifier()
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('quoteIdentifier')
            ->with(\Mockery::mustBe('dada'))
            ->andReturn('"dada"');

        // Make the trait function call
        $return = $this->dbLowerLayerObject->quoteIdentifier('dada');

        // Check the result
        $this->assertSame('"dada"', $return);
    }

    public function testQuoteExpression()
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('quoteExpression')
            ->with(\Mockery::mustBe('WHERE :dada:'))
            ->andReturn('WHERE "dada"');

        // Make the trait function call
        $return = $this->dbLowerLayerObject->quoteExpression('WHERE :dada:');

        // Check the result
        $this->assertSame('WHERE "dada"', $return);
    }

    public function testChangeTransaction()
    {
        // What we expect to be called with the lower layer
        $this->dbRawObject
            ->shouldReceive('setTransaction')
            ->with(\Mockery::mustBe(true));

        // Make the trait function call
        $this->dbLowerLayerObject->setTransaction(true);

        // Check the result
        $this->assertTrue(true);
    }

    public function testGetConnection()
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
