<?php

namespace Squirrel\Queries\Doctrine;

/**
 * DB SQLite implementation using Doctrine DBAL with custom upsert functionality
 *
 * This is equal to the Postgres version, as SQLite uses the same syntax
 */
class DBSQLiteImplementation extends DBPostgreSQLImplementation
{
    private ?float $sqliteVersion = null;

    /**
     * @codeCoverageIgnore
     */
    public function insertOrUpdate(
        string $tableName,
        array $row = [],
        array $indexColumns = [],
        ?array $rowUpdates = null
    ): void {
        if ($this->sqliteVersion === null) {
            $connection = $this->getConnection();

            $this->sqliteVersion = \floatval($connection->query('select sqlite_version() AS "v"')->fetch()['v']);
        }

        // SQLite below version 3.24 does not offer native upsert, so emulate it
        if ($this->sqliteVersion < 3.24) {
            $this->insertOrUpdateEmulation($tableName, $row, $indexColumns, $rowUpdates);
        } else {
            parent::insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);
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
