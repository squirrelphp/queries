<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Queries\DB\SQLiteImplementation;

class SQLiteImplementationTest extends \PHPUnit\Framework\TestCase
{
    use SharedFunctionalityTrait;

    private SQLiteImplementation $db;
    private ConnectionInterface&MockInterface $connection;

    protected function setUp(): void
    {
        $this->connection = \Mockery::mock(ConnectionInterface::class);
        $this->connection->shouldReceive('quoteIdentifier')->andReturnUsing([$this, 'quoteIdentifier']);

        $this->db = new SQLiteImplementation($this->connection);
    }

    public function testFetchOneStructured2(): void
    {
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

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $returnValue = ['id' => '5', 'hash' => 'fhsdkj'];

        $this->connection
            ->shouldReceive('fetchOne')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchOne($structuredQuery);

        $this->assertEquals($returnValue, $result);
    }
}
