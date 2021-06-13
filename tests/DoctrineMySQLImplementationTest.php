<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Queries\Doctrine\DBMySQLImplementation;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test custom MySQL implementation parts
 */
class DoctrineMySQLImplementationTest extends \PHPUnit\Framework\TestCase
{
    private DBMySQLImplementation $db;
    /** @var Connection&MockInterface */
    private $connection;

    /**
     * Prepare common aspects of all tests
     */
    protected function setUp(): void
    {
        // Mock of the Doctrine Connection class
        $this->connection = \Mockery::mock(Connection::class);
        $this->connection->shouldReceive('quoteIdentifier')->andReturnUsing([$this, 'quoteIdentifier']);

        // MySQL implementation class
        $this->db = new DBMySQLImplementation($this->connection);
    }

    private function bindValues(MockInterface $statement, array $vars): void
    {
        $varCounter = 1;

        foreach ($vars as $var) {
            $statement
                ->shouldReceive('bindValue')
                ->once()
                ->with(IsEqual::equalTo($varCounter++), IsEqual::equalTo($var), \PDO::PARAM_STR);
        }
    }

    /**
     * Test vanilla upsert without explicit update part
     */
    public function testUpsert(): void
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') .
            ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE ' .
            $this->quoteIdentifier('name') . '=?,' .
            $this->quoteIdentifier('bla43') . '=?,' .
            $this->quoteIdentifier('judihui') . '=?';

        $vars = [
            5,
            6,
            'value',
            'niiiiice',
            1,
            'value',
            'niiiiice',
            1,
        ];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($sql))
            ->andReturn($statement);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Test the upsert
        $this->db->insertOrUpdate('example.example', [
            'id' => 5,
            'id2' => 6,
            'name' => 'value',
            'bla43' => 'niiiiice',
            'judihui' => true,
        ], [
            'id',
            'id2',
        ]);

        $this->assertTrue(true);
    }

    /**
     * Recreated quoteIdentifier function as a standin for the Doctrine one
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        if (strpos($identifier, ".") !== false) {
            $parts = array_map(
                function ($p) {
                    return '"' . str_replace('"', '""', $p) . '"';
                },
                explode(".", $identifier),
            );

            return implode(".", $parts);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Test upsert with an explicit update part
     */
    public function testUpsertCustomUpdate(): void
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE ' .
            $this->quoteIdentifier('name') . '=?,' .
            $this->quoteIdentifier('lala45') . '=?,judihui = judihui + 1,' .
            $this->quoteIdentifier('evenmore') . '=?,' .
            $this->quoteIdentifier('lastone') . '=?';

        $vars = [
            5,
            6,
            'value',
            'niiiiice',
            5,
            'value5',
            '534',
            8,
            'laaaast',
        ];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($sql))
            ->andReturn($statement);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Test the upsert
        $this->db->insertOrUpdate('example.example', [
            'id' => 5,
            'id2' => 6,
            'name' => 'value',
            'bla43' => 'niiiiice',
            'judihui' => 5,
        ], [
            'id',
            'id2',
        ], [
            'name' => 'value5',
            'lala45' => '534',
            'judihui = judihui + 1',
            'evenmore' => 8,
            'lastone' => 'laaaast',
        ]);

        $this->assertTrue(true);
    }

    /**
     * Test upsert with an explicit update part and escapeable variables in there
     */
    public function testUpsertCustomUpdateWithVars(): void
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE ' .
            $this->quoteIdentifier('name') . '=?,' .
            $this->quoteIdentifier('lala45') . '=?,' .
            $this->quoteIdentifier('judihui') . ' = ' . $this->quoteIdentifier('judihui') . ' + 1,' .
            $this->quoteIdentifier('judihui') . ' = ' . $this->quoteIdentifier('judihui') . ' + ?,' .
            $this->quoteIdentifier('evenmore') . '=?,' . $this->quoteIdentifier('lastone') . '=?';

        $vars = [
            5,
            6,
            'value',
            'niiiiice',
            5,
            'value5',
            '534',
            13,
            8,
            'laaaast',
        ];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($sql))
            ->andReturn($statement);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Test the upsert
        $this->db->insertOrUpdate('example.example', [
            'id' => 5,
            'id2' => 6,
            'name' => 'value',
            'bla43' => 'niiiiice',
            'judihui' => 5,
        ], [
            'id',
            'id2',
        ], [
            'name' => 'value5',
            'lala45' => '534',
            ':judihui: = :judihui: + 1',
            ':judihui: = :judihui: + ?' => 13,
            'evenmore' => 8,
            'lastone' => 'laaaast',
        ]);

        $this->assertTrue(true);
    }

    /**
     * Test upsert with an explicit update part and escapeable variables in there
     */
    public function testUpsertNoUpdateRows(): void
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') .
            ') VALUES (?,?) ON DUPLICATE KEY UPDATE ' .
            $this->quoteIdentifier('id') . '=' . $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . '=' . $this->quoteIdentifier('id2');

        $vars = [
            5,
            6,
        ];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($sql))
            ->andReturn($statement);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Test the upsert
        $this->db->insertOrUpdate('example.example', [
            'id' => 5,
            'id2' => 6,
        ], [
            'id',
            'id2',
        ]);

        $this->assertTrue(true);
    }

    public function testUpsertInvalidOptionNoTableName(): void
    {
        // Expect an InvalidOptions exception
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdate('', [
            'dada' => 5,
            'fieldname' => 'rowvalue',
        ]);
    }

    public function testUpsertInvalidOptionNoRow(): void
    {
        // Expect an InvalidOptions exception
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdate('table');
    }
}
