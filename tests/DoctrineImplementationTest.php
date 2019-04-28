<?php

namespace Squirrel\Queries\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Doctrine\DBAbstractImplementation;
use Squirrel\Queries\Doctrine\DBSelectQuery;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test abstract Doctrine implementation parts
 */
class DoctrineImplementationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBAbstractImplementation
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

        // Implementation class
        $this->db = \Mockery::mock(DBAbstractImplementation::class)->makePartial();
        $this->db->__construct($this->connection);

        // Make sure quoteIdentifier works as expected
        $this->connection
            ->shouldReceive('quoteIdentifier')
            ->andReturnUsing(function ($identifier) {
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
            });
    }

    /**
     * Check that we correctly return the connection object
     */
    public function testConnection()
    {
        $this->assertSame($this->connection, $this->db->getConnection());
    }

    /**
     * Check correct return values for transaction bool
     */
    public function testInTransaction()
    {
        $this->assertSame(false, $this->db->inTransaction());
        $this->db->setTransaction(true);
        $this->assertSame(true, $this->db->inTransaction());
        $this->db->setTransaction(false);
        $this->assertSame(false, $this->db->inTransaction());
    }

    public function testTransaction()
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

    public function testTransactionWithinTransaction()
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

    public function testSelect()
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
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

        $result = $this->db->select($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testFetch()
    {
        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // Select query object
        $selectQuery = new DBSelectQuery($statement);

        // Return value from fetch
        $returnValue = ['fieldName' => 'dada'];

        // Fetch result set
        $statement
            ->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn($returnValue);

        // Make the fetch call
        $result = $this->db->fetch($selectQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testClear()
    {
        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // Select query object
        $selectQuery = new DBSelectQuery($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Make the fetch call
        $this->db->clear($selectQuery);

        // Make sure the query has ended with the correct result
        $this->assertTrue(true);
    }

    public function testFetchOne()
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
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

        // Return value from fetch
        $returnValue = ['dada'];

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

        $result = $this->db->fetchOne($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAll()
    {
        // Query parameters
        $query = 'SELECT blabla FROM yudihui';
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

        // Return value from fetch
        $returnValue = ['dada', 'mumu', 'hihihi'];

        // Fetch result set
        $statement
            ->shouldReceive('fetchAll')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchAll($query, $vars);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testInsert()
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (?,?,?)';
        $vars = [5, 'Dada', 43535];

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

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Insert query call
        $result = $this->db->insert('tableName', [
            'id' => 5,
            'name' => 'Dada',
            'lastUpdate' => 43535,
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testLastInsertId()
    {
        // Query parameters
        $name = 'yayyay';

        // "lastInsertId" call to doctrine connection
        $this->connection
            ->shouldReceive('lastInsertId')
            ->once()
            ->with(\Mockery::mustBe($name))
            ->andReturn(88);

        $result = $this->db->lastInsertId($name);

        // Make sure the query has ended with the correct result
        $this->assertEquals(88, $result);
    }

    public function testUpdate()
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
            ->with(\Mockery::mustBe($queryAsString), \Mockery::mustBe($vars))
            ->andReturn(33);

        // Call the update
        $results = $this->db->update($query);

        // Make sure we received the right results
        $this->assertSame(33, $results);
    }

    public function testChange()
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (?,?,?)';
        $vars = [5, 'Dada', 43535];

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

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Insert query call
        $result = $this->db->change('INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (?,?,?)', [
            5,
            'Dada',
            43535,
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testChangeSimple()
    {
        // Expected query and parameters
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)';

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
            ->with(\Mockery::mustBe([]));

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Insert query call
        $result = $this->db->change('INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)');

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testDelete()
    {
        // Expected query and parameters
        $query = 'DELETE FROM "tablename" WHERE "mamamia"=? AND "fumbal" IN (?,?,?)';
        $vars = [13, 3, 5, 9];

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

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Insert query call
        $result = $this->db->delete('tablename', [
            'mamamia' => $vars[0],
            'fumbal' => [$vars[1], $vars[2], $vars[3]],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testDeleteSimple()
    {
        // Expected query and parameters
        $query = 'DELETE FROM "tablename" WHERE ("mamamia"=1)';

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
            ->with(\Mockery::mustBe([]));

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        // Insert query call
        $result = $this->db->delete('tablename', [
            '"mamamia"=1',
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(5, $result);
    }

    public function testNoLowerLayer()
    {
        // Expect an InvalidArgument exception
        $this->expectException(\LogicException::class);

        // No lower layer may be set with the actual implementation
        $this->db->setLowerLayer(\Mockery::mock(DBAbstractImplementation::class));
    }

    public function testNoSelectObject1()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Make a mock of just the interface, although we need a DBSelectQuery object
        $selectQueryInterface = \Mockery::mock(DBSelectQueryInterface::class);

        // No valid DBSelectQuery object - should throw an exception
        $this->db->fetch($selectQueryInterface);
    }

    public function testNoSelectObject2()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Make a mock of just the interface, although we need a DBSelectQuery object
        $selectQueryInterface = \Mockery::mock(DBSelectQueryInterface::class);

        // No valid DBSelectQuery object - should throw an exception
        $this->db->clear($selectQueryInterface);
    }

    public function testInsertNoTableName()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Invalid insert statement without table name
        $this->db->insert('', [
            'blabla' => 'dada',
        ]);
    }

    public function testDeleteNoTableName()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Invalid insert statement without table name
        $this->db->delete('', [
            'blabla' => 'dada',
        ]);
    }

    public function testSelectStructuredSimple()
    {
        // Query parameters
        $query = 'SELECT "blabla" FROM "yudihui" WHERE "lala"=?';
        $vars = [5];

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
        $this->assertEquals(new DBSelectQuery($statement), $result);

        $result = $this->db->select([
            'field' => 'blabla',
            'table' => 'yudihui',
            'where' => [
                'lala' => 5,
            ],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredCatchAll()
    {
        // Query parameters
        $query = 'SELECT "a".*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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
        $this->assertEquals(new DBSelectQuery($statement), $result);

        // Query parameters
        $query = 'SELECT a.*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredCatchAllNoFields()
    {
        // Query parameters
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

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
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredNoWhere()
    {
        // Query parameters
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b"';
        $vars = [];

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

        $result = $this->db->select([
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [],
        ]);

        // Make sure the query has ended with the correct result
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredComplicated()
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

        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($query . ' LIMIT 10 OFFSET 5'))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('execute')
            ->once()
            ->with(\Mockery::mustBe($vars));

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
        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testFetchOneStructured()
    {
        // Query parameters
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=? LIMIT 1';
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

    public function testFetchAllStructured()
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
        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        // Fetch result set
        $statement
            ->shouldReceive('fetchAll')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchAll($structuredQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAllStructuredFlattened()
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
            'flattenFields' => true,
        ];

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
        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        // Fetch result set
        $statement
            ->shouldReceive('fetchAll')
            ->once()
            ->with(\Mockery::mustBe(FetchMode::ASSOCIATIVE))
            ->andReturn($returnValue);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->fetchAll($structuredQuery);

        // Make sure the query has ended with the correct result
        $this->assertEquals(['5', 'fhsdkj'], $result);
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
            'AND ("s"."user_id" <= 0) ' .
            'LIMIT 1 FOR UPDATE';
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

        // Query parameters
        $query = 'SELECT "c"."cart_id","c"."checkout_step","s"."session_id","s"."user_id","s"."domain" ' .
            'FROM ' . '"carts"' . ' "c",' . '"sessions"' . ' "s" ' .
            'WHERE (c.session_id = s.session_id) ' .
            'AND "c"."cart_id"=? ' .
            'AND "s"."session_id"=? ' .
            'AND "s"."session_start"=? ' .
            'AND "c"."create_date"=? ' .
            'AND "s"."domain"=? ' .
            'AND (s.user_id <= 0) ' .
            'LIMIT 1';
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

    public function testUpdateStructured()
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

        // Doctrine statement
        $statement = \Mockery::mock(ResultStatement::class);

        // "Prepare" call to doctrine connection
        $this->connection
            ->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::mustBe($query . ' LIMIT 13'))
            ->andReturn($statement);

        // "Execute" call on doctrine result statement
        $statement
            ->shouldReceive('execute')
            ->once()
            ->with(\Mockery::mustBe($vars));

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(33);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->update([
            'table' => 'blobs.aa_sexy',
            'changes' => [
                'anyfieldname' => 'nicevalue',
            ],
            'where' => [
                'blabla' => 5,
            ],
            'limit' => 13,
        ]);

        $this->assertEquals(33, $result);
    }

    public function testUpdateStructuredNULL()
    {
        $query = 'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=?,"nullentry"=? WHERE "blabla"=?';
        $vars = ['nicevalue', null, 5];

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

        // "RowCount" call on doctrine result statement
        $statement
            ->shouldReceive('rowCount')
            ->once()
            ->andReturn(33);

        // Close result set
        $statement
            ->shouldReceive('closeCursor')
            ->once();

        $result = $this->db->update([
            'table' => 'blobs.aa_sexy',
            'changes' => [
                'anyfieldname' => 'nicevalue',
                'nullentry' => null,
            ],
            'where' => [
                'blabla' => 5,
            ],
        ]);

        $this->assertEquals(33, $result);
    }

    public function testConvertToSelectSQLStringInvalidOptionFields1()
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

    public function testConvertToSelectSQLStringInvalidOptionFields2()
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

    public function testConvertToSelectSQLStringInvalidOptionFields3()
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

    public function testConvertToSelectSQLStringInvalidOptionFields4()
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

    public function testConvertToSelectSQLStringInvalidOptionFields5()
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

    public function testConvertToSelectSQLStringInvalidOptionTables1()
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

    public function testConvertToSelectSQLStringInvalidOptionTables2()
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

    public function testConvertToSelectSQLStringInvalidOptionTables3()
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

    public function testConvertToSelectSQLStringInvalidOptionTables4()
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

    public function testConvertToSelectSQLStringInvalidOptionTablesWithNULL()
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

    public function testConvertToSelectSQLStringInvalidOptionWhere1()
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

    public function testConvertToSelectSQLStringInvalidOptionWhere2()
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

    public function testConvertToSelectSQLStringInvalidOptionWhere3()
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

    public function testConvertToSelectSQLStringInvalidOptionWhere4()
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

    public function testConvertToSelectSQLStringInvalidOptionWhere5()
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

    public function testConvertToSelectSQLStringInvalidOptionGroup1()
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

    public function testConvertToSelectSQLStringInvalidOptionGroup2()
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

    public function testConvertToSelectSQLStringInvalidOptionOrder1()
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

    public function testConvertToSelectSQLStringInvalidOptionOrder2()
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

    public function testConvertToSelectSQLStringInvalidOptionOrder3()
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

    public function testConvertToSelectSQLStringInvalidOptionLimit()
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

    public function testConvertToSelectSQLStringInvalidOptionLimitNoInteger()
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

    public function testConvertToSelectSQLStringInvalidOptionLimitBoolean()
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

    public function testConvertToSelectSQLStringInvalidOptionOffset()
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

    public function testConvertToSelectSQLStringInvalidOptionOffsetNoInteger()
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

    public function testConvertToSelectSQLStringInvalidOptionLockNoBool()
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

    public function testConvertToSelectSQLStringInvalidOptionFieldAndFields()
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

    public function testConvertToSelectSQLStringInvalidOptionTableAndTables()
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

    public function testConvertToSelectSQLStringInvalidOptionResourceUsed()
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

    public function testConvertToUpdateSQLStringInvalidOptionNoChanges()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update([
            'changes' => [],
            'table' => 'blobs.aa_sexy',
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadChange()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update([
            'changes' => [
                new \stdClass(),
            ],
            'table' => 'blobs.aa_sexy',
            'where' => [
                'blabla' => 5,
                'dada' => true,
            ],
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadExpression()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update([
            'changes' => [
                'no_assignment',
            ],
            'table' => 'blobs.aa_sexy',
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadExpression2()
    {
        $this->expectException(DBInvalidOptionException::class);

        // Try it with the invalid option
        $this->db->update([
            'changes' => [
                ':no_equal_sign:' => 5,
            ],
            'table' => 'blobs.aa_sexy',
            'where' => [
                'blabla' => 5,
            ],
        ]);
    }
}
