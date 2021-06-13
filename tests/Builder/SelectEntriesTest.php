<?php

namespace Squirrel\Queries\Tests\Builder;

use Mockery\MockInterface;
use Squirrel\Queries\Builder\SelectEntries;
use Squirrel\Queries\Builder\SelectIterator;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

class SelectEntriesTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBInterface&MockInterface */
    private DBInterface $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataGetEntries(): void
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

    public function testGetEntries(): void
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

    public function testGetEntriesSingular(): void
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

    public function testOneEntry(): void
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

    public function testIterator(): void
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

    public function testGetFlattenedFields(): void
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['one', 5];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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
            ->getFlattenedFields();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetFlattenedIntegerFields(): void
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['4', 5, 1, '9'];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $this->assertSame([4, 5, 1, 9], $selectBuilder->getFlattenedIntegerFields());
    }

    public function testGetFlattenedFloatFields(): void
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['1.3', 5, 5.3, '5.3'];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $this->assertSame([1.3, 5.0, 5.3, 5.3], $selectBuilder->getFlattenedFloatFields());
    }

    public function testGetFlattenedStringFields(): void
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['one', 5, 5.3, 'true'];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $this->assertSame(['one', '5', '5.3', 'true'], $selectBuilder->getFlattenedStringFields());
    }

    public function testGetFlattenedBooleanFields(): void
    {
        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['1', 0, 1, '0', true, false];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $this->assertSame([true, false, true, false, true, false], $selectBuilder->getFlattenedBooleanFields());
    }

    public function testGetFlattenedIntegerFieldsWrongScalarType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [5, '5lada', 6, 8];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedIntegerFields();
    }

    public function testGetFlattenedIntegerFieldsWrongNonNumberType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [5, true, 6, 8];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedIntegerFields();
    }

    public function testGetFlattenedFloatFieldsWrongScalarType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [5, 6, 8, '3.7nonnumber'];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedFloatFields();
    }

    public function testGetFlattenedFloatFieldsWrongNonNumberType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [5, 6, 8, true];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedFloatFields();
    }

    public function testGetFlattenedBooleanFieldsWrongType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = [true, false, true, 'dada', false];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedBooleanFields();
    }

    public function testGetFlattenedStringFieldsWrongType(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $selectBuilder = new SelectEntries($this->db);

        $expectedResult = ['dada', '5', 'rtew', false, '7777.3'];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
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

        $selectBuilder
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
            ->blocking();

        $selectBuilder->getFlattenedStringFields();
    }
}
