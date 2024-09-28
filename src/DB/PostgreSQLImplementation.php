<?php

namespace Squirrel\Queries\DB;

/**
 * DB Postgres implementation with custom upsert functionality
 */
class PostgreSQLImplementation extends AbstractImplementation
{
    public function insertOrUpdate(string $table, array $row = [], array $index = [], ?array $update = null): void
    {
        [$query, $queryValues] = $this->generateUpsertSQLAndParameters($table, $row, $index, $update);

        $connection = $this->getConnection();
        $statement = $connection->prepareQuery($query);
        $connection->executeQuery($statement, $queryValues);
        $connection->freeResults($statement);
    }

    protected function generateUpsertSQLAndParameters(
        string $tableName,
        array $row = [],
        array $indexColumns = [],
        ?array $rowUpdates = null,
    ): array {
        $this->validateMandatoryUpsertParameters($tableName, $row, $indexColumns);

        $rowUpdates = $this->prepareUpsertRowUpdates($rowUpdates, $row, $indexColumns);

        // Divvy up the field names, values and placeholders for the INSERT part
        $columnsForInsert = \array_map([$this, 'quoteIdentifier'], \array_keys($row));
        $placeholdersForInsert = \array_fill(0, \count($row), '?');
        $queryValues = \array_values($row);

        // Generate the insert query
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName) .
            ' (' . (\count($columnsForInsert) > 0 ? \implode(',', $columnsForInsert) : '') . ') ' .
            'VALUES (' . (\count($columnsForInsert) > 0 ? \implode(',', $placeholdersForInsert) : '') . ') ' .
            'ON CONFLICT (' . \implode(',', \array_map([$this, 'quoteIdentifier'], $indexColumns)) . ') ';

        if (\count($rowUpdates) === 0) { // No updates, so insert or do nothing
            $query .= 'DO NOTHING';
        } else { // Generate update part of the query
            [$updatePart, $queryValues] = $this->structuredQueryConverter->buildChanges($rowUpdates, $queryValues);
            $query .= 'DO UPDATE SET ' . $updatePart;
        }

        return [$query, $queryValues];
    }
}
