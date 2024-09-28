<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Queries\Builder\CountEntries;
use Squirrel\Queries\Builder\DeleteEntries;
use Squirrel\Queries\Builder\InsertEntry;
use Squirrel\Queries\Builder\InsertOrUpdateEntry;
use Squirrel\Queries\Builder\SelectEntries;
use Squirrel\Queries\Builder\UpdateEntries;
use Squirrel\Queries\DBBuilder;
use Squirrel\Queries\DBInterface;

class DBBuilderTest extends \PHPUnit\Framework\TestCase
{
    private DBInterface&MockInterface $db;
    private DBBuilder $builder;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
        $this->builder = new DBBuilder($this->db);
    }

    public function testCount(): void
    {
        $this->assertEquals(new CountEntries($this->db), $this->builder->count());
    }

    public function testSelect(): void
    {
        $this->assertEquals(new SelectEntries($this->db), $this->builder->select());
    }

    public function testInsert(): void
    {
        $this->assertEquals(new InsertEntry($this->db), $this->builder->insert());
    }

    public function testInsertOrUpdate(): void
    {
        $this->assertEquals(new InsertOrUpdateEntry($this->db), $this->builder->insertOrUpdate());
    }

    public function testUpdate(): void
    {
        $this->assertEquals(new UpdateEntries($this->db), $this->builder->update());
    }

    public function testDelete(): void
    {
        $this->assertEquals(new DeleteEntries($this->db), $this->builder->delete());
    }

    public function testGetDBInterface(): void
    {
        $this->assertSame($this->db, $this->builder->getDBInterface());
    }

    public function testTransaction(): void
    {
        $a = 2;
        $b = 3;
        $c = 37;

        $function = function (int $a, int $b, int $c): int {
            return $a + $b + $c;
        };

        $this->db
            ->shouldReceive('transaction')
            ->once()
            ->with(IsEqual::equalTo($function), IsEqual::equalTo($a), IsEqual::equalTo($b), IsEqual::equalTo($c))
            ->andReturnUsing(function (callable $function, int $a, int $b, int $c): int {
                return $function($a, $b, $c);
            });

        $this->assertSame(42, $this->builder->transaction($function, $a, $b, $c));
    }
}
