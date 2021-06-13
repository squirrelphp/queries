<?php

namespace Squirrel\Queries\Tests\Builder;

use Mockery\MockInterface;
use Squirrel\Queries\Builder\InsertEntry;
use Squirrel\Queries\DBInterface;

class InsertEntryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBInterface&MockInterface */
    private DBInterface $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataInsert(): void
    {
        $insertBuilder = new InsertEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insert')
            ->once()
            ->with('', []);

        $insertBuilder->write();

        $this->assertTrue(true);
    }

    public function testInsert(): void
    {
        $insertBuilder = new InsertEntry($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('insert')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ]);

        $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->write();

        $this->assertTrue(true);
    }

    public function testInsertWithNewId(): void
    {
        $insertBuilder = new InsertEntry($this->db);

        $expectedResult = '666';

        $this->db
            ->shouldReceive('insert')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ], 'fieldName')
            ->andReturn($expectedResult);

        $result = $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->writeAndReturnNewId('fieldName');

        $this->assertSame($expectedResult, $result);
    }
}
