<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Queries\Doctrine\DBSQLiteImplementation;

class DoctrineSQLiteImplementationTest extends \PHPUnit\Framework\TestCase
{
    private DBSQLiteImplementation $db;
    /** @var Connection&MockInterface */
    private Connection $connection;

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

    public function testFetchOneStructured2(): void
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

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->with(IsEqual::equalTo($vars))
            ->andReturn($statementResult);

        // Return value from fetch
        $returnValue = ['id' => '5', 'hash' => 'fhsdkj'];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
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
