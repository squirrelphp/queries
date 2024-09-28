<?php

namespace Squirrel\Queries\Tests;

use Mockery\MockInterface;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Queries\DB\MySQLImplementation;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test custom MySQL implementation parts
 */
class MySQLImplementationTest extends \PHPUnit\Framework\TestCase
{
    use SharedFunctionalityTrait;

    private MySQLImplementation $db;
    private ConnectionInterface&MockInterface $connection;

    protected function setUp(): void
    {
        $this->connection = \Mockery::mock(ConnectionInterface::class);
        $this->connection->shouldReceive('quoteIdentifier')->andReturnUsing([$this, 'quoteIdentifier']);

        $this->db = new MySQLImplementation($this->connection);
    }

    /**
     * Test vanilla upsert without explicit update part
     */
    public function testUpsert(): void
    {
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

        $this->prepareForQueryWithVars($sql, $vars);

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
     * Test upsert with an explicit update part
     */
    public function testUpsertCustomUpdate(): void
    {
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

        $this->prepareForQueryWithVars($sql, $vars);

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

        $this->prepareForQueryWithVars($sql, $vars);

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

        $this->prepareForQueryWithVars($sql, $vars);

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
        $this->expectException(DBInvalidOptionException::class);

        $this->db->insertOrUpdate('', [
            'dada' => 5,
            'fieldname' => 'rowvalue',
        ]);
    }

    public function testUpsertInvalidOptionNoRow(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $this->db->insertOrUpdate('table');
    }
}
