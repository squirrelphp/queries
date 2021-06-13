<?php

namespace Squirrel\Queries\Tests\Builder;

use Mockery\MockInterface;
use Squirrel\Queries\Builder\UpdateEntries;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

class UpdateEntriesTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBInterface&MockInterface */
    private DBInterface $db;

    protected function setUp(): void
    {
        $this->db = \Mockery::mock(DBInterface::class);
    }

    public function testNoDataGetEntries(): void
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with('', [], [])
            ->andReturn($expectedResult);

        $updateBuilder
            ->confirmNoWhereRestrictions()
            ->write();

        $this->assertTrue(true);
    }

    public function testNoDataGetEntriesWithAffected(): void
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with('', [], [])
            ->andReturn($expectedResult);

        $results = $updateBuilder
            ->confirmNoWhereRestrictions()
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntries(): void
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with('table6', [
                    'somefield' => 33,
                    ':otherfield: = :otherfield: + 1',
                ], [
                    'somefield' => 33,
                    'multipleValues' => [3,66],
                ])
            ->andReturn($expectedResult);

        $results = $updateBuilder
            ->inTable('table6')
            ->set([
                'somefield' => 33,
                ':otherfield: = :otherfield: + 1',
            ])
            ->where([
                'somefield' => 33,
                'multipleValues' => [3,66],
            ])
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testNoWhereNoConfirmation(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with('', [], [])
            ->andReturn($expectedResult);

        $updateBuilder->write();
    }
}
