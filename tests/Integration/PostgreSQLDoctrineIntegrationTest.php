<?php

namespace Squirrel\Queries\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Doctrine\DBPostgreSQLImplementation;

class PostgreSQLDoctrineIntegrationTest extends AbstractDoctrineIntegrationTests
{
    protected static function initializeDatabaseAndGetConnection(): ?DBInterface
    {
        if (!isset($_SERVER['SQUIRREL_TEST_POSTGRES'])) {
            return null;
        }

        static::waitUntilDatabaseReady('squirrel_queries_postgres', 5432);

        // Create a doctrine connection
        $dbalConnection = DriverManager::getConnection([
            'url' => $_SERVER['SQUIRREL_TEST_POSTGRES'],
            'driverOptions' => [
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);

        $sql = 'CREATE TABLE account(
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
        $statement = $dbalConnection->prepare($sql);
        $statement->execute();

        // Create a MySQL implementation layer
        return new DBPostgreSQLImplementation($dbalConnection);
    }
}
