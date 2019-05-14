<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Queries\DBInterface;
use Squirrel\Queries\LargeObject;

abstract class AbstractDoctrineIntegrationTests extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBInterface
     */
    protected static $db;

    abstract protected static function initializeDatabaseAndGetConnection(): ?DBInterface;

    protected static function waitUntilDatabaseReady(string $host, int $port): void
    {
        $maxSleep = 60;

        while (!@fsockopen($host, $port)) {
            $maxSleep--;

            // Quit after 60 seconds
            if ($maxSleep<=0) {
                throw new \Exception('No connection possible to ' . $host . ':' . $port);
            }

            sleep(1);
        }
    }

    public static function setUpBeforeClass(): void
    {
        self::$db = static::initializeDatabaseAndGetConnection();
    }

    protected function setUp(): void
    {
        if (self::$db === null) {
            $this->markTestSkipped('Not in an environment with correct database');
        }
    }

    public function testInsert()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
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

    public function testInsertOrUpdateWithUpdate()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'user_id' => 1,
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'some@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 300,
            'description' => 'I am dynamic and nice!',
            'picture' => \md5('dadaism'),
            'active' => true,
            'create_date' => '48674935',
        ];

        self::$db->insertOrUpdate('account', $accountData, ['user_id']);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 1,
            ],
        ]);

        $accountData['phone'] = null;
        $insertedData['user_id'] = \intval($insertedData['user_id']);

        $accountData['active'] = \intval($accountData['active']);
        $insertedData['active'] = \intval($insertedData['active']);

        $accountData['create_date'] = \intval($accountData['create_date']);
        $insertedData['create_date'] = \intval($insertedData['create_date']);

        $accountData['balance'] = \round($accountData['balance'], 2);
        $insertedData['balance'] = \round($insertedData['balance'], 2);

        $this->assertEquals($accountData, $insertedData);
    }

    public function testInsertOrUpdateWithInsert()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'user_id' => '2',
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'other@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => '300',
            'description' => 'I am dynamic and nice!',
            'picture' => \md5('dadaism'),
            'active' => 1,
            'create_date' => '48674935',
        ];

        self::$db->insertOrUpdate('account', $accountData, ['user_id']);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $accountData['phone'] = null;
        $accountData['user_id'] = \intval($accountData['user_id']);
        $insertedData['user_id'] = \intval($insertedData['user_id']);

        $accountData['active'] = \intval($accountData['active']);
        $insertedData['active'] = \intval($insertedData['active']);

        $accountData['create_date'] = \intval($accountData['create_date']);
        $insertedData['create_date'] = \intval($insertedData['create_date']);

        $accountData['balance'] = \round($accountData['balance'], 2);
        $insertedData['balance'] = \round($insertedData['balance'], 2);

        $this->assertEquals($accountData, $insertedData);
    }

    public function testInsertOrUpdateNoUpdate()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'user_id' => '2',
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'other@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => '500',
            'description' => 'I am dynamic and nice!',
            'picture' => \md5('dadaism'),
            'active' => 1,
            'create_date' => '48674935',
        ];

        self::$db->insertOrUpdate('account', $accountData, ['user_id'], []);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $this->assertEquals(300, \intval($insertedData['balance']));
    }

    public function testUpdate()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => '800',
            'description' => 'I am dynamicer and nicer!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => 1,
            'create_date' => '486749356',
        ];

        // UPDATE where changes are made and we should get one affected row
        $rowsAffected = self::$db->update('account', $accountData, ['user_id' => 2]);

        $this->assertEquals(1, $rowsAffected);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $accountData['picture'] = \hex2bin(\md5('dadaism'));
        $accountData['phone'] = null;
        $accountData['user_id'] = 2;
        $insertedData['user_id'] = \intval($insertedData['user_id']);

        $accountData['active'] = \intval($accountData['active']);
        $insertedData['active'] = \intval($insertedData['active']);

        $accountData['create_date'] = \intval($accountData['create_date']);
        $insertedData['create_date'] = \intval($insertedData['create_date']);

        $accountData['balance'] = \round($accountData['balance'], 2);
        $insertedData['balance'] = \round($insertedData['balance'], 2);

        $this->assertEquals($accountData, $insertedData);

        $accountData['picture'] = new LargeObject(\hex2bin(\md5('dadaism')));

        // UPDATE where we do not change anything and test if we still get 1 as $rowsAffected
        $rowsAffected = self::$db->update('account', $accountData, ['user_id' => 2]);

        $this->assertEquals(1, $rowsAffected);
    }

    public function testSelect()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'user_id' => 2,
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'phone' => null,
            'birthdate' => '1984-05-08',
            'balance' => 800,
            'description' => 'I am dynamicer and nicer!',
            'picture' => \hex2bin(\md5('dadaism')),
            'active' => true,
            'create_date' => 486749356,
        ];

        $rowData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $rowData['user_id'] = \intval($rowData['user_id']);
        $rowData['active'] = \boolval($rowData['active']);
        $rowData['create_date'] = \intval($rowData['create_date']);
        $rowData['balance'] = \round($rowData['balance'], 2);

        $this->assertEquals($accountData, $rowData);
    }

    public function testSelectFieldsWithAlias()
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'id' => 2,
            'birthday' => '1984-05-08',
            'active' => true,
        ];

        $rowData = self::$db->fetchOne([
            'table' => 'account',
            'fields' => [
                'id' => 'user_id',
                'birthday' => 'birthdate',
                'active',
            ],
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $rowData['id'] = \intval($rowData['id']);
        $rowData['active'] = \boolval($rowData['active']);

        $this->assertEquals($accountData, $rowData);
    }

    public function testTransactionSelectAndUpdate()
    {
        if (self::$db === null) {
            return;
        }

        self::$db->transaction(function () {
            $accountData = [
                'id' => 2,
                'birthday' => '1984-05-08',
                'active' => true,
            ];

            $rowData = self::$db->fetchOne([
                'table' => 'account',
                'fields' => [
                    'id' => 'user_id',
                    'birthday' => 'birthdate',
                    'active',
                ],
                'where' => [
                    'active' => true,
                ],
                'order' => [
                    'user_id' => 'DESC',
                ],
                'limit' => 1,
                'offset' => 0,
                'lock' => true,
            ]);

            $rowData['id'] = \intval($rowData['id']);
            $rowData['active'] = \boolval($rowData['active']);

            $this->assertEquals($accountData, $rowData);

            self::$db->update('account', [
                'active' => false,
            ], [
                'user_id' => $rowData['id'],
            ]);
        });
    }

    public function testDelete()
    {
        if (self::$db === null) {
            return;
        }

        $rowsAffected = self::$db->delete('account', ['user_id' => 2]);

        $this->assertEquals(1, $rowsAffected);

        $rowData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        $this->assertEquals(null, $rowData);

        $rowsAffected = self::$db->delete('account', ['user_id' => 2]);

        $this->assertEquals(0, $rowsAffected);
    }
}
