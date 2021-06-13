<?php

namespace Squirrel\Queries\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Doctrine\DBSQLiteImplementation;

class SQLiteDoctrineIntegrationTest extends AbstractDoctrineIntegrationTests
{
    protected static function initializeDatabaseAndGetConnection(): ?DBInterface
    {
        if (!isset($_SERVER['SQUIRREL_TEST_SQLITE'])) {
            return null;
        }

        // Create a doctrine connection
        $dbalConnection = DriverManager::getConnection([
            'url' => $_SERVER['SQUIRREL_TEST_SQLITE'],
            'driverOptions' => [
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);

        // Create implementation layer
        return new DBSQLiteImplementation($dbalConnection);
    }

    protected static function createAccountTableQuery(): string
    {
        return 'CREATE TABLE account(
                  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                  username VARCHAR (50) NOT NULL,
                  password VARCHAR (50) NOT NULL,
                  email VARCHAR (250) UNIQUE NOT NULL,
                  phone VARCHAR (100) NULL,
                  birthdate DATE NULL,
                  balance DECIMAL(9,2) DEFAULT 0,
                  description BLOB,
                  picture BLOB,
                  active BOOLEAN,
                  create_date INTEGER NOT NULL
                );';
    }

    public function testInsertNoLargeObject(): void
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'sqlite@mary.com',
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
