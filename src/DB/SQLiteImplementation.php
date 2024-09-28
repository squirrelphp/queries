<?php

namespace Squirrel\Queries\DB;

use Squirrel\Debug\Debug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * DB SQLite implementation with custom upsert functionality
 *
 * This is equal to the Postgres version, as SQLite uses the same syntax
 */
class SQLiteImplementation extends PostgreSQLImplementation
{
    private ?float $sqliteVersion = null;

    /**
     * @codeCoverageIgnore
     */
    public function insertOrUpdate(
        string $table,
        array $row = [],
        array $index = [],
        ?array $update = null,
    ): void {
        if ($this->sqliteVersion === null) {
            $connection = $this->getConnection();

            $statement = $connection->prepareQuery('select sqlite_version() AS "v"');
            $connection->executeQuery($statement);
            $result = $connection->fetchOne($statement);
            $connection->freeResults($statement);

            if (!isset($result['v'])) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'SQLite version could not be retrieved',
                    ignoreClasses: DBInterface::class,
                );
            }

            $this->sqliteVersion = \floatval($result['v']);
        }

        // SQLite below version 3.24 does not offer native upsert, so emulate it
        if ($this->sqliteVersion < 3.24) {
            $this->insertOrUpdateEmulation($table, $row, $index, $update);
        } else {
            parent::insertOrUpdate($table, $row, $index, $update);
        }
    }

    protected function convertStructuredSelectToQuery(array $select): array
    {
        [$sql, $queryValues] = parent::convertStructuredSelectToQuery($select);

        // SQLite does not support ... FOR UPDATE and it is not needed, so we remove it
        if (($select['lock'] ?? false) === true) {
            $sql = \substr($sql, 0, -11);
        }

        return [$sql, $queryValues];
    }
}
