<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Queries\DB\MySQLImplementation;
use Squirrel\Queries\DBInterface;

class MariaDBIntegrationTest extends MySQLIntegrationTest
{
    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        if (!self::shouldExecuteTests()) {
            return;
        }

        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'], 3306);
    }

    protected static function getConnection(): DBInterface
    {
        return new MySQLImplementation(new ConnectionPDO(
            new Mysql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_MARIADB'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        ));
    }
}
