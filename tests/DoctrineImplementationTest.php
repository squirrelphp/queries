<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Doctrine\DBAbstractImplementation;
use Squirrel\Queries\Doctrine\DBSelectQuery;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test abstract Doctrine implementation parts
 */
class DoctrineImplementationTest extends \PHPUnit\Framework\TestCase
{
    /** @var DBAbstractImplementation&MockInterface */
    private DBAbstractImplementation $db;
    /** @var Connection&MockInterface */
    private Connection $connection;

    /**
     * Prepare common aspects of all tests
     */
    protected function setUp(): void
    {
        // Mock of the Doctrine Connection class
        $this->connection = \Mockery::mock(Connection::class);

        // Implementation class
        $this->db = \Mockery::mock(DBAbstractImplementation::class)->makePartial();
        $this->db->__construct($this->connection);

        // Make sure quoteIdentifier works as expected
        $this->connection
            ->shouldReceive('quoteIdentifier')
            ->andReturnUsing(function (string $identifier): string {
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
            });
    }

    /**
     * Check that we correctly return the connection object
     */
    public function testConnection(): void
    {
        $this->assertSame($this->connection, $this->db->getConnection());
    }

    /**
     * Check correct return values for transaction bool
     */
    public function testInTransaction(): void
    {
        $this->assertSame(false, $this->db->inTransaction());
        $this->db->setTransaction(true);
        $this->assertSame(true, $this->db->inTransaction());
        $this->db->setTransaction(false);
        $this->assertSame(false, $this->db->inTransaction());
    }

    public function testTransaction(): void
    {
        // Make sure no transaction is running at the beginning
        $this->assertSame(false, $this->db->inTransaction());

        // Expect a "beginTransaction" call
        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        // Actual transaction call
        $result = $this->db->transaction(function () {
            // Make sure we are now in a transaction
            $this->assertSame(true, $this->db->inTransaction());

            // A commit is expected next
            $this->connection
                ->shouldReceive('commit')
                ->once();

            // Return value to make sure it is passed along
            return 'blabla';
        });

        // Make sure the transaction has ended with the correct result
        $this->assertSame('blabla', $result);
        $this->assertSame(false, $this->db->inTransaction());
    }

    public function testTransactionWithinTransaction(): void
    {
        // Set transaction to "yes"
        $this->db->setTransaction(true);

        // Actual transaction call
        $result = $this->db->transaction(function () {
            // Make sure we are now in a transaction
            $this->assertSame(true, $this->db->inTransaction());

            // Return value to make sure it is passed along
            return 'blabla';
        });

        // Make sure the transaction has ended with the correct result
        $this->assertSame('blabla', $result);
        $this->assertSame(true, $this->db->inTransaction());
    }

    public function testSelect(): void
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

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

        $result = $this->db->select($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testFetch(): void
    {
        // Doctrine result
        $statementResult = \Mockery::mock(Result::class);

        // Select query object
        $selectQuery = new DBSelectQuery($statementResult);

        // Return value from fetch
        $returnValue = ['fieldName' => 'dada'];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($returnValue);

        // Make the fetch call
        $result = $this->db->fetch($selectQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testClear(): void
    {
        // Doctrine result
        $statementResult = \Mockery::mock(Result::class);

        // Select query object
        $selectQuery = new DBSelectQuery($statementResult);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Make the fetch call
        $this->db->clear($selectQuery);

        // Make sure the query has ended with the correct result
        $this->assertTrue(true);
    }

    public function testFetchOne(): void
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

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
        $returnValue = ['dada'];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->fetchOne($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAll(): void
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

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
        $returnValue = ['dada', 'mumu', 'hihihi'];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->fetchAll($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
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

    public function testInsert(): void
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)';
        $vars = [5, 'Dada', 1, 43535];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Insert query call
        $this->db->insert('tableName', [
            'id' => 5,
            'name' => 'Dada',
            'active' => true,
            'lastUpdate' => 43535,
        ]);

        // Make sure the query has ended with the correct result
        $this->assertTrue(true);
    }

    public function testLastInsertId(): void
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (?,?,?)';
        $vars = [5, 'Dada', 43535];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        $this->connection
            ->shouldReceive('lastInsertId')
            ->once()
            ->with(IsEqual::equalTo('tableName_id_seq'))
            ->andReturn(5);

        // Insert query call
        $result = $this->db->insert('tableName', [
            'id' => 5,
            'name' => 'Dada',
            'lastUpdate' => 43535,
        ], 'id');

        // Make sure the query has ended with the correct result
        $this->assertEquals('5', $result);
    }

    public function testUpdate(): void
    {
        // Query we use to test the update
        $query = [
            'changes' => [
                'boringfield' => 33,
            ],
            'table' => 'dada',
            'where' => [
                'blabla' => 5,
            ],
        ];

        // What we convert the query into
        $queryAsString = 'UPDATE "dada" SET "boringfield"=? WHERE "blabla"=?';
        $vars = [33, 5];

        // Change call within the db class
        $this->db
            ->shouldReceive('change')
            ->once()
            ->with(IsEqual::equalTo($queryAsString), IsEqual::equalTo($vars))
            ->andReturn(33);

        // Call the update
        $results = $this->db->update($query['table'], $query['changes'], $query['where']);

        // Make sure we received the right results
        $this->assertSame(33, $results);
    }

    public function testChange(): void
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)';
        $vars = [5, 'Dada', 1, 43535];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Insert query call
        $result = $this->db->change('INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)', [
            5,
            'Dada',
            true,
            43535,
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testChangeSimple(): void
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)';

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
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Insert query call
        $result = $this->db->change('INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)');

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testInsertOrUpdateEmulationUpdate(): void
    {
        // Expected query and parameters
        $query = 'UPDATE "tablename" SET "name"=? WHERE "id"=?';
        $vars = ['Andy', 13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        $this->connection
            ->shouldReceive('commit')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id']);

        $this->assertTrue(true);
    }

    public function testInsertOrUpdateEmulationInsert(): void
    {
        // Expected query and parameters
        $query = 'UPDATE "tablename" SET "name"=? WHERE "id"=?';
        $vars = ['Andy', 13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Expected query and parameters
        $insertQuery = 'INSERT INTO "tablename" ("id","name") VALUES (?,?)';
        $insertVars = [13, 'Andy'];

        // Doctrine statement
        $insertStatement = \Mockery::mock(Statement::class);
        $insertStatementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($insertQuery))
            ->andReturn($insertStatement);

        $this->bindValues($insertStatement, $insertVars);

        // "Execute" call on doctrine result statement
        $insertStatement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($insertStatementResult);

        // "RowCount" call on doctrine result statement
        $insertStatementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);

        // Close result set
        $insertStatementResult
            ->shouldReceive('free')
            ->once();

        $this->connection
            ->shouldReceive('commit')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id']);

        $this->assertTrue(true);
    }

    public function testInsertOrUpdateEmulationDoNothingInsert(): void
    {
        // Expected query and parameters
        $query = 'UPDATE "tablename" SET "id"="id" WHERE "id"=?';
        $vars = [13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(0);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Expected query and parameters
        $insertQuery = 'INSERT INTO "tablename" ("id","name") VALUES (?,?)';
        $insertVars = [13, 'Andy'];

        // Doctrine statement
        $insertStatement = \Mockery::mock(Statement::class);
        $insertStatementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($insertQuery))
            ->andReturn($insertStatement);

        $this->bindValues($insertStatement, $insertVars);

        // "Execute" call on doctrine result statement
        $insertStatement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($insertStatementResult);

        // "RowCount" call on doctrine result statement
        $insertStatementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(1);

        // Close result set
        $insertStatementResult
            ->shouldReceive('free')
            ->once();

        $this->connection
            ->shouldReceive('commit')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id'], []);

        $this->assertTrue(true);
    }

    public function testDelete(): void
    {
        // Expected query and parameters
        $query = 'DELETE FROM "tablename" WHERE "mamamia"=? AND "fumbal" IN (?,?,?)';
        $vars = [13, 3, 5, 9];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Insert query call
        $result = $this->db->delete('tablename', [
            'mamamia' => $vars[0],
            'fumbal' => [$vars[1], $vars[2], $vars[3]],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testDeleteSimple(): void
    {
        // Expected query and parameters
        $query = 'DELETE FROM "tablename" WHERE ("mamamia"=1)';

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
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        // Insert query call
        $result = $this->db->delete('tablename', [
            '"mamamia"=1',
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testNoLowerLayer(): void
    {
        // Expect an InvalidArgument exception
        $this->expectException(\LogicException::class);

        // No lower layer may be set with the actual implementation
        $this->db->setLowerLayer(\Mockery::mock(DBAbstractImplementation::class));
    }

    public function testNoSelectObject1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Make a mock of just the interface, although we need a DBSelectQuery object
        $selectQueryInterface = \Mockery::mock(DBSelectQueryInterface::class);

        // No valid DBSelectQuery object - should throw an exception
        $this->db->fetch($selectQueryInterface);
    }

    public function testNoSelectObject2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Make a mock of just the interface, although we need a DBSelectQuery object
        $selectQueryInterface = \Mockery::mock(DBSelectQueryInterface::class);

        // No valid DBSelectQuery object - should throw an exception
        $this->db->clear($selectQueryInterface);
    }

    public function testInsertNoTableName(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Invalid insert statement without table name
        $this->db->insert('', [
            'blabla' => 'dada',
        ]);
    }

    public function testDeleteNoTableName(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Invalid insert statement without table name
        $this->db->delete('', [
            'blabla' => 'dada',
        ]);
    }

    public function testSelectStructuredSimple(): void
    {
        // Query parameters
        $query = 'SELECT "blabla" FROM "yudihui" WHERE "lala"=?';
        $vars = [5];

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

        $result = $this->db->select([
            'fields' => [
                'blabla',
            ],
            'tables' => [
                'yudihui',
            ],
            'where' => [
                'lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);

        $result = $this->db->select([
            'field' => 'blabla',
            'table' => 'yudihui',
            'where' => [
                'lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testSelectStructuredCatchAll(): void
    {
        // Query parameters
        $query = 'SELECT "a".*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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

        $result = $this->db->select([
            'fields' => [
                ':a:.*',
                ':b.lala:',
            ],
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [
                'a.lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);

        // Query parameters
        $query = 'SELECT a.*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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

        $result = $this->db->select([
            'fields' => [
                'a.*',
                'b.lala',
            ],
            'tables' => [
                'yudihui a',
                'ahoi b',
            ],
            'where' => [
                'a.lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testSelectStructuredCatchAllNoFields(): void
    {
        // Query parameters
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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

        $result = $this->db->select([
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [
                'a.lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testSelectStructuredNoWhere(): void
    {
        // Query parameters
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b"';
        $vars = [];

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

        $result = $this->db->select([
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testSelectStructuredComplicated(): void
    {
        // Query parameters
        $query = 'SELECT "fufumama","b"."lalala","a"."setting_value" AS "result",' .
            '("a"."setting_value"+"b"."blabla_value") AS "result2" ' .
            'FROM "blobs"."aa_sexy" "a","blobs"."aa_blubli" "b" ' .
            'LEFT JOIN "blobs"."aa_blubla" "c" ON ("c"."field" = "b"."field5" AND "b"."sexy" = ?) ' .
            'WHERE ' .
            '("a"."field" = "b"."field") ' .
            'AND "setting_id"=? ' .
            'AND "boring_field_name" IN (?,?,?,?) ' .
            'AND "another_boring_name" IS NULL ' .
            'AND "boolean_field"=? ' .
            'GROUP BY "a"."field" ' .
            'ORDER BY "a"."field" DESC,"a"."field" + "b"."field" ASC';
        $vars = [5, 'orders_xml_override', 5, 3, 8, 13, 1];

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
            ->with(IsEqual::equalTo($query . ' LIMIT 10 OFFSET 5'))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->with(IsEqual::equalTo($vars))
            ->andReturn($statementResult);

        $result = $this->db->select([
            'fields' => [
                'fufumama',
                'b.lalala',
                'result' => 'a.setting_value',
                'result2' => ':a.setting_value:+:b.blabla_value:',
            ],
            'tables' => [
                'blobs.aa_sexy a',
                ':blobs.aa_blubli: :b: ' .
                'LEFT JOIN :blobs.aa_blubla: :c: ON (:c.field: = :b.field5: AND :b.sexy: = ?)' => 5,
            ],
            'where' => [
                ':a.field: = :b.field:',
                'setting_id' => 'orders_xml_override',
                'boring_field_name' => [5, 3, 8, 13],
                'another_boring_name' => null,
                'boolean_field' => true,
            ],
            'group' => [
                'a.field',
            ],
            'order' => [
                'a.field' => 'DESC',
                ':a.field: + :b.field:'
            ],
            'limit' => 10,
            'offset' => 5,
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statementResult), $result);
    }

    public function testFetchOneStructured(): void
    {
        // Query parameters
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

        // Structured query to test
        $structuredQuery = [
            'fields' => [
                'id' => 'user_agent_id',
                'hash' => 'user_agent_hash',
            ],
            'tables' => [
                'blobs.aa_stats_user_agents',
            ],
            'where' => [
                'user_agent_hash' => 'Mozilla',
            ],
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

    public function testFetchAllStructured(): void
    {
        // Query parameters
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

        // Structured query to test
        $structuredQuery = [
            'fields' => [
                'id' => 'user_agent_id',
                'hash' => 'user_agent_hash',
            ],
            'tables' => [
                'blobs.aa_stats_user_agents',
            ],
            'where' => [
                'user_agent_hash' => 'Mozilla',
            ],
        ];

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
        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->fetchAll($structuredQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAllStructuredFlattened(): void
    {
        // Query parameters
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

        // Structured query to test
        $structuredQuery = [
            'fields' => [
                'id' => 'user_agent_id',
                'hash' => 'user_agent_hash',
            ],
            'tables' => [
                'blobs.aa_stats_user_agents',
            ],
            'where' => [
                'user_agent_hash' => 'Mozilla',
            ],
        ];

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
        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        // Fetch result set
        $statementResult
            ->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->fetchAllAndFlatten($structuredQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals(['5', 'fhsdkj'], $result);
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
            'AND ("s"."user_id" <= 0) ' .
            'FOR UPDATE';
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

        // Query parameters
        $query = 'SELECT "c"."cart_id","c"."checkout_step","s"."session_id","s"."user_id","s"."domain" ' .
            'FROM ' . '"carts"' . ' "c",' . '"sessions"' . ' "s" ' .
            'WHERE (c.session_id = s.session_id) ' .
            'AND "c"."cart_id"=? ' .
            'AND "s"."session_id"=? ' .
            'AND "s"."session_start"=? ' .
            'AND "c"."create_date"=? ' .
            'AND "s"."domain"=? ' .
            'AND (s.user_id <= 0)';
        $vars = [5, 'aagdhf', 13, 19, 'example.com'];

        // Structured query to test
        $structuredQuery = [
            'tables' => [
                'carts c',
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
                'c.session_id = s.session_id',
                'c.cart_id' => 5,
                's.session_id' => 'aagdhf',
                's.session_start' => 13,
                'c.create_date' => 19,
                's.domain' => 'example.com',
                's.user_id <= 0',
            ],
        ];

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

    public function testUpdateStructured(): void
    {
        $query = 'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=? WHERE "blabla"=?';
        $vars = ['nicevalue', 5];

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

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(33);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->update('blobs.aa_sexy', [
            'anyfieldname' => 'nicevalue',
        ], [
            'blabla' => 5,
        ]);

        $this->assertEquals(33, $result);
    }

    public function testUpdateStructuredNULL(): void
    {
        $query = 'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=?,"active"=? WHERE "blabla"=?';
        $vars = ['nicevalue', null, 1, 5];

        // Doctrine statement and result
        $statement = \Mockery::mock(Statement::class);
        $statementResult = \Mockery::mock(Result::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->bindValues($statement, $vars);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('executeQuery')
            ->once()
            ->withNoArgs()
            ->andReturn($statementResult);

        // "RowCount" call on doctrine result statement
        $statementResult
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(33);

        // Close result set
        $statementResult
            ->shouldReceive('free')
            ->once();

        $result = $this->db->update('blobs.aa_sexy', [
            'anyfieldname' => 'nicevalue',
            'nullentry' => null,
            'active' => true,
        ], [
            'blabla' => 5,
        ]);

        $this->assertEquals(33, $result);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => 'stringinsteadofarray',
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                new \stdClass(),
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields3(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'field' => new \stdClass(),
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields4(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                ':field:' => 'field',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields5(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                8,
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTables1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTables2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => 'stringisnotallowed',
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTables3(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                new \stdClass(),
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTables4(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                5,
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTablesWithNULL(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                ':blobs.aa_sexy: :a: LEFT JOIN :blobs.dada: :b: ON (:a.setting_id: = ?)' => null,
            ],
            'where' => [
                'setting_id' => 'orders_xml_override',
                ':sexy:>?' => '5',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionWhere1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => 'stringnotallowed',
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionWhere2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                new \stdClass(),
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionWhere3(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => new \stdClass(),
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionWhere4(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => [[1, 2]],
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionWhere5(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionGroup1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'group' => [
                [5],
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionGroup2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'group' => [
                new \stdClass(),
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionOrder1(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'order' => [
                new \stdClass(),
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionOrder2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'order' => [
                5,
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionOrder3(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'order' => [
                'bla' => 'invalidvalue',
            ],
            'limit' => 10,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionLimit(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'limit' => -2,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionLimitNoInteger(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'limit' => '54a',
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionLimitBoolean(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'limit' => true,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionOffset(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'offset' => -2,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionOffsetNoInteger(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'offset' => '45.',
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionLockNoBool(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'lock' => 4,
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionFieldAndFields(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'field' => 'boringfield',
            'fields' => [
                'boringfield',
            ],
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionTableAndTables(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'table' => 'blobs.aa_sexy',
            'tables' => [
                'blobs.aa_sexy',
            ],
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }

    public function testConvertToSelectSQLStringInvalidOptionResourceUsed(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->select([
            'fields' => [
                'boringfield',
            ],
            'table' => tmpfile(),
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionNoChanges(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update('blobs.aa_sexy', [], [
            'blabla' => 5,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadChange(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update('blobs.aa_sexy', [new \stdClass()], [
            'blabla' => 5,
            'dada' => true,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadChange2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update('blobs.aa_sexy', [
            'dada' => new \stdClass(),
        ], [
            'blabla' => 5,
            'dada' => true,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadExpression(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update('blobs.aa_sexy', ['no_assignment'], [
            'blabla' => 5,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadExpression2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update('blobs.aa_sexy', [':no_equal_sign:' => 5], ['blabla' => 5]);
    }

    public function testInsertOrUpdateEmulationNoIndex(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], []);
    }

    public function testInsertOrUpdateEmulationIndexNotInRow(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->insertOrUpdateEmulation('tablename', [
            'id2' => 13,
            'name' => 'Andy',
        ], ['id']);
    }
}
