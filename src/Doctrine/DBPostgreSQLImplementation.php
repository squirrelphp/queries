<?php

namespace Squirrel\Queries\Doctrine;

use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\LargeObject;

/**
 * DB Postgres implementation using Doctrine DBAL with custom upsert functionality
 */
class DBPostgreSQLImplementation extends DBAbstractImplementation
{
    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        $result = parent::fetch($selectQuery);

        if (isset($result)) {
            $result = $this->replaceResourceWithString($result);
        }

        return $result;
    }

    public function fetchOne($query, array $vars = []): ?array
    {
        $result = parent::fetchOne($query, $vars);

        if (isset($result)) {
            $result = $this->replaceResourceWithString($result);
        }

        return $result;
    }

    public function fetchAll($query, array $vars = []): array
    {
        $results = parent::fetchAll($query, $vars);

        $results = $this->replaceResourceWithString($results);

        return $results;
    }

    private function replaceResourceWithString(array $result): array
    {
        foreach ($result as $key => $value) {
            if (\is_array($value)) {
                $result[$key] = $this->replaceResourceWithString($value);
            } elseif (\is_resource($value)) {
                $result[$key] = \stream_get_contents($value);
            }
        }

        return $result;
    }

    public function insertOrUpdate(string $table, array $row = [], array $index = [], ?array $update = null): void
    {
        [$query, $queryValues] = $this->generateUpsertSQLAndParameters($table, $row, $index, $update);

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
