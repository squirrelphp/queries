<?php

namespace Squirrel\Queries\Tests\Builder;

use Squirrel\Queries\Builder\UpdateEntries;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

class UpdateEntriesTest extends \PHPUnit\Framework\TestCase
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
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with([
                'tables' => [],
                'changes' => [],
                'where' => [],
                'order' => [],
                'limit' => 0,
            ])
            ->andReturn($expectedResult);

        $updateBuilder
            ->confirmNoWhereRestrictions()
            ->write();

        $this->assertTrue(true);
    }

    public function testNoDataGetEntriesWithAffected()
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with([
                'tables' => [],
                'changes' => [],
                'where' => [],
                'order' => [],
                'limit' => 0,
            ])
            ->andReturn($expectedResult);

        $results = $updateBuilder
            ->confirmNoWhereRestrictions()
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntries()
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                    'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
                ],
                'changes' => [
                    'somefield' => 33,
                    ':otherfield: = :otherfield: + 1',
                ],
                'where' => [
                    'g.somefield' => 33,
                    'multipleValues' => [3,66],
                ],
                'order' => [
                    'somefield' => 'DESC',
                ],
                'limit' => 4,
            ])
            ->andReturn($expectedResult);

        $results = $updateBuilder
            ->inTables([
                'table6',
                'otherTable g LEFT JOIN superTable e ON (g.id = e.id AND g.name=?)' => 'Jane',
            ])
            ->set([
                'somefield' => 33,
                ':otherfield: = :otherfield: + 1',
            ])
            ->where([
                'g.somefield' => 33,
                'multipleValues' => [3,66],
            ])
            ->orderBy([
                'somefield' => 'DESC',
            ])
            ->limitTo(4)
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testGetEntriesSingular()
    {
        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with([
                'tables' => [
                    'table6',
                ],
                'changes' => [
                    'somefield' => 33,
                    ':otherfield: = :otherfield: + 1',
                ],
                'where' => [
                    'g.somefield' => 33,
                    'multipleValues' => [3,66],
                ],
                'order' => [
                    'somefield',
                ],
                'limit' => 4,
            ])
            ->andReturn($expectedResult);

        $results = $updateBuilder
            ->inTable('table6')
            ->set([
                'somefield' => 33,
                ':otherfield: = :otherfield: + 1',
            ])
            ->where([
                'g.somefield' => 33,
                'multipleValues' => [3,66],
            ])
            ->orderBy('somefield')
            ->limitTo(4)
            ->writeAndReturnAffectedNumber();

        $this->assertSame($expectedResult, $results);
    }

    public function testNoWhereNoConfirmation()
    {
        $this->expectException(DBInvalidOptionException::class);

        $updateBuilder = new UpdateEntries($this->db);

        $expectedResult = 33;

        $this->db
            ->shouldReceive('update')
            ->once()
            ->with([
                'tables' => [],
                'changes' => [],
                'where' => [],
                'order' => [],
                'limit' => 0,
            ])
            ->andReturn($expectedResult);

        $updateBuilder->write();
    }
}
