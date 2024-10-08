<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Connection\Config\Sqlite;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Queries\DB\SQLiteImplementation;
use Squirrel\Queries\DBInterface;

class SQLiteIntegrationTest extends AbstractCommonTests
{
    protected static function shouldExecuteTests(): bool
    {
        return true;
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        // No need to wait for SQLite, as we are testing with an in-memory database
    }

    protected static function getConnection(): DBInterface
    {
        return new SQLiteImplementation(new ConnectionPDO(new Sqlite()));
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
        self::$db = static::getConnectionAndInitializeAccount();

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
