<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\SelectIterator;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBSelectQueryInterface;

class SelectIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var array
     */
    private $query;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
        $this->query = [
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
        ];
    }

    public function testLoop()
    {
        $selectQuery = \Mockery::mock(DBSelectQueryInterface::class);

        $this->db
            ->shouldReceive('select')
            ->once()
            ->with($this->query)
            ->andReturn($selectQuery);

        $this->db
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQuery)
            ->andReturn(['dada' => 55, 'other' => 'Jane']);

        $this->db
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQuery)
            ->andReturn(['dada' => 5888, 'other' => 'Henry']);

        $this->db
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQuery)
            ->andReturn(null);

        $this->db
            ->shouldReceive('clear')
            ->once()
            ->with($selectQuery);

        $iterator = new SelectIterator($this->db, $this->query);

        $assertionsCount = 0;

        foreach ($iterator as $key => $entry) {
            if ($key === 0) {
                $this->assertEquals(['dada' => 55, 'other' => 'Jane'], $entry);
                $assertionsCount++;
            } elseif ($key === 1) {
                $this->assertEquals(['dada' => 5888, 'other' => 'Henry'], $entry);
                $assertionsCount++;
            } else {
                $this->assertTrue(false);
            }
        }

        $iterator->clear();

        $this->assertEquals(2, $assertionsCount);

        $this->db
            ->shouldReceive('select')
            ->once()
            ->with($this->query)
            ->andReturn($selectQuery);

        $this->db
            ->shouldReceive('fetch')
            ->once()
            ->with($selectQuery)
            ->andReturn(null);

        foreach ($iterator as $key => $entry) {
            $this->assertTrue(false);
        }
    }
}
