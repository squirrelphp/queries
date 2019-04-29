<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Squirrel\Queries\DBDebug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBRawInterface;
use Squirrel\Queries\DBSelectQueryInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * DB implementation using Doctrine DBAL without the upsert functionality,
 * because upsert is implemented differently on different database systems
 *
 * No error handling on this layer at all - this needs another layer like
 * the DBErrorHandler class to handle transaction and connection failures
 */
abstract class DBAbstractImplementation implements DBRawInterface
{
    /**
     * Doctrine DBAL connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * @var DBConvertStructuredQueryToSQL
     */
    protected $structuredQueryConverter;

    /**
     * Whether there is currently a transaction active, to avoid nested
     * transactions in our "transaction" function
     *
     * @var bool
     */
    private $inTransaction = false;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->structuredQueryConverter = new DBConvertStructuredQueryToSQL([$this, 'quoteIdentifier']);
    }

    /**
     * @inheritDoc
     */
    public function transaction(callable $func, ...$arguments)
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

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * @inheritDoc
     */
    public function select($query, array $vars = []): DBSelectQueryInterface
    {
        // Convert structured query into a string query with variables
        if (is_array($query)) {
            [$query, $vars] = $this->convertStructuredSelectToQuery($query);
        }

        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $statement->execute($vars);

        // Return select query object with PDO statement
        return new DBSelectQuery($statement);
    }

    /**
     * @inheritDoc
     */
    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        // Make sure we have a valid DBSelectQuery object
        if (!($selectQuery instanceof DBSelectQuery)) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'Invalid select query class provided'
            );
        }

        // Get the result - can be an array of the entry, or false if it is empty
        $result = $selectQuery->getStatement()->fetch(FetchMode::ASSOCIATIVE);

        // Return one result as an array
        return ($result === false ? null : $result);
    }

    /**
     * @inheritDoc
     */
    public function clear(DBSelectQueryInterface $selectQuery): void
    {
        // Make sure we have a valid DBSelectQuery object
        if (!($selectQuery instanceof DBSelectQuery)) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'Invalid select query class provided'
            );
        }

        // Close the result set
        $selectQuery->getStatement()->closeCursor();
    }

    /**
     * @inheritDoc
     */
    public function fetchOne($query, array $vars = []): ?array
    {
        // Use our internal functions to not repeat ourselves
        $selectQuery = $this->select($query, $vars);
        $result = $this->fetch($selectQuery);
        $this->clear($selectQuery);

        // Return query result
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function fetchAll($query, array $vars = []): array
    {
        // Convert structured query into a string query with variables
        if (is_array($query)) {
            $flattenFields = $this->getFlattenFieldsOption($query['flattenFields'] ?? false);
            if (isset($query['flattenFields'])) {
                unset($query['flattenFields']);
            }
            [$query, $vars] = $this->convertStructuredSelectToQuery($query);
        }

        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $statement->execute($vars);

        // Get result and close result set
        $result = $statement->fetchAll(FetchMode::ASSOCIATIVE);
        $statement->closeCursor();

        // We flatten the fields if requested
        if (isset($flattenFields) && $flattenFields === true && count($result) > 0) {
            return $this->flattenResults($result);
        }

        // Return query result
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function insert(string $tableName, array $row = []): int
    {
        // No table name specified
        if (strlen($tableName) === 0) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for insert'
            );
        }

        // Make table name safe by quoting it
        $tableName = $this->quoteIdentifier($tableName);

        // Divvy up the field names, values and placeholders
        $columnNames = array_keys($row);
        $columnValues = array_values($row);
        $placeholders = array_fill(0, count($row), '?');

        // Generate the insert query
        $query = 'INSERT INTO ' . $tableName .
            ' (' . (count($row) > 0 ? implode(',', array_map([$this, 'quoteIdentifier'], $columnNames)) : '') . ') ' .
            'VALUES (' . (count($row) > 0 ? implode(',', $placeholders) : '') . ')';

        // Return number of affected rows
        return $this->change($query, $columnValues);
    }

    /**
     * @inheritDoc
     */
    public function update(array $query): int
    {
        // Convert the structure into a query string and query variables
        [$queryAsString, $vars] = $this->convertStructuredUpdateToQuery($query);

        // Call the change function to avoid duplication
        return $this->change($queryAsString, $vars);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $tableName, array $where = []): int
    {
        // No table name specified
        if (strlen($tableName) === 0) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'No table name specified for delete'
            );
        }

        // Build the WHERE part of the query
        [$whereSQL, $queryValues] = $this->structuredQueryConverter->buildWhere($where, []);

        // Compile the DELETE query
        $query = 'DELETE FROM ' . $this->quoteIdentifier($tableName) . ' WHERE ' . $whereSQL;

        // Use our existing update function so there is no duplication
        return $this->change($query, $queryValues);
    }

    /**
     * @inheritDoc
     */
    public function change(string $query, array $vars = []): int
    {
        // Prepare and execute query
        $statement = $this->connection->prepare($query);
        $statement->execute($vars);

        // Get affected rows
        $result = $statement->rowCount();

        // Close query
        $statement->closeCursor();

        // Return affected rows
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId($name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * @inheritDoc
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }

    private function getFlattenFieldsOption($flattenFields)
    {
        if (!\is_bool($flattenFields) && $flattenFields !== 1 && $flattenFields !== 0) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                DBInterface::class,
                'flattenFields set to a non-boolean value: ' . DBDebug::sanitizeData($flattenFields)
            );
        }
        return \boolval($flattenFields);
    }

    private function flattenResults(array $results)
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

    private function convertStructuredSelectToQuery(array $select): array
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
            (strlen($whereSQL) > 1 ? ' WHERE ' . $whereSQL : '') .
            (isset($groupSQL) && strlen($groupSQL) > 0 ? ' GROUP BY ' . $groupSQL : '') .
            (isset($orderSQL) && strlen($orderSQL) > 0 ? ' ORDER BY ' . $orderSQL : '');

        // Add limit for results
        if ((isset($select['limit']) && $select['limit'] > 0) || (isset($select['offset']) && $select['offset'] > 0)) {
            $sql = $this->connection->getDatabasePlatform()->modifyLimitQuery(
                $sql,
                $select['limit'] ?? null,
                $select['offset'] ?? null
            );
        }

        // Lock the result set
        if ($select['lock'] === true) {
            $sql .= ' FOR UPDATE';
        }

        return [$sql, $queryValues];
    }

    private function convertStructuredUpdateToQuery(array $update): array
    {
        // Make sure all options are correctly defined
        $update = $this->structuredQueryConverter->verifyAndProcessOptions([
            'changes' => [],
            'tables' => [],
            'where' => [],
            'order' => [],
            'limit' => 0,
        ], $update);

        // Build table joining SQL (between UPDATE and SET)
        [$tableJoinsSQL, $queryValues] = $this->structuredQueryConverter->buildTableJoins($update['tables']);

        // Generate changes SQL (SET part)
        [$changeSQL, $queryValues] = $this->structuredQueryConverter->buildChanges($update['changes'], $queryValues);

        // Build the WHERE part of the query
        [$whereSQL, $queryValues] = $this->structuredQueryConverter->buildWhere($update['where'], $queryValues);

        // Build the ORDER BY part of the query if specified
        if (isset($update['order'])) {
            $orderSQL = $this->structuredQueryConverter->buildOrderBy($update['order']);
        }

        // Generate SELECT query
        $sql = 'UPDATE ' . $tableJoinsSQL . ' SET ' . $changeSQL .
            (strlen($whereSQL) > 1 ? ' WHERE ' . $whereSQL : '') .
            (isset($orderSQL) && strlen($orderSQL) > 0 ? ' ORDER BY ' . $orderSQL : '');

        // Add limit for results
        if (isset($update['limit']) && $update['limit'] > 0) {
            $sql = $this->connection->getDatabasePlatform()->modifyLimitQuery($sql, $update['limit']);
        }

        return [$sql, $queryValues];
    }

    /**
     * @inheritDoc
     */
    public function setTransaction(bool $inTransaction): void
    {
        $this->inTransaction = $inTransaction;
    }

    /**
     * @inheritDoc
     */
    public function getConnection(): object
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function setLowerLayer($lowerLayer): void
    {
        throw new \LogicException('Lower DBRawInterface layers cannot be set in ' . __METHOD__ .
            ' because we are already at the lowest level of implementation');
    }
}
