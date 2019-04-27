<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\SelectEntries;
use Squirrel\Queries\Builder\SelectIterator;
use Squirrel\Queries\DBInterface;

class SelectEntriesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBInterface
     */
    private $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataGetEntries()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [['test' => 'one'], ['something' => 5]];

        $this->db
            ->shouldReceive('fetchAll')
            ->once()
            ->with([
                'tables' => [],
                'where' => [],
                'group' => [],
                'order' => [],
                'fields' => [],
                'limit' => 0,
                'offset' => 0,
                'lock' => false,
            ])
            ->andReturn($expectedResult);

        $results = $selectBuilder->getAllEntries();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntries()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [['test' => 'one'], ['something' => 5]];

        $this->db
            ->shouldReceive('fetchAll')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                    'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'group' => [
                    'g.somefield',
                ],
                'order' => [
                    'e.id',
                ],
                'fields' => [
                    'field1',
                    'field6' => 'somefield',
                ],
                'limit' => 55,
                'offset' => 13,
                'lock' => true,
            ])
            ->andReturn($expectedResult);

        $results = $selectBuilder
            ->fields([
                'field1',
                'field6' => 'somefield',
            ])
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->where([
                'g.somefield' => 33,
            ])
            ->groupBy([
                'g.somefield',
            ])
            ->orderBy([
                'e.id',
            ])
            ->limitTo(55)
            ->startAt(13)
            ->blocking()
            ->getAllEntries();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntriesSingular()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [['test' => 'one'], ['something' => 5]];

        $this->db
            ->shouldReceive('fetchAll')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'group' => [
                    'g.somefield',
                ],
                'order' => [
                    'e.id',
                ],
                'fields' => [
                    'field1',
                ],
                'limit' => 55,
                'offset' => 13,
                'lock' => true,
            ])
            ->andReturn($expectedResult);

        $results = $selectBuilder
            ->field('field1')
            ->inTable('table6')
            ->where([
                'g.somefield' => 33,
            ])
            ->groupBy('g.somefield')
            ->orderBy('e.id')
            ->limitTo(55)
            ->startAt(13)
            ->blocking()
            ->getAllEntries();

        $this->assertSame($expectedResult, $results);
    }

    public function testOneEntry()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['test' => 'one'];

        $this->db
            ->shouldReceive('fetchOne')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                    'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'group' => [
                    'g.somefield',
                ],
                'order' => [
                    'e.id' => 'DESC',
                ],
                'fields' => [
                    'field1',
                    'field6' => 'somefield',
                ],
                'limit' => 1,
                'offset' => 13,
                'lock' => true,
            ])
            ->andReturn($expectedResult);

        $results = $selectBuilder
            ->fields([
                'field1',
                'field6' => 'somefield',
            ])
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->where([
                'g.somefield' => 33,
            ])
            ->groupBy([
                'g.somefield',
            ])
            ->orderBy([
                'e.id' => 'DESC',
            ])
            ->limitTo(55)
            ->startAt(13)
            ->blocking()
            ->getOneEntry();

        $this->assertSame($expectedResult, $results);
    }

    public function testIterator()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['test' => 'one'];

        $expectedResult = new SelectIterator($this->db, [
            'tables' => [
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ],
            'where' => [
                'g.somefield' => 33,
            ],
            'group' => [
                'g.somefield',
            ],
            'order' => [
                'e.id' => 'DESC',
            ],
            'fields' => [
                'field1',
                'field6' => 'somefield',
            ],
            'limit' => 55,
            'offset' => 13,
            'lock' => true,
        ]);

        $results = $selectBuilder
            ->fields([
                'field1',
                'field6' => 'somefield',
            ])
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->where([
                'g.somefield' => 33,
            ])
            ->groupBy([
                'g.somefield',
            ])
            ->orderBy([
                'e.id' => 'DESC',
            ])
            ->limitTo(55)
            ->startAt(13)
            ->blocking()
            ->getIterator();

        $this->assertEquals($expectedResult, $results);
    }

    public function testGetFlattenedFields()
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['one', 5];

        $this->db
            ->shouldReceive('fetchAll')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                    'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'group' => [
                    'g.somefield',
                ],
                'order' => [
                    'e.id',
                ],
                'fields' => [
                    'field1',
                    'field6' => 'somefield',
                ],
                'limit' => 55,
                'offset' => 13,
                'lock' => true,
                'flattenFields' => true,
            ])
            ->andReturn($expectedResult);

        $results = $selectBuilder
            ->fields([
                'field1',
                'field6' => 'somefield',
            ])
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->where([
                'g.somefield' => 33,
            ])
            ->groupBy([
                'g.somefield',
            ])
            ->orderBy([
                'e.id',
            ])
            ->limitTo(55)
            ->startAt(13)
            ->blocking()
            ->getFlattenedFields();

        $this->assertSame($expectedResult, $results);
    }
}
