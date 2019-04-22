<?php

namespace Squirrel\Queries\Doctrine;

use Squirrel\Queries\DBDebug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * DB MySQL implementation using Doctrine DBAL with custom upsert functionality
 */
class DBMySQLImplementation extends DBAbstractImplementation
{
    /**
     * @inheritDoc
     */
    public function upsert(string $tableName, array $row = [], array $indexColumns = [], array $rowUpdates = []): int
    {
        // No table name specified
        if (strlen($tableName) === 0) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for upsert'
            );
        }

        // No insert row specified
        if (count($row) === 0) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No insert data specified for upsert for table "' . $tableName . '"'
            );
        }

        // No update fields defined, so we assume the table is changed the same way
        // as with the insert
        if (count($rowUpdates) === 0) {
            // Copy over insert fields and values
            $rowUpdates = $row;

            // Remove index fields for update
            foreach ($indexColumns as $fieldName) {
                unset($rowUpdates[$fieldName]);
            }
        }

        // Divvy up the field names, values and placeholders for the INSERT part
        $columnsForInsert = array_map([$this, 'quoteIdentifier'], array_keys($row));
        $placeholdersForInsert = array_fill(0, count($row), '?');
        $queryValues = array_values($row);

        // No update, so just make a dummy update
        if (count($rowUpdates) === 0) {
            $updatePart = '1=1';
        } else { // Generate update part of the query
            [$updatePart, $queryValues] = $this->structuredQueryConverter->buildChanges($rowUpdates, $queryValues);
        }

        // Generate the insert query
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName) .
            ' (' . (count($columnsForInsert) > 0 ? implode(',', $columnsForInsert) : '') . ') ' .
            'VALUES (' . (count($columnsForInsert) > 0 ? implode(',', $placeholdersForInsert) : '') . ') ' .
            'ON DUPLICATE KEY UPDATE ' . $updatePart;

        // Return 1 if a row was inserted, 2 if a row was updated, and 0 if there was no change
        return $this->change($query, $queryValues);
    }
}
