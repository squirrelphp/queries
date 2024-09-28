<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Connection\Config\Mysql;
use Squirrel\Connection\PDO\ConnectionPDO;
use Squirrel\Queries\DB\MySQLImplementation;
use Squirrel\Queries\DBInterface;

class MySQLIntegrationTest extends AbstractCommonTests
{
    protected static function shouldExecuteTests(): bool
    {
        return isset($_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL']);
    }

    protected static function waitUntilThisDatabaseReady(): void
    {
        if (!self::shouldExecuteTests()) {
            return;
        }

        static::waitUntilDatabaseReady($_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL'], 3306);
    }

    protected static function getConnection(): DBInterface
    {
        return new MySQLImplementation(new ConnectionPDO(
            new Mysql(
                host: $_SERVER['SQUIRREL_CONNECTION_HOST_MYSQL'],
                user: $_SERVER['SQUIRREL_CONNECTION_USER'],
                password: $_SERVER['SQUIRREL_CONNECTION_PASSWORD'],
                dbname: $_SERVER['SQUIRREL_CONNECTION_DBNAME'],
            ),
        ));
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
        self::$db = static::getConnectionAndInitializeAccount();

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
