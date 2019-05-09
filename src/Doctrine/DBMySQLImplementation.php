<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Connection;
use Squirrel\Queries\LargeObject;

/**
 * DB MySQL implementation using Doctrine DBAL with custom upsert functionality
 */
class DBMySQLImplementation extends DBAbstractImplementation
{
    /**
     * @inheritDoc
     */
    public function insertOrUpdate(string $tableName, array $row = [], array $indexColumns = [], ?array $rowUpdates = null): void
    {
        $this->validateMandatoryUpsertParameters($tableName, $row, $indexColumns);

        $rowUpdates = $this->prepareUpsertRowUpdates($rowUpdates, $row, $indexColumns);

        // Divvy up the field names, values and placeholders for the INSERT part
        $columnsForInsert = array_map([$this, 'quoteIdentifier'], array_keys($row));
        $placeholdersForInsert = array_fill(0, count($row), '?');
        $queryValues = array_values($row);

        // No update, so just make a dummy update setting the unique index fields
        if (count($rowUpdates) === 0) {
            foreach ($indexColumns as $fieldName) {
                $rowUpdates[] = ':' . $fieldName . ':=:' . $fieldName . ':';
            }
        }

        // Generate update part of the query
        [$updatePart, $queryValues] = $this->structuredQueryConverter->buildChanges($rowUpdates, $queryValues);

        // Generate the insert query
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName) .
            ' (' . (count($columnsForInsert) > 0 ? implode(',', $columnsForInsert) : '') . ') ' .
            'VALUES (' . (count($columnsForInsert) > 0 ? implode(',', $placeholdersForInsert) : '') . ') ' .
            'ON DUPLICATE KEY UPDATE ' . $updatePart;

        /**
         * @var Connection $connection
         */
        $connection = $this->getConnection();
        $statement = $connection->prepare($query);

        $paramCounter = 1;
        foreach ($queryValues as $columnValue) {
            if (\is_bool($columnValue)) {
                $columnValue = \intval($columnValue);
            }

            $statement->bindValue(
                $paramCounter++,
                ($columnValue instanceof LargeObject) ? $columnValue->getStream() : $columnValue,
                ($columnValue instanceof LargeObject) ? \PDO::PARAM_LOB : \PDO::PARAM_STR
            );
        }

        $statement->execute();
        $statement->closeCursor();
    }
}
