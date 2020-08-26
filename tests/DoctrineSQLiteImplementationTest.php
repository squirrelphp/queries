<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Squirrel\Queries\Doctrine\DBSQLiteImplementation;

class DoctrineSQLiteImplementationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBSQLiteImplementation
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
        $this->db = new DBSQLiteImplementation($this->connection);
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

    public function testFetchOneStructured2()
    {
        // Query parameters
        $query = 'SELECT "c"."cart_id","c"."checkout_step","s"."session_id","s"."user_id","s"."domain" ' .
            'FROM ' . '"carts"' . ' "c",' . '"sessions"' . ' "s" ' .
            'WHERE ("c"."session_id" = "s"."session_id") ' .
            'AND "c"."cart_id"=? ' .
            'AND "s"."session_id"=? ' .
            'AND "s"."session_start"=? ' .
            'AND "c"."create_date"=? ' .
            'AND "s"."domain"=? ' .
            'AND ("s"."user_id" <= 0)';
        $vars = [5, 'aagdhf', 13, 19, 'example.com'];

        // Structured query to test
        $structuredQuery = [
            'tables' => [
                ':carts: :c:',
                'sessions s',
            ],
            'fields' => [
                'c.cart_id',
                'c.checkout_step',
                's.session_id',
                's.user_id',
                's.domain',
            ],
            'where' => [
                ':c.session_id: = :s.session_id:',
                'c.cart_id' => 5,
                's.session_id' => 'aagdhf',
                's.session_start' => 13,
                'c.create_date' => 19,
                's.domain' => 'example.com',
                ':s.user_id: <= 0',
            ],
            'lock' => true,
        ];

        // Emulation of the Doctrine platform class
        $platform = \Mockery::mock(AbstractPlatform::class)->makePartial();

        // Return the platform if it is asked for by the connection
        $this->connection
            ->shouldReceive('getDatabasePlatform')
            ->once()
            ->andReturn($platform);

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

        // Return value from fetch
        $returnValue = ['id' => '5', 'hash' => 'fhsdkj'];

        // Fetch result set
        $statement
            ->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchOne($structuredQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
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
}
