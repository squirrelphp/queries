<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\MockInterface;
use Squirrel\Connection\ConnectionInterface;
use Squirrel\Connection\ConnectionQueryInterface;
use Squirrel\Queries\DB\AbstractImplementation;
use Squirrel\Queries\DB\DBSelectQuery;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Test abstract Doctrine implementation parts
 */
class ImplementationTest extends \PHPUnit\Framework\TestCase
{
    use SharedFunctionalityTrait;

    private AbstractImplementation&MockInterface $db;
    private ConnectionInterface&MockInterface $connection;

    protected function setUp(): void
    {
        $this->connection = \Mockery::mock(ConnectionInterface::class);
        $this->connection->shouldReceive('quoteIdentifier')->andReturnUsing([$this, 'quoteIdentifier']);

        $this->db = \Mockery::mock(AbstractImplementation::class)->makePartial();
        $this->db->__construct($this->connection);
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

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        $result = $this->db->transaction(function () {
            // Make sure we are now in a transaction
            $this->assertSame(true, $this->db->inTransaction());

            // A commit is expected next
            $this->connection
                ->shouldReceive('commitTransaction')
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
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $result = $this->db->select($query, $vars);

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testFetch(): void
    {
        $statementResult = \Mockery::mock(ConnectionQueryInterface::class);

        $selectQuery = new DBSelectQuery($statementResult);

        $returnValue = ['fieldName' => 'dada'];

        $this->connection
            ->shouldReceive('fetchOne')
            ->once()
            ->with(IsEqual::equalTo($statementResult))
            ->andReturn($returnValue);

        $result = $this->db->fetch($selectQuery);

        $this->assertEquals($returnValue, $result);
    }

    public function testClear(): void
    {
        $statementResult = \Mockery::mock(ConnectionQueryInterface::class);

        $selectQuery = new DBSelectQuery($statementResult);

        $this->connection
            ->shouldReceive('freeResults')
            ->once()
            ->with(IsEqual::equalTo($statementResult));

        $this->db->clear($selectQuery);

        $this->assertTrue(true);
    }

    public function testFetchOne(): void
    {
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $returnValue = ['dada'];

        $this->connection
            ->shouldReceive('fetchOne')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchOne($query, $vars);

        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAll(): void
    {
        $query = 'SELECT blabla FROM yudihui';
        $vars = [0, 'dada', 3.5];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $returnValue = ['dada', 'mumu', 'hihihi'];

        $this->connection
            ->shouldReceive('fetchAll')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchAll($query, $vars);

        $this->assertEquals($returnValue, $result);
    }

    public function testInsert(): void
    {
        $query = 'INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)';
        $vars = [5, 'Dada', 1, 43535];

        $this->prepareForQueryWithVars($query, $vars);

        $this->db->insert('tableName', [
            'id' => 5,
            'name' => 'Dada',
            'active' => true,
            'lastUpdate' => 43535,
        ]);

        $this->assertTrue(true);
    }

    public function testLastInsertId(): void
    {
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (?,?,?)';
        $vars = [5, 'Dada', 43535];

        $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('lastInsertId')
            ->once()
            ->withNoArgs()
            ->andReturn(5);

        $result = $this->db->insert('tableName', [
            'id' => 5,
            'name' => 'Dada',
            'lastUpdate' => 43535,
        ], 'id');

        $this->assertEquals('5', $result);
    }

    public function testUpdate(): void
    {
        $query = [
            'changes' => [
                'boringfield' => 33,
            ],
            'table' => 'dada',
            'where' => [
                'blabla' => 5,
            ],
        ];

        $queryAsString = 'UPDATE "dada" SET "boringfield"=? WHERE "blabla"=?';
        $vars = [33, 5];

        $this->db
            ->shouldReceive('change')
            ->once()
            ->with(IsEqual::equalTo($queryAsString), IsEqual::equalTo($vars))
            ->andReturn(33);

        $results = $this->db->update($query['table'], $query['changes'], $query['where']);

        $this->assertSame(33, $results);
    }

    public function testChange(): void
    {
        $query = 'INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)';
        $vars = [5, 'Dada', 1, 43535];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(5);

        $result = $this->db->change('INSERT INTO "tableName" ("id","name","active","lastUpdate") VALUES (?,?,?,?)', [
            5,
            'Dada',
            true,
            43535,
        ]);

        $this->assertEquals(5, $result);
    }

    public function testChangeSimple(): void
    {
        $query = 'INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)';

        $statement = $this->prepareForQueryWithVars($query, []);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(5);

        $result = $this->db->change('INSERT INTO "tableName" ("id","name","lastUpdate") VALUES (5,"Dada",4534)');

        $this->assertEquals(5, $result);
    }

    public function testInsertOrUpdateEmulationUpdate(): void
    {
        $query = 'UPDATE "tablename" SET "name"=? WHERE "id"=?';
        $vars = ['Andy', 13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(1);

        $this->connection
            ->shouldReceive('commitTransaction')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id']);

        $this->assertTrue(true);
    }

    public function testInsertOrUpdateEmulationInsert(): void
    {
        $query = 'UPDATE "tablename" SET "name"=? WHERE "id"=?';
        $vars = ['Andy', 13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(0);

        $insertQuery = 'INSERT INTO "tablename" ("id","name") VALUES (?,?)';
        $insertVars = [13, 'Andy'];

        $insertStatement = $this->prepareForQueryWithVars($insertQuery, $insertVars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($insertStatement))
            ->andReturn(1);

        $this->connection
            ->shouldReceive('commitTransaction')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id']);

        $this->assertTrue(true);
    }

    public function testInsertOrUpdateEmulationDoNothingInsert(): void
    {
        $query = 'UPDATE "tablename" SET "id"="id" WHERE "id"=?';
        $vars = [13];

        $this->connection
            ->shouldReceive('beginTransaction')
            ->once();

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(0);

        $insertQuery = 'INSERT INTO "tablename" ("id","name") VALUES (?,?)';
        $insertVars = [13, 'Andy'];

        $insertStatement = $this->prepareForQueryWithVars($insertQuery, $insertVars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($insertStatement))
            ->andReturn(1);

        $this->connection
            ->shouldReceive('commitTransaction')
            ->once();

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], ['id'], []);

        $this->assertTrue(true);
    }

    public function testDelete(): void
    {
        $query = 'DELETE FROM "tablename" WHERE "mamamia"=? AND "fumbal" IN (?,?,?)';
        $vars = [13, 3, 5, 9];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(5);

        $result = $this->db->delete('tablename', [
            'mamamia' => $vars[0],
            'fumbal' => [$vars[1], $vars[2], $vars[3]],
        ]);

        $this->assertEquals(5, $result);
    }

    public function testDeleteSimple(): void
    {
        $query = 'DELETE FROM "tablename" WHERE ("mamamia"=1)';

        $statement = $this->prepareForQueryWithVars($query, []);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(5);

        $result = $this->db->delete('tablename', [
            '"mamamia"=1',
        ]);

        $this->assertEquals(5, $result);
    }

    public function testNoLowerLayer(): void
    {
        $this->expectException(\LogicException::class);

        // No lower layer may be set with the actual implementation
        $this->db->setLowerLayer(\Mockery::mock(AbstractImplementation::class));
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
        $query = 'SELECT "blabla" FROM "yudihui" WHERE "lala"=?';
        $vars = [5];

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

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

        $this->assertEquals(new DBSelectQuery($statement), $result);

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

        $result = $this->db->select([
            'field' => 'blabla',
            'table' => 'yudihui',
            'where' => [
                'lala' => 5,
            ],
        ]);

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredCatchAll(): void
    {
        $query = 'SELECT "a".*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

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

        $this->assertEquals(new DBSelectQuery($statement), $result);

        $query = 'SELECT a.*,"b"."lala" FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

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

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredCatchAllNoFields(): void
    {
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b" WHERE "a"."lala"=?';
        $vars = [5];

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

        $result = $this->db->select([
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [
                'a.lala' => 5,
            ],
        ]);

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredNoWhere(): void
    {
        $query = 'SELECT * FROM "yudihui" "a","ahoi" "b"';
        $vars = [];

        $statement = $this->prepareForQueryWithVars($query, $vars, expectFreeResults: false);

        $result = $this->db->select([
            'tables' => [
                'yudihui a',
                ':ahoi: :b:',
            ],
            'where' => [],
        ]);

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testSelectStructuredComplicated(): void
    {
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
            'GROUP BY "a"."field",DATE("a"."field") ' .
            'ORDER BY "a"."field" DESC,"a"."field" + "b"."field" ASC';
        $vars = [5, 'orders_xml_override', 5, 3, 8, 13, 1];

        $statement = $this->prepareForQueryWithVars($query . ' LIMIT 10 OFFSET 5', $vars, expectFreeResults: false);

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
                'DATE(:a.field:)',
            ],
            'order' => [
                'a.field' => 'DESC',
                ':a.field: + :b.field:'
            ],
            'limit' => 10,
            'offset' => 5,
        ]);

        $this->assertEquals(new DBSelectQuery($statement), $result);
    }

    public function testFetchOneStructured(): void
    {
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

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

    public function testFetchAllStructured(): void
    {
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

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

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        $this->connection
            ->shouldReceive('fetchAll')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchAll($structuredQuery);

        $this->assertEquals($returnValue, $result);
    }

    public function testFetchAllStructuredFlattened(): void
    {
        $query = 'SELECT "user_agent_id" AS "id","user_agent_hash" AS "hash" ' .
            'FROM "blobs"."aa_stats_user_agents" WHERE "user_agent_hash"=?';
        $vars = ['Mozilla'];

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

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $returnValue = [['id' => '5', 'hash' => 'fhsdkj']];

        $this->connection
            ->shouldReceive('fetchAll')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchAllAndFlatten($structuredQuery);

        $this->assertEquals(['5', 'fhsdkj'], $result);
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
            'AND ("s"."user_id" <= 0) ' .
            'FOR UPDATE';
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

        $query = 'SELECT "c"."cart_id","c"."checkout_step","s"."session_id","s"."user_id","s"."domain" ' .
            'FROM ' . '"carts"' . ' "c",' . '"sessions"' . ' "s" ' .
            'WHERE (c.session_id = s.session_id) ' .
            'AND "c"."cart_id"=? ' .
            'AND "s"."session_id"=? ' .
            'AND "s"."session_start"=? ' .
            'AND "c"."create_date"=? ' .
            'AND "s"."domain"=? ' .
            'AND (s.user_id <= 0)';

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

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('fetchOne')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn($returnValue);

        $result = $this->db->fetchOne($structuredQuery);

        $this->assertEquals($returnValue, $result);
    }

    public function testUpdateStructured(): void
    {
        $query = 'UPDATE "blobs"."aa_sexy" SET "anyfieldname"=? WHERE "blabla"=?';
        $vars = ['nicevalue', 5];

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(33);

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

        $statement = $this->prepareForQueryWithVars($query, $vars);

        $this->connection
            ->shouldReceive('rowCount')
            ->once()
            ->with(IsEqual::equalTo($statement))
            ->andReturn(33);

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

        $this->db->update('blobs.aa_sexy', [], [
            'blabla' => 5,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadChange(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $this->db->update('blobs.aa_sexy', [new \stdClass()], [
            'blabla' => 5,
            'dada' => true,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadChange2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

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

        $this->db->update('blobs.aa_sexy', ['no_assignment'], [
            'blabla' => 5,
        ]);
    }

    public function testConvertToUpdateSQLStringInvalidOptionBadExpression2(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $this->db->update('blobs.aa_sexy', [':no_equal_sign:' => 5], ['blabla' => 5]);
    }

    public function testInsertOrUpdateEmulationNoIndex(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $this->db->insertOrUpdateEmulation('tablename', [
            'id' => 13,
            'name' => 'Andy',
        ], []);
    }

    public function testInsertOrUpdateEmulationIndexNotInRow(): void
    {
        $this->expectException(DBInvalidOptionException::class);

        $this->db->insertOrUpdateEmulation('tablename', [
            'id2' => 13,
            'name' => 'Andy',
        ], ['id']);
    }
}
