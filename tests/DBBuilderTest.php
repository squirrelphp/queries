<?php

namespace Squirrel\Queries\Tests;

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
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var DBBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
        $this->builder = new DBBuilder($this->db);
    }

    public function testCount()
    {
        $this->assertEquals(new CountEntries($this->db), $this->builder->count());
    }

    public function testSelect()
    {
        $this->assertEquals(new SelectEntries($this->db), $this->builder->select());
    }

    public function testInsert()
    {
        $this->assertEquals(new InsertEntry($this->db), $this->builder->insert());
    }

    public function testInsertOrUpdate()
    {
        $this->assertEquals(new InsertOrUpdateEntry($this->db), $this->builder->insertOrUpdate());
    }

    public function testUpdate()
    {
        $this->assertEquals(new UpdateEntries($this->db), $this->builder->update());
    }

    public function testDelete()
    {
        $this->assertEquals(new DeleteEntries($this->db), $this->builder->delete());
    }

    public function testGetDBInterface()
    {
        $this->assertSame($this->db, $this->builder->getDBInterface());
    }

    public function testTransaction()
    {
        // The three arguments used
        $a = 2;
        $b = 3;
        $c = 37;

        // Transaction function to execute
        $function = function ($a, $b, $c) {
            return $a + $b + $c;
        };

        $this->db
            ->shouldReceive('transaction')
            ->once()
            ->with(\Mockery::mustBe($function), \Mockery::mustBe($a), \Mockery::mustBe($b), \Mockery::mustBe($c))
            ->andReturnUsing(function ($function, $a, $b, $c) {
                return $function($a, $b, $c);
            });

        $this->assertSame(42, $this->builder->transaction($function, $a, $b, $c));
    }
}
