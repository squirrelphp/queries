<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\CountEntries;
use Squirrel\Queries\DBInterface;

class CountEntriesTest extends \PHPUnit\Framework\TestCase
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
        $countBuilder = new CountEntries($this->db);

        $expectedResult = [33];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
            ->once()
            ->with([
                'fields' => [
                    'num' => 'COUNT(*)',
                ],
                'tables' => [],
                'where' => [],
                'lock' => false,
            ])
            ->andReturn($expectedResult);

        $results = $countBuilder->getNumber();

        $this->assertSame($expectedResult[0], $results);
    }

    public function testGetEntries()
    {
        $countBuilder = new CountEntries($this->db);

        $expectedResult = [33];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
            ->once()
            ->with([
                'fields' => [
                    'num' => 'COUNT(*)',
                ],
                'tables' => [
                    'table6',
                    'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'lock' => true,
            ])
            ->andReturn($expectedResult);

        $results = $countBuilder
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->where([
                'g.somefield' => 33,
            ])
            ->blocking()
            ->getNumber();

        $this->assertSame($expectedResult[0], $results);
    }

    public function testGetEntriesSingular()
    {
        $countBuilder = new CountEntries($this->db);

        $expectedResult = [33];

        $this->db
            ->shouldReceive('fetchAllAndFlatten')
            ->once()
            ->with([
                'fields' => [
                    'num' => 'COUNT(*)',
                ],
                'tables' => [
                    'table6',
                ],
                'where' => [
                    'g.somefield' => 33,
                ],
                'lock' => true,
            ])
            ->andReturn($expectedResult);

        $results = $countBuilder
            ->inTable('table6')
            ->where([
                'g.somefield' => 33,
            ])
            ->blocking()
            ->getNumber();

        $this->assertSame($expectedResult[0], $results);
    }
}
