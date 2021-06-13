<?php

namespace Squirrel\Queries\Tests\Builder;

use Mockery\MockInterface;
use Squirrel\Queries\Builder\DeleteEntries;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

class DeleteEntriesTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBInterface&MockInterface */
    private DBInterface $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataGetEntries(): void
    {
        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('', [])
            ->andReturn($expectedResult);

        $deleteBuilder
            ->confirmNoWhereRestrictions()
            ->write();

        $this->assertTrue(true);
    }

    public function testNoDataGetEntriesWithAffected(): void
    {
        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('', [])
            ->andReturn($expectedResult);

        $results = $deleteBuilder
            ->confirmNoWhereRestrictions()
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntries(): void
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

    public function testNoWhereNoConfirmation(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $deleteBuilder = new DeleteEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('delete')
            ->once()
            ->with('', [])
            ->andReturn($expectedResult);

        $deleteBuilder->write();
    }
}
