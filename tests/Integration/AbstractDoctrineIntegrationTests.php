<?php

namespace Squirrel\Queries\Tests\Integration;

use Squirrel\Queries\DBInterface;
use Squirrel\Queries\LargeObject;
use Squirrel\Types\Coerce;

abstract class AbstractDoctrineIntegrationTests extends \PHPUnit\Framework\TestCase
{
    protected static ?DBInterface $db = null;

    abstract protected static function initializeDatabaseAndGetConnection(): ?DBInterface;

    abstract protected static function createAccountTableQuery(): string;

    protected static function waitUntilDatabaseReady(string $host, int $port): void
    {
        $maxSleep = 60;

        while (!@fsockopen($host, $port)) {
            $maxSleep--;

            // Quit after 60 seconds
            if ($maxSleep <= 0) {
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

        // Recreate account table
        self::$db->change('DROP TABLE IF EXISTS account');
        self::$db->change($this->createAccountTableQuery());
    }

    public function testInsert(): void
    {
        if (self::$db === null) {
            return;
        }

        $accountData = [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.2,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => 48674935,
        ];

        $userId = self::$db->insert('account', $accountData, 'user_id');

        $this->assertSame('1', $userId);

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
        $accountData['user_id'] = Coerce::toInt($userId);

        $this->compareDataArrays($accountData, $insertedData);
    }

    public function testInsertOrUpdateWithUpdate(): void
    {
        if (self::$db === null) {
            return;
        }

        self::$db->insert('account', [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => '48674935',
        ], 'user_id');

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
            'create_date' => 48671935,
        ];

        self::$db->insertOrUpdate('account', $accountData, ['user_id']);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 1,
            ],
        ]);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['phone'] = null;
        $insertedData['user_id'] = \intval($insertedData['user_id']);

        $this->compareDataArrays($accountData, $insertedData);
    }

    public function testInsertOrUpdateWithInsert(): void
    {
        if (self::$db === null) {
            return;
        }

        self::$db->insert('account', [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => '48674935',
        ], 'user_id');

        $accountData = [
            'user_id' => 2,
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'other@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 300,
            'description' => 'I am dynamic and nice!',
            'picture' => \md5('dadaism'),
            'active' => true,
            'create_date' => 48674935,
        ];

        self::$db->insertOrUpdate('account', $accountData, ['user_id']);

        $insertedData = self::$db->fetchOne([
            'table' => 'account',
            'where' => [
                'user_id' => 2,
            ],
        ]);

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['phone'] = null;

        $this->compareDataArrays($accountData, $insertedData);
    }

    public function testInsertOrUpdateNoUpdate(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

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

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $this->assertEquals(800, Coerce::toInt($insertedData['balance']));
    }

    public function testUpdate(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

        $picture = new LargeObject(\hex2bin(\md5('dadaism')));

        $accountData = [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 800,
            'description' => 'I am dynamicer and nicer!',
            'picture' => $picture,
            'active' => true,
            'create_date' => 486749356,
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

        if ($insertedData === null) {
            throw new \LogicException('Inserted row not found');
        }

        $accountData['picture'] = $picture->getString();
        $accountData['phone'] = null;
        $accountData['user_id'] = 2;

        $this->compareDataArrays($accountData, $insertedData);

        $accountData['picture'] = $picture;

        // UPDATE where we do not change anything and test if we still get 1 as $rowsAffected
        $rowsAffected = self::$db->update('account', $accountData, ['user_id' => 2]);

        $this->assertEquals(1, $rowsAffected);
    }

    public function testCount(): void
    {
        if (self::$db === null) {
            return;
        }

        $rowData = self::$db->fetchOne([
            'table' => 'account',
            'fields' => [
                'num' => 'COUNT(*)',
            ],
        ]);

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['num'] = \intval($rowData['num']);

        $this->assertEquals(['num' => 0], $rowData);

        $this->initializeDataWithDefaultTwoEntries();

        $rowData = self::$db->fetchOne([
            'table' => 'account',
            'fields' => [
                'num' => 'COUNT(*)',
            ],
        ]);

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['num'] = \intval($rowData['num']);

        $this->assertEquals(['num' => 2], $rowData);
    }

    public function testSelect(): void
    {
        if (self::$db === null) {
            return;
        }

        $rowData = self::$db->fetchOne([
            'table' => 'account',
        ]);

        $this->assertEquals(null, $rowData);

        $this->initializeDataWithDefaultTwoEntries();

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

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['user_id'] = \intval($rowData['user_id']);
        $rowData['active'] = \boolval($rowData['active']);
        $rowData['create_date'] = \intval($rowData['create_date']);
        $rowData['balance'] = \round($rowData['balance'], 2);

        $this->assertEquals($accountData, $rowData);
    }

    public function testSelectFlattened(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

        $userIds = self::$db->fetchAllAndFlatten([
            'table' => 'account',
            'field' => 'user_id',
        ]);

        $this->assertEquals([1, 2], [intval($userIds[0]), intval($userIds[1])]);

        $flattenedResults = self::$db->fetchAllAndFlatten([
            'table' => 'account',
            'field' => 'username',
        ]);

        $this->assertEquals(['Mary', 'John'], $flattenedResults);
    }

    public function testSelectFieldsWithAlias(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

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

        if ($rowData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $rowData['id'] = \intval($rowData['id']);
        $rowData['active'] = \boolval($rowData['active']);

        $this->assertEquals($accountData, $rowData);
    }

    public function testSelectWithGroupByOrderBy(): void
    {
        if (self::$db === null) {
            return;
        }

        self::$db->insert('account', [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 300,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => '48674935',
        ], 'user_id');

        self::$db->insert('account', [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => '800',
            'description' => 'I am dynamicer and nicer!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => 1,
            'create_date' => '486749356',
        ], 'user_id');

        self::$db->insert('account', [
            'user_id' => 3,
            'username' => 'Liam',
            'password' => 'password',
            'email' => 'lala@mary.com',
            'birthdate' => '1964-05-08',
            'balance' => 300,
            'active' => false,
            'create_date' => 586749356,
        ]);

        self::$db->insert('account', [
            'user_id' => 4,
            'username' => 'Liam',
            'password' => 'password2',
            'email' => 'liam@mary.com',
            'birthdate' => '1954-05-08',
            'balance' => 900,
            'active' => false,
            'create_date' => 686749356,
        ]);

        self::$db->insert('account', [
            'user_id' => 5,
            'username' => 'John',
            'password' => 'password3',
            'email' => 'john573@mary.com',
            'birthdate' => '1944-05-08',
            'balance' => 1100,
            'active' => false,
            'create_date' => 786749356,
        ]);

        self::$db->insert('account', [
            'user_id' => 6,
            'username' => 'John',
            'password' => 'password4',
            'email' => 'john574@mary.com',
            'birthdate' => '1934-05-08',
            'balance' => 1300,
            'active' => false,
            'create_date' => 886749356,
        ]);

        $rowsData = self::$db->fetchAll([
            'table' => 'account',
            'fields' => [
                'username',
                'number' => 'COUNT(*)',
                'totalBalance' => 'SUM(:balance:)',
                'newest' => 'MAX(:create_date:)',
            ],
            'group' => [
                'username',
            ],
            'order' => [
                'totalBalance' => 'DESC',
            ],
        ]);

        $this->assertEquals([
            'username' => 'John',
            'number' => 3,
            'totalBalance' => 3200,
            'newest' => 886749356,
        ], [
            'username' => $rowsData[0]['username'],
            'number' => \intval($rowsData[0]['number']),
            'totalBalance' => \intval($rowsData[0]['totalBalance']),
            'newest' => \intval($rowsData[0]['newest']),
        ]);
        $this->assertEquals([
            'username' => 'Liam',
            'number' => 2,
            'totalBalance' => 1200,
            'newest' => 686749356,
        ], [
            'username' => $rowsData[1]['username'],
            'number' => \intval($rowsData[1]['number']),
            'totalBalance' => \intval($rowsData[1]['totalBalance']),
            'newest' => \intval($rowsData[1]['newest']),
        ]);
        $this->assertEquals([
            'username' => 'Mary',
            'number' => 1,
            'totalBalance' => 300,
            'newest' => 48674935,
        ], [
            'username' => $rowsData[2]['username'],
            'number' => \intval($rowsData[2]['number']),
            'totalBalance' => \intval($rowsData[2]['totalBalance']),
            'newest' => \intval($rowsData[2]['newest']),
        ]);

        $rowsData = self::$db->fetchOne([
            'table' => 'account',
            'fields' => [
                'username',
                'number' => 'COUNT(*)',
                'totalBalance' => 'SUM(:balance:)',
                'newest' => 'MAX(:create_date:)',
            ],
            'group' => [
                'username',
            ],
            'order' => [
                'totalBalance' => 'ASC',
            ],
        ]);

        if ($rowsData === null) {
            throw new \LogicException('Expected row did not exist');
        }

        $this->assertEquals([
            'username' => 'Mary',
            'number' => 1,
            'totalBalance' => 300,
            'newest' => 48674935,
        ], [
            'username' => $rowsData['username'],
            'number' => \intval($rowsData['number']),
            'totalBalance' => \intval($rowsData['totalBalance']),
            'newest' => \intval($rowsData['newest']),
        ]);
        $this->assertEquals(4, \count($rowsData));
    }

    public function testTransactionSelectAndUpdate(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

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

            if ($rowData === null) {
                throw new \LogicException('Expected row did not exist');
            }

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

    public function testDelete(): void
    {
        if (self::$db === null) {
            return;
        }

        $this->initializeDataWithDefaultTwoEntries();

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

    private function initializeDataWithDefaultTwoEntries(): void
    {
        self::$db->insert('account', [
            'username' => 'Mary',
            'password' => 'secret',
            'email' => 'mary@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => 105.20,
            'description' => 'I am dynamic and nice!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => true,
            'create_date' => '48674935',
        ], 'user_id');

        self::$db->insert('account', [
            'username' => 'John',
            'password' => 'othersecret',
            'email' => 'supi@mary.com',
            'birthdate' => '1984-05-08',
            'balance' => '800',
            'description' => 'I am dynamicer and nicer!',
            'picture' => new LargeObject(\hex2bin(\md5('dadaism'))),
            'active' => 1,
            'create_date' => 486749356,
        ], 'user_id');
    }

    private function compareDataArrays(array $expected, array $actual): void
    {
        $this->assertCount(\count($expected), $actual);

        foreach ($expected as $fieldName => $value) {
            if (\is_int($value)) {
                $this->assertSame($value, Coerce::toInt($actual[$fieldName]));
            } elseif (\is_float($value)) {
                $this->assertSame($value, Coerce::toFloat($actual[$fieldName]));
            } elseif (\is_bool($value)) {
                $this->assertSame($value, Coerce::toBool($actual[$fieldName]));
            } else {
                $this->assertSame($value, $actual[$fieldName]);
            }
        }
    }
}
