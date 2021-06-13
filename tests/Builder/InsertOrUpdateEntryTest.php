<?php

namespace Squirrel\Queries\Tests\Builder;

use Mockery\MockInterface;
use Squirrel\Queries\Builder\InsertOrUpdateEntry;
use Squirrel\Queries\DBInterface;

class InsertOrUpdateEntryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBInterface&MockInterface */
    private DBInterface $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataInsert(): void
    {
        $insertBuilder = new InsertOrUpdateEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('', [], [], []);

        $insertBuilder->write();

        $this->assertTrue(true);
    }

    public function testInsert(): void
    {
        $insertBuilder = new InsertOrUpdateEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ], [
                'fieldName',
            ], [
                'floaty' => 33,
            ]);

        $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->index([
                'fieldName',
            ])
            ->setOnUpdate([
                'floaty' => 33,
            ])
            ->write();

        $this->assertTrue(true);
    }

    public function testInsertWithReturn(): void
    {
        $insertBuilder = new InsertOrUpdateEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ], [
                'fieldName',
            ], [
                'floaty' => 33,
            ]);

        $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->index([
                'fieldName',
            ])
            ->setOnUpdate([
                'floaty' => 33,
            ])
            ->write();

        $this->assertTrue(true);
    }

    public function testUpdateWithReturn(): void
    {
        $insertBuilder = new InsertOrUpdateEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ], [
                'fieldName',
            ], [
                ':floaty: = :floaty: + 1'
            ]);

        $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->index('fieldName')
            ->setOnUpdate(':floaty: = :floaty: + 1')
            ->write();

        $this->assertTrue(true);
    }

    public function testNoChangeWithReturn(): void
    {
        $insertBuilder = new InsertOrUpdateEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insertOrUpdate')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ], [
                'fieldName',
            ], [
                ':floaty: = :floaty: + 1'
            ])
            ->andReturn('update');

        $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->index('fieldName')
            ->setOnUpdate(':floaty: = :floaty: + 1')
            ->write();

        $this->assertTrue(true);
    }
}
