<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\DeleteEntries;
use Squirrel\Queries\DBInterface;

class DeleteEntriesTest extends \PHPUnit\Framework\TestCase
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
        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('', [])
            ->andReturn($expectedResult);

        $deleteBuilder->write();

        $this->assertTrue(true);
    }

    public function testNoDataGetEntriesWithAffected()
    {
        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('', [])
            ->andReturn($expectedResult);

        $results = $deleteBuilder->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntries()
    {
        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('table788', [
                'g.somefield' => 33,
                'multipleValues' => [3,66],
            ])
            ->andReturn($expectedResult);

        $results = $deleteBuilder
            ->inTable('table788')
            ->where([
                'g.somefield' => 33,
                'multipleValues' => [3,66],
            ])
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }
}
