<?php

namespace Squirrel\Queries\Doctrine;

use Squirrel\Queries\LargeObject;

/**
 * DB MySQL implementation using Doctrine DBAL with custom upsert functionality
 */
class DBMySQLImplementation extends DBAbstractImplementation
{
    public function insertOrUpdate(string $table, array $row = [], array $index = [], ?array $update = null): void
    {
        $this->validateMandatoryUpsertParameters($table, $row, $index);

        $update = $this->prepareUpsertRowUpdates($update, $row, $index);

        // Divvy up the field names, values and placeholders for the INSERT part
        $columnsForInsert = \array_map([$this, 'quoteIdentifier'], \array_keys($row));
        $placeholdersForInsert = \array_fill(0, \count($row), '?');
        $queryValues = \array_values($row);

        // No update, so just make a dummy update setting the unique index fields
        if (\count($update) === 0) {
            foreach ($index as $fieldName) {
                $update[] = ':' . $fieldName . ':=:' . $fieldName . ':';
            }
        }

        // Generate update part of the query
        [$updatePart, $queryValues] = $this->structuredQueryConverter->buildChanges($update, $queryValues);

        // Generate the insert query
        $query = 'INSERT INTO ' . $this->quoteIdentifier($table) .
            ' (' . (\count($columnsForInsert) > 0 ? \implode(',', $columnsForInsert) : '') . ') ' .
            'VALUES (' . (\count($columnsForInsert) > 0 ? \implode(',', $placeholdersForInsert) : '') . ') ' .
            'ON DUPLICATE KEY UPDATE ' . $updatePart;

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
                ($columnValue instanceof LargeObject) ? \PDO::PARAM_LOB : \PDO::PARAM_STR,
            );
        }

        $statementResult = $statement->executeQuery();
        $statementResult->free();
    }
}
