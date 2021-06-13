<?php

namespace Squirrel\Queries\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Doctrine\DBMySQLImplementation;

class MySQLDoctrineIntegrationTest extends AbstractDoctrineIntegrationTests
{
    protected static function initializeDatabaseAndGetConnection(): ?DBInterface
    {
        if (!isset($_SERVER['SQUIRREL_TEST_MYSQL'])) {
            return null;
        }

        static::waitUntilDatabaseReady('squirrel_queries_mysql', 3306);

        // Create a doctrine connection
        $dbalConnection = DriverManager::getConnection([
            'url' => $_SERVER['SQUIRREL_TEST_MYSQL'],
            'driverOptions' => [
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_FOUND_ROWS => true,
                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
            ],
        ]);

        // Create implementation layer
        return new DBMySQLImplementation($dbalConnection);
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id INT AUTO_INCREMENT,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance DECIMAL(9,2) DEFAULT 0,
                  description BLOB,
                  picture BLOB,
                  active TINYINT,
                  create_date INTEGER NOT NULL,
                  PRIMARY KEY (user_id),
                  UNIQUE (email)
                ) ENGINE InnoDB;';
    }

    public function testInsertNoLargeObject(): void
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mysql@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => \hex2bin(\md5('dadaism')),
            'active' => true,
            'create_date' => '48674935',
        ];

        $userId = self::$db->insert('account', $accountData, 'user_id');

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => $userId,
            ],
        ]);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['picture'] = \hex2bin(\md5('dadaism'));
        $accountData['phone'] = null;
        $accountData['user_id'] = $userId;
        $insertedData['user_id'] = \intval($insertedData['user_id']);

        $accountData['active'] = \intval($accountData['active']);
        $insertedData['active'] = \intval($insertedData['active']);

        $accountData['create_date'] = \intval($accountData['create_date']);
        $insertedData['create_date'] = \intval($insertedData['create_date']);

        $accountData['balance'] = \round($accountData['balance'], 2);
        $insertedData['balance'] = \round($insertedData['balance'], 2);

        $this->assertEquals($accountData, $insertedData);
    }
}
