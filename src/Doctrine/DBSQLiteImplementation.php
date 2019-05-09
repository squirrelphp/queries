<?php

namespace Squirrel\Queries\Doctrine;

/**
 * DB SQLite implementation using Doctrine DBAL with custom upsert functionality
 *
 * This is equal to the Postgres version, as SQLite uses the same syntax
 */
class DBSQLiteImplementation extends DBPostgreSQLImplementation
{
    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function insertOrUpdate(string $tableName, array $row = [], array $indexColumns = [], ?array $rowUpdates = null): void
    {
        // SQLite in version 3.24 or above with upsert functionality is only included in PHP 7.3 and above
        // Emulate upsert for PHP below 7.3
        if (PHP_VERSION_ID < 70300) {
            $this->insertOrUpdateEmulation($tableName, $row, $indexColumns, $rowUpdates);
        } else {
            parent::insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);
        }
    }
}
