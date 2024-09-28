<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Connection\Config\Pgsql;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Queries\DB\PostgreSQLImplementation;
use Squirrel\Queries\DBInterface;

class PostgreSQLIntegrationTest extends AbstractCommonTests
{
    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        if (!self::shouldExecuteTests()) {
            return;
        }

        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'], 5432);
    }

    protected static function getConnection(): DBInterface
    {
        return new PostgreSQLImplementation(new ConnectionPDO(
            new Pgsql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_POSTGRES'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        ));
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id serial PRIMARY KEY,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) UNIQUE NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance NUMERIC(9,2) DEFAULT 0,
                  description BYTEA,
                  picture BYTEA,
                  active BOOLEAN,
                  create_date INTEGER NOT NULL
                );';
    }

    public function testSpecialTypes(): void
    {
        self::$db = static::getConnectionAndInitializeAccount();

        self::$db->change('DROP TABLE IF EXISTS locations');

        self::$db->change(
            'CREATE TABLE locations (
               current_location POINT,
               ip_address INET,
               create_date INTEGER NOT NULL
             );',
        );

        self::$db->insert('locations', [
            'current_location' => '(5,13)',
            'ip_address' => '212.55.108.55',
            'create_date' => 34534543,
        ]);

        $entry = self::$db->fetchOne([
            'table' => 'locations',
        ]);

        if ($entry === null) {
            throw new \LogicException('Inserted row not found');
        }

        $this->assertEquals([
            'current_location' => '(5,13)',
            'ip_address' => '212.55.108.55',
            'create_date' => '34534543',
        ], $entry);
    }
}
