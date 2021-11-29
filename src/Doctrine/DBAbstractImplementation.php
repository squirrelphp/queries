<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Connection;
use Squirrel\Debug\Debug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;
use Squirrel\Queries\LargeObject;

/**
 * DB implementation using Doctrine DBAL without the upsert functionality,
 * because upsert is implemented differently on different database systems
 *
 * No error handling on this layer at all - this needs another layer like
 * the DBErrorHandler class to handle transaction and connection failures
 */
abstract class DBAbstractImplementation implements DBRawInterface
{
    protected DBConvertStructuredQueryToSQL $structuredQueryConverter;

    /**
     * Whether there is currently a transaction active, to avoid nested
     * transactions in our "transaction" function
     */
    private bool $inTransaction = false;

    public function __construct(
        private Connection $connection,
    ) {
        $this->structuredQueryConverter = new DBConvertStructuredQueryToSQL(
            [$this, 'quoteIdentifier'],
            [$this, 'quoteExpression'],
        );
    }

    public function transaction(callable $func, mixed ...$arguments): mixed
    {
        // If we are already in a transaction we just run the function
        if ($this->inTransaction === true) {
            return $func(...$arguments);
        }

        // Record in class as "we are in a transaction"
        $this->inTransaction = true;

        // Start transaction
        $this->connection->beginTransaction();

        // Run the function and commit transaction
        $result = $func(...$arguments);
        $this->connection->commit();

        // Go back to "we are not in a transaction anymore"
        $this->inTransaction = false;

        // Return result from the function
        return $result;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function select(string|array $query, array $vars = []): DBSelectQueryInterface
    {
        // Convert structured query into a string query with variables
        if (\is_array($query)) {
            [$query, $vars] = $this->convertStructuredSelectToQuery($query);
        }

        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $statementResult = $statement->executeQuery($vars);

        // Return select query object with PDO statement
        return new DBSelectQuery($statementResult);
    }

    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        // Make sure we have a valid DBSelectQuery object
        if (!($selectQuery instanceof DBSelectQuery)) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'Invalid select query class provided',
            );
        }

        // Get the result - can be an array of the entry, or false if it is empty
        $result = $selectQuery->getStatement()->fetchAssociative();

        // Return one result as an array
        return ($result === false ? null : $result);
    }

    public function clear(DBSelectQueryInterface $selectQuery): void
    {
        // Make sure we have a valid DBSelectQuery object
        if (!($selectQuery instanceof DBSelectQuery)) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'Invalid select query class provided',
            );
        }

        // Close the result set
        $selectQuery->getStatement()->free();
    }

    public function fetchOne(string|array $query, array $vars = []): ?array
    {
        // Use our internal functions to not repeat ourselves
        $selectQuery = $this->select($query, $vars);
        $result = $this->fetch($selectQuery);
        $this->clear($selectQuery);

        // Return query result
        return $result;
    }

    public function fetchAll(string|array $query, array $vars = []): array
    {
        // Convert structured query into a string query with variables
        if (\is_array($query)) {
            [$query, $vars] = $this->convertStructuredSelectToQuery($query);
        }

        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $statementResult = $statement->executeQuery($vars);

        // Get result and close result set
        $result = $statementResult->fetchAllAssociative();
        $statementResult->free();

        // Return query result
        return $result;
    }

    public function fetchAllAndFlatten(string|array $query, array $vars = []): array
    {
        return $this->flattenResults($this->fetchAll($query, $vars));
    }

    public function insert(string $table, array $row = [], string $autoIncrement = ''): ?string
    {
        // No table name specified
        if (\strlen($table) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for insert',
            );
        }

        // Make table name safe by quoting it
        $tableNameQuoted = $this->quoteIdentifier($table);

        // Divvy up the field names, values and placeholders
        $columnNames = \array_keys($row);
        $columnValues = \array_values($row);
        $placeholders = \array_fill(0, \count($row), '?');

        // Generate the insert query
        $query = 'INSERT INTO ' . $tableNameQuoted . ' ' .
            '(' . (\count($row) > 0 ? \implode(',', \array_map([$this, 'quoteIdentifier'], $columnNames)) : '') . ') ' .
            'VALUES (' . (\count($row) > 0 ? \implode(',', $placeholders) : '') . ')';

        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $paramCounter = 1;
        foreach ($columnValues as $columnValue) {
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

        // No autoincrement index - no insert ID return value needed
        if (\strlen($autoIncrement) === 0) {
            return null;
        }

        // Return autoincrement ID
        return \strval($this->connection->lastInsertId($table . '_' . $autoIncrement . '_seq'));
    }

    public function update(string $table, array $changes, array $where = []): int
    {
        // Changes in update query need to be defined
        if (\count($changes) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No "changes" definition',
            );
        }

        // Generate changes SQL (SET part)
        [$changeSQL, $queryValues] = $this->structuredQueryConverter->buildChanges($changes, []);

        // Build the WHERE part of the query
        [$whereSQL, $queryValues] = $this->structuredQueryConverter->buildWhere($where, $queryValues);

        // Generate query
        $sql = 'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . $changeSQL .
            (\strlen($whereSQL) > 1 ? ' WHERE ' . $whereSQL : '');

        // Call the change function to avoid duplication
        return $this->change($sql, $queryValues);
    }

    public function delete(string $table, array $where = []): int
    {
        // No table name specified
        if (\strlen($table) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for delete',
            );
        }

        // Build the WHERE part of the query
        [$whereSQL, $queryValues] = $this->structuredQueryConverter->buildWhere($where, []);

        // Compile the DELETE query
        $query = 'DELETE FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . $whereSQL;

        // Use our existing update function so there is no duplication
        return $this->change($query, $queryValues);
    }

    public function change(string $query, array $vars = []): int
    {
        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $paramCounter = 1;
        foreach ($vars as $columnValue) {
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

        // Get affected rows
        $result = $statementResult->rowCount();

        // Close query
        $statementResult->free();

        // Return affected rows
        return $result;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }

    public function quoteExpression(string $expression): string
    {
        return \preg_replace_callback('/[:]([^:]+)[:]/si', function (array $matches): string {
            return $this->quoteIdentifier($matches[1]);
        }, $expression) ?? $expression;
    }

    private function flattenResults(array $results): array
    {
        // New flattened array
        $list = [];

        // Go through results and reduce the array to just a list of column values
        foreach ($results as $entryKey => $entryArray) {
            foreach ($entryArray as $fieldName => $fieldValue) {
                $list[] = $fieldValue;
            }
        }

        // Returned flattened results
        return $list;
    }

    protected function convertStructuredSelectToQuery(array $select): array
    {
        // Make sure all options are correctly defined
        $select = $this->structuredQueryConverter->verifyAndProcessOptions([
            'fields' => [],
            'tables' => [],
            'where' => [],
            'group' => [],
            'order' => [],
            'limit' => 0,
            'offset' => 0,
            'lock' => false,
        ], $select);

        // Generate field select SQL (between SELECT and FROM)
        $fieldSelectionSQL = $this->structuredQueryConverter->buildFieldSelection($select['fields'] ?? []);

        // Build table joining SQL (between FROM and WHERE)
        [$tableJoinsSQL, $queryValues] = $this->structuredQueryConverter->buildTableJoins($select['tables']);

        // Build the WHERE part of the query
        [$whereSQL, $queryValues] = $this->structuredQueryConverter->buildWhere($select['where'], $queryValues);

        // Build the GROUP BY part of the query if specified
        if (isset($select['group'])) {
            $groupSQL = $this->structuredQueryConverter->buildGroupBy($select['group']);
        }

        // Build the ORDER BY part of the query if specified
        if (isset($select['order'])) {
            $orderSQL = $this->structuredQueryConverter->buildOrderBy($select['order']);
        }

        // Generate SELECT query
        $sql = 'SELECT ' . $fieldSelectionSQL . ' FROM ' . $tableJoinsSQL .
            (\strlen($whereSQL) > 1 ? ' WHERE ' . $whereSQL : '') .
            (isset($groupSQL) && \strlen($groupSQL) > 0 ? ' GROUP BY ' . $groupSQL : '') .
            (isset($orderSQL) && \strlen($orderSQL) > 0 ? ' ORDER BY ' . $orderSQL : '');

        // Either "limit" or "offset" options were specified
        if (
            (isset($select['limit']) && $select['limit'] > 0)
            || (isset($select['offset']) && $select['offset'] > 0)
        ) {
            $sql = $this->connection->getDatabasePlatform()->modifyLimitQuery(
                $sql,
                $select['limit'] ?? null,
                $select['offset'] ?? null,
            );
        }

        // Lock the result set
        if ($select['lock'] === true) {
            $sql .= ' FOR UPDATE';
        }

        return [$sql, $queryValues];
    }

    /**
     * Emulate an UPSERT with an UPDATE and INSERT query wrapped in a transaction
     *
     * @param string $tableName Name of the table
     * @param array $row Row to insert, keys are column names, values are the data
     * @param string[] $indexColumns Index columns which encompass the unique index
     * @param array|null $rowUpdates Fields to update if entry already exists
     */
    public function insertOrUpdateEmulation(
        string $tableName,
        array $row = [],
        array $indexColumns = [],
        ?array $rowUpdates = null,
    ): void {
        $this->validateMandatoryUpsertParameters($tableName, $row, $indexColumns);

        $rowUpdates = $this->prepareUpsertRowUpdates($rowUpdates, $row, $indexColumns);

        // Do all queries in a transaction to correctly emulate the UPSERT
        $this->transaction(function (
            string $tableName,
            array $row,
            array $indexColumns,
            array $rowUpdates,
        ) {
            // Contains all WHERE restrictions for the UPDATE query
            $whereForUpdate = [];

            // Assign all row values of index fields to the WHERE restrictions
            foreach ($indexColumns as $indexColumn) {
                $whereForUpdate[$indexColumn] = $row[$indexColumn];
            }

            // No update, so just make a dummy update setting the unique index fields
            if (\count($rowUpdates) === 0) {
                foreach ($indexColumns as $fieldName) {
                    $rowUpdates[] = ':' . $fieldName . ':=:' . $fieldName . ':';
                }
            }

            // Execute UPDATE query and get affected rows
            $rowsAffected = $this->update($tableName, $rowUpdates, $whereForUpdate);

            // Rows were affected, meaning the UPDATE worked / an entry already exists
            if ($rowsAffected > 0) {
                return;
            }

            // Because the UPDATE did not work, we do a regular insert
            $this->insert($tableName, $row);
        }, $tableName, $row, $indexColumns, $rowUpdates);
    }

    protected function validateMandatoryUpsertParameters(
        string $tableName,
        array $row,
        array $indexColumns,
    ): void {
        // No table name specified
        if (\strlen($tableName) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for upsert',
            );
        }

        // No insert row specified
        if (\count($row) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No insert data specified for upsert for table "' . $tableName . '"',
            );
        }

        // No index specified
        if (\count($indexColumns) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No index specified for upsert for table "' . $tableName . '"',
            );
        }

        // Make sure the index columns also exist in the insert row
        foreach ($indexColumns as $fieldName) {
            if (!isset($row[$fieldName])) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    DBInterface::class,
                    'Index values are missing in insert row values',
                );
            }
        }
    }

    protected function prepareUpsertRowUpdates(
        ?array $rowUpdates,
        array $rowInsert,
        array $indexColumns,
    ): array {
        // No update fields defined, so we assume the table is changed the same way
        // as with the insert
        if ($rowUpdates === null) {
            // Copy over insert fields and values
            $rowUpdates = $rowInsert;

            // Remove index fields for update
            foreach ($indexColumns as $fieldName) {
                unset($rowUpdates[$fieldName]);
            }
        }

        return $rowUpdates;
    }

    public function setTransaction(bool $inTransaction): void
    {
        $this->inTransaction = $inTransaction;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function setLowerLayer(DBRawInterface $lowerLayer): void
    {
        throw new \LogicException('Lower DBRawInterface layers cannot be set in ' . __METHOD__ .
            ' because we are already at the lowest level of implementation');
    }
}
