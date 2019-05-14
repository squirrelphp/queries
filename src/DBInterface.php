<?php

namespace Squirrel\Queries;

/**
 * Main interface which should be used as type hint / for dependency injection
 */
interface DBInterface
{
    /**
     * Process $func within a transaction. Any additional arguments after
     * $func are passed to $func as arguments
     *
     * @param callable $func
     * @param mixed ...$arguments
     * @return mixed
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function transaction(callable $func, ...$arguments);

    /**
     * Checks if we are currently in a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Execute a select query and return an identifier to fetch and clear the result set
     *
     * @param string|array $query SQL query as a string, with ? as variable placeholders if necessary
     *                            or an array for a structured SQL query where $vars is not used
     * @psalm-param string|array{fields?:array,field?:string,tables?:array,table?:string,where?:array,group?:array,order?:array,limit?:int,offset?:int,lock?:bool} $query
     * @param array $vars Query variables, replaces ? with these variables in order
     * @return DBSelectQueryInterface Query result to use with fetch, fetchAll, clear
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function select($query, array $vars = []): DBSelectQueryInterface;

    /**
     * Fetch a row from a previously executed select query
     *
     * @param DBSelectQueryInterface $selectQuery Identifier received from select function
     * @return array|null Table row as an associative array, or null if no (more) entries exist
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function fetch(DBSelectQueryInterface $selectQuery): ?array;

    /**
     * Clear the results of a select query
     *
     * @param DBSelectQueryInterface $selectQuery Identifier received from select function
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function clear(DBSelectQueryInterface $selectQuery): void;

    /**
     * Fetch one row with a select query and then clear the result set
     *
     * @param string|array $query SQL query as a string, with ? as variable placeholders if necessary
     *                            or an array for a structured SQL query where $vars is not used
     * @psalm-param string|array{fields?:array,field?:string,tables?:array,table?:string,where?:array,group?:array,order?:array,lock?:bool} $query
     * @param array $vars Query variables, replaces ? with these variables in order
     * @return array|null Table row as an associative array, or null if no entry was found
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function fetchOne($query, array $vars = []): ?array;

    /**
     * Fetch all rows with a select query and then clear the result set
     *
     * @param string|array $query SQL query as a string, with ? as variable placeholders if necessary
     *                            or an array for a structured SQL query where $vars is not used
     * @psalm-param string|array{fields?:array,field?:string,tables?:array,table?:string,where?:array,group?:array,order?:array,limit?:int,offset?:int,lock?:bool,flattenFields?:bool} $query
     * @param array $vars Query variables, replaces ? with these variables in order
     * @return array List of table rows, each entry as an associate array for one row
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function fetchAll($query, array $vars = []): array;

    /**
     * Insert a row into a table with the given names and values
     *
     * @param string $tableName Name of the table
     * @param array<string, mixed> $row Name and value pairs to insert into the table
     * @param string $autoIncrementIndex Index of an automatically generated value that should be returned
     * @return string|null If $autoIncrementIndex is empty, return null, otherwise return the auto increment value
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function insert(string $tableName, array $row = [], string $autoIncrementIndex = ''): ?string;

    /**
     * Insert a new entry or update existing entry (also called UPSERT: update-or-insert)
     * in one atomic operation, called MERGE query in ANSI SQL
     *
     * The actual implementation/query is different depending on the database:
     *
     * MySQL: INSERT INTO ... ON DUPLICATE KEY UPDATE ...
     * Postgres & SQLite: INSERT INTO ... ON CONFLICT(column_names) DO UPDATE ... WHERE ...
     * Others/ANSI: MERGE (see https://en.wikipedia.org/wiki/Merge_(SQL))
     *
     * @param string $tableName Name of the table
     * @param array<string, mixed> $row Row to insert, keys are column names, values are the data
     * @param string[] $indexColumns Index columns which encompass the unique index
     * @param array|null $rowUpdates Fields to update if entry already exists, default is all non-index field entries
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function insertOrUpdate(string $tableName, array $row = [], array $indexColumns = [], ?array $rowUpdates = null): void;

    /**
     * Execute an update query and return number of affected rows
     *
     * @param string $tableName Name of the table
     * @param array $changes List of changes, the SET clauses
     * @param array $where Restrictions on which rows to update
     * @return int Number of affected rows
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function update(string $tableName, array $changes, array $where = []): int;

    /**
     * Execute a delete query and return number of affected rows
     *
     * @param string $tableName Name of the table
     * @param array $where Restrictions for the row deletion
     * @return int Number of affected rows
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function delete(string $tableName, array $where = []): int;

    /**
     * Execute an insert, update or delete query and return number of affected rows
     *
     * @param string $query SQL query as a string, with ? as variable placeholders if necessary
     * @param array $vars Query variables, replaces ? with these variables in order
     * @return int Number of affected rows
     *
     * @throws DBException Common minimal exception thrown if anything goes wrong
     */
    public function change(string $query, array $vars = []): int;

    /**
     * Quotes an identifier, like a table name or column name, so there is no risk
     * of overlap with a reserved keyword
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Quotes all identifiers in an expression (for example a query). Identifiers are found
     * by being surrounded by colons, for example: "WHERE :user_id: = ?" - user_id would be
     * quoted in that example
     *
     * @param string $expression
     * @return string
     */
    public function quoteExpression(string $expression): string;

    /**
     * Get connection object for low-level access when there is no other way of solving something - should
     * rarely be necessary
     *
     * @return object
     */
    public function getConnection(): object;
}
