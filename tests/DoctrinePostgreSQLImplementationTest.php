<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use Squirrel\Queries\Doctrine\DBPostgreSQLImplementation;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test custom PostgreSQL implementation parts
 */
class DoctrinePostgreSQLImplementationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBPostgreSQLImplementation
     */
    private $db;

    /**
     * @var Connection
     */
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
        $this->db = new DBPostgreSQLImplementation($this->connection);
    }

    private function bindValues($statement, $vars)
    {
        $varCounter = 1;

        foreach ($vars as $var) {
            $statement
                ->shouldReceive('bindValue')
                ->once()
                ->with(\Mockery::mustBe($varCounter++), \Mockery::mustBe($var), \PDO::PARAM_STR);
        }
    }

    public function testFetchOne()
    {
        // Query parameters
        $query = 'SELECT blob FROM yudihui WHERE active = ? AND name = ? AND balance = ?';
        $vars = [0, 'dada', 3.5];

        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($query))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('execute')
            ->once()
            ->with(\Mockery::mustBe($vars));

        $fp = \fopen('php://temp', 'rb+');
        \fwrite($fp, 'binary data!');
        \fseek($fp, 0);

        $statement
            ->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn([
                'blob' => $fp,
            ]);

        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchOne($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals(['blob' => 'binary data!'], $result);
    }

    public function testFetchAll()
    {
        // Query parameters
        $query = 'SELECT blob FROM yudihui WHERE active = ? AND name = ? AND balance = ?';
        $vars = [0, 'dada', 3.5];

        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($query))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('execute')
            ->once()
            ->with(\Mockery::mustBe($vars));

        $fp = \fopen('php://temp', 'rb+');
        \fwrite($fp, 'binary data!');
        \fseek($fp, 0);

        $statement
            ->shouldReceive('fetchAll')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn([[
                'blob' => $fp,
            ]]);

        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchAll($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals([['blob' => 'binary data!']], $result);
    }

    /**
     * Test vanilla upsert without explicit update part
     */
    public function testUpsert()
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') .
            ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ' .
            'ON CONFLICT (' . $this->quoteIdentifier('id') . ',' . $this->quoteIdentifier('id2') . ') DO UPDATE SET ' .
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

        // Statement and the data values it should receive
        $statement = \Mockery::mock(Statement::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('execute')
            ->once()
            ->withNoArgs();

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($sql))
            ->andReturn($statement);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
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
                explode(".", $identifier)
            );

            return implode(".", $parts);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Test upsert with an explicit update part
     */
    public function testUpsertCustomUpdate()
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ' .
            'ON CONFLICT (' . $this->quoteIdentifier('id') . ',' . $this->quoteIdentifier('id2') . ') DO UPDATE SET ' .
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

        // Statement and the data values it should receive
        $statement = \Mockery::mock(Statement::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('execute')
            ->once()
            ->withNoArgs();

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($sql))
            ->andReturn($statement);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
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
    public function testUpsertCustomUpdateWithVars()
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') . ',' .
            $this->quoteIdentifier('name') . ',' .
            $this->quoteIdentifier('bla43') . ',' .
            $this->quoteIdentifier('judihui') .
            ') VALUES (?,?,?,?,?) ' .
            'ON CONFLICT (' . $this->quoteIdentifier('id') . ',' . $this->quoteIdentifier('id2') . ') DO UPDATE SET ' .
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

        // Statement and the data values it should receive
        $statement = \Mockery::mock(Statement::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('execute')
            ->once()
            ->withNoArgs();

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($sql))
            ->andReturn($statement);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
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
    public function testUpsertNoUpdateRows()
    {
        // SQL query which should be generated by the implementation
        $sql = 'INSERT INTO ' . $this->quoteIdentifier('example.example') . ' (' .
            $this->quoteIdentifier('id') . ',' .
            $this->quoteIdentifier('id2') .
            ') VALUES (?,?) ' .
            'ON CONFLICT (' . $this->quoteIdentifier('id') . ',' . $this->quoteIdentifier('id2') . ') DO NOTHING';

        $vars = [
            5,
            6,
        ];

        // Statement and the data values it should receive
        $statement = \Mockery::mock(Statement::class);

        $this->bindValues($statement, $vars);

        $statement
            ->shouldReceive('execute')
            ->once()
            ->withNoArgs();
        $statement
            ->shouldReceive('fetchAll')
            ->once()
            ->andReturn([
                ['case' => 'update'],
            ]);

        // SQL query should be received by "prepare"
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($sql))
            ->andReturn($statement);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
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

    public function testUpsertInvalidOptionNoTableName()
    {
        // Expect an InvalidOptions exception
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdate('', [
            'dada' => 5,
            'fieldname' => 'rowvalue',
        ]);
    }

    public function testUpsertInvalidOptionNoRow()
    {
        // Expect an InvalidOptions exception
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdate('table');
    }
}
