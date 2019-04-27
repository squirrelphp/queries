<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\InsertEntry;
use Squirrel\Queries\DBInterface;

class InsertEntryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBInterface
     */
    private $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataInsert()
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

    public function testInsert()
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

    public function testInsertWithNewId()
    {
        $insertBuilder = new InsertEntry($this->db);

        $expectedResult = '666';

        $this->db
            ->shouldReceive('insert')
            ->once()
            ->with('someTTTable', [
                'fieldName' => 33,
                'floaty' => 3.7,
            ]);

        $this->db
            ->shouldReceive('lastInsertId')
            ->once()
            ->withNoArgs()
            ->andReturn($expectedResult);

        $result = $insertBuilder
            ->inTable('someTTTable')
            ->set([
                'fieldName' => 33,
                'floaty' => 3.7,
            ])
            ->writeAndReturnNewId();

        $this->assertSame($expectedResult, $result);
    }
}
