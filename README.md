Squirrel Queries Component
==========================

[![Build Status](https://img.shields.io/travis/com/squirrelphp/queries.svg)](https://travis-ci.com/squirrelphp/queries) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE) [![Test Coverage](https://api.codeclimate.com/v1/badges/4f12e6ef097b4202bf65/test_coverage)](https://codeclimate.com/github/squirrelphp/queries/test_coverage) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/queries.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/queries)

Provides a slimmed down concise interface (DBInterface) for database queries and transactions. The limited interface is aimed to avoid confusion/misuse and encourage fail-safe usage.

Doctrine is used as the underlying connection (and abstraction), what we add are an upsert (MERGE) functionality, structured queries which are easier to write and read (and make errors less likely), and the possibility to layer database concerns (like actual implementation, connections retries, performance measurements, logging, etc.).

By default this library provides two layers, one dealing with Doctrine DBAL (passing the queries, processing and returning the results) and one dealing with errors (DBErrorHandler). DBErrorHandler catches deadlocks and connection problems and tries to repeat the query or transaction, and it unifies the exceptions coming from DBAL so the originating call to DBInterface is provided and the error can easily be found.

Installation
------------

    composer require squirrelphp/queries

Usage
-----

Use Squirrel\Queries\DBInterface as a type hint in your services. The interface options are based upon Doctrine and PDO with slight tweaks. If you know Doctrine or PDO you should be able to use this library easily.

In addition, this library supports structured SELECT and UPDATE queries which break down the queries into its parts and take care of your field names and parameters automatically.

For a solution which integrates easily with the Symfony framework, check out [squirrelphp/queries-bundle](https://github.com/squirrelphp/queries-bundle), and for entity and repository support check out [squirrelphp/entities](https://github.com/squirrelphp/entities) and [squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle).

If you want to assemble a DBInterface object yourself, something like the following code can be a start:

    use Doctrine\DBAL\DriverManager;
    use Squirrel\Queries\DBInterface;
    use Squirrel\Queries\Doctrine\DBErrorHandler;
    use Squirrel\Queries\Doctrine\DBMySQLImplementation;
    
    // Create a doctrine connection
    $dbalConnection = DriverManager::getConnection([
        'url' => 'mysql://user:secret@localhost/mydb'
    ]);
    
    // Create a MySQL implementation layer
    $implementationLayer = new DBMySQLImplementation($dbalConnection);
    
    // Create an error handler layer
    $errorLayer = new DBErrorHandler();
    
    // Set implementation layer beneath the error layer
    $errorLayer->setLowerLayer($implementationLayer);
    
    // $errorLayer is now useable and can be injected
    // anywhere you need it. Typehint it with 
    // \Squirrel\Queries\DBInterface
    
    $fetchEntry = function(DBInterface $db) {
      return $db->fetchOne('SELECT * FROM table');
    };
    
    $fetchEntry($errorLayer);
    
    // If you want to add more layers, you can create a
    // class which implements DBRawInterface and includes
    // the DBPassToLowerLayer trait and then just overwrite
    // the functions you want to change, and then connect
    // it to the other layers through setLowerLayer
    
    // It is also a good idea to catch \Squirrel\Queries\DBException
    // in your application in case of a DB error so it
    // can be handled gracefully

### SELECT queries

You can write your own SELECT queries with given parameters using the `select` function, then getting results with the `fetch` function and clearing the results with the `clear` function:

```php
$selectStatement = $db->select('SELECT fieldname FROM tablename WHERE restriction = ? AND restriction2 = ?', [5, 8]);
$firstRow = $db->fetch($selectStatement);
$db->clear($selectStatement);
```

All ? are replaced by the array values in the second argument (those are the query parameters), if you have none you can omit the second argument:

```php
$selectStatement = $db->select('SELECT fieldname FROM tablename WHERE restriction = 5 AND restriction2 = 8');
```

It is recommended to use query parameters for any query data, even if it is fixed, because it is secure no matter where the data came from (like user input) and the charset or type does not matter (string, integer, boolean).

`fetchOne` and `fetchAll` can be used instead of the `select` function to directly retrieve exactly one row (`fetchOne`) or all rows (`fetchAll`) for a SELECT query, for example:

```php
$firstRow = $db->fetchOne('SELECT fieldname FROM tablename WHERE restriction = ? AND restriction2 = ?', [5, 8]);
```
```php
$allRows = $db->fetchAll('SELECT fieldname FROM tablename WHERE restriction = ? AND restriction2 = ?', [5, 8]);
```

### Structured SELECT queries

Instead of writing raw SQL you can use a structured query:

```php
$selectStatement = $db->select([
  'field' => 'fieldname',
  'table' => 'tablename',
  'where' => [
    'restriction' => 5,
    'restriction2' => 8,
  ],
]);
$firstRow = $db->fetch($selectStatement);
$db->clear($selectStatement);
```

In addition to being easier to write or process it also escapes field and table names, so the following string query is identical to the structured query above:

```php
$selectStatement = $db->select('SELECT ´fieldname´ FROM ´tablename´ WHERE ´restriction´=? AND ´restriction2´=?', [5, 8]);
```

How field names and tables are quoted depends on Doctrine and its abstractions, so the escape character can differ according to the database engine. The above shows how MySQL would be escaped.

Structured queries can replace almost all string select queries, even with multiple tables - this is a more complex example showing its options:

```php
$selectStatement = $db->select([
 'fields' => [
   'fufumama',
   'b.lalala',
   'result' => 'a.setting_value',
   'result2' => ':a.setting_value:+:b.blabla_value:',
 ],
 'tables' => [
   'blobs.aa_sexy a',
   ':blobs.aa_blubli: :b: LEFT JOIN :blobs.aa_blubla: :c: ON (:c.field: = :b.field5: AND :b.sexy: = ?)' => 5,
 ],
 'where' => [
   ':a.field: = :b.field:',
   'setting_id' => 'orders_xml_override',
   'boring_field_name' => [5,3,8,13],
   ':setting_value: = ? OR :setting_value2: = ?' => ['one','two'],
 ],
 'group' => [
   'a.field',
 ],
 'order' => [
   'a.field' => 'DESC',
 ],
 'limit' => 10,
 'offset' => 5,
 'lock' => true,
]);
$firstRow = $db->fetch($selectStatement);
$db->clear($selectStatement);
```

This would be aquivalent to this string SELECT query (when using MySQL):

```php
$selectStatement = $db->select('SELECT `fufumama`,`b`.`lalala`,`a`.`setting_value` AS "result",(`a`.`setting_value`+`b`.`blabla_value`) AS "result2" FROM `blobs`.`aa_sexy` `a`,`blobs`.`aa_blubli` `b` LEFT JOIN `blobs`.`aa_blubla` `c` ON (`c`.`field` = `b`.`field5` AND `b`.`sexy` = ?) WHERE (`a`.`field` = `b`.`field`) AND `setting_id`=? AND `boring_field_name` IN (?,?,?,?) AND (`setting_value` = ? OR `setting_value2` = ?) GROUP BY `a`.`field` ORDER BY `a`.`field` DESC LIMIT 10 OFFSET 5 FOR UPDATE', [5,'orders_xml_override',5,3,8,13,'one','two']);
```

Important parts of how the conversion works:

- If an expression contains something like :fieldname: it is assumed that it is a field or table name which will then be escaped. For simple WHERE restrictions or fields definitions field names are escaped automatically.
- You can use "field" if there is just one field, or "fields" for multiple fields. The same with "table" and "tables".
- If you set "lock" to true "FOR UPDATE" is added to the query, so the results are locked within the current transaction.
- The arguments are checked as much as possible and if an option/expression is not valid, a DBInvalidOptionException is thrown. This does not include SQL errors, as the SQL components knows nothing of the allowed field names, table names or what constitutes a valid SQL expression.

### Change queries

Custom INSERT, UPDATE and DELETE queries (or other custom queries) can be executed with the `change` function, implying that this query changes something in contrast to a SELECT query:

```php
$rowsAffected = $dbInterface->change('UPDATE users SET first_name = ?, last_name = ?, login_number = login_number + 1 WHERE user_id = ?', [
  'Liam', // first_name
  'Henry', // last_name
  5, // user_id
]);
```

```php
$rowsAffected = $dbInterface->change('DELETE FROM users WHERE user_id = ? AND first_name = ?', [
  5, // user_id
  'Liam', // first_name
]);
```

```php
$rowsAffected = $dbInterface->change('INSERT INTO users (user_id, first_name) SELECT user_id, first_name FROM users_backup');
```

### Structured UPDATE queries

TODO

### INSERT

TODO

#### Example

```php
// Does a prepared statement internally separating query and content,
// also quotes the table name and all the identifier names
$dbInterface->insert('yourdatabase.yourtable', [
  'tableId' => 5,
  'column1' => 'Henry',
  'other_column' => 'Liam',
]);

// Get the last insert ID if you have an autoincrement primary index:
$newInsertedId = $dbInterface->lastInsertId();
```

### UPSERT / MERGE

TODO

#### Examples

An example without using `$rowUpdates`, which means all `$row` entries are used for the update except for `$indexColumns`:

```php
// Does a prepared statement internally separating query and content,
// also quotes the table name and all the field names
$dbInterface->upsert('yourdatabase.yourtable', [
  'tableId' => 5,
  'column1' => 'Henry',
  'other_column' => 'Liam',
], [
  'tableId',
]);
```

The first two arguments are identical to the normal insert function, the third defines the index columns which is your unique or primary key in the database. For MySQL this is converted into this prepared statement:

```sql
INSERT INTO `yourdatabase`.`yourtable` (`tableId`,`column1`,`other_column`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `column1`=?,`other_column`=?
```

If you want to customize the UPDATE part you use `$rowUpdates`:

```php
// Does a prepared statement internally separating query and content,
// also quotes the table name and all the field names
$dbInterface->upsert('yourdatabase.yourtable', [
  'tableId' => 5,
  'column1' => 'Henry',
  'other_column' => 'Liam',
  'access_number' => 1,
], [
  'tableId',
], [
  'column1' => 'Henry',
  'access_number = access_number + 1',
]);
```

This is converted into this prepared statement for MySQL:

```sql
INSERT INTO `yourdatabase`.`yourtable` (`tableId`,`column1`,`other_column`,`access_number`) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE `column1`=?,access_number = access_number + 1
```

As you can see, we decided to not make the UPDATE identical to the INSERT - we only change column1 and we also inserted a custom SQL part with `access_number = access_number + 1`. Whenever an entry in `$rowUpdates` has no named key the value is used as-is in the SQL query.

### UPDATE / DELETE / CUSTOM INSERT

TODO

### TRANSACTION

Just pass a callable/function to the `transaction` method and DBInterface will take care of the commit/rollback parts automatically.

#### Examples

```php
$dbInterface->transaction(function(){
  // Do queries in here as much as you want, it will all be one transaction
  // and committed as soon as this function ends
});
```

An actual example might be:

```php
$dbInterface->transaction(function() use ($dbInterface) {
  $dbInterface->insert('myTable', [
    'tableName' => 'Henry',
  ]);
  
  $tableId = $dbInterface->lastInsertId();
  
  $dbInterface->update('UPDATE otherTable SET tableId = ? WHERE tableName = ?', [$tableId, 'Henry']);
});
```

If you call transaction within a transaction function, that function will just become part of the "outer transaction" and will fail or succeed with it:

```php
$dbInterface->transaction(function() use ($dbInterface) {
  $dbInterface->insert('myTable', [
    'tableId' => 5,
    'tableName' => 'Henry',
  ]);
  
  $tableId = $dbInterface->lastInsertId();
  
  // This still does exactly the same as in the previous example, because the
  // function will be executed without a "new" transaction being started,
  // the existing one just continues
  $dbInterface->transaction(function() use ($dbInterface, $tableId)) {
    // If this fails, then the error handler will attempt to repeat the outermost
    // transaction function, which is what you would want / expect, so it starts
    // with the Henry insert again
    $dbInterface->update('UPDATE otherTable SET tableId = ? WHERE tableName = ?', [$tableId, 'Henry']);
  });
});
```

If there is a deadlock or connection problem, the error handler will roll back the transaction and attempt to retry it 10 times, with increasing wait times inbetween. Only if there are 10 failures within about 30 seconds will the exception be escalated with a DBException.

If you want to pass arguments to $func, this would be an example:

```php
$dbInterface->transaction(function($dbInterface, $table, $tableName) {
  $dbInterface->insert($table, [
    'tableName' => $tableName,
  ]);
  
  $tableId = $dbInterface->lastInsertId();
  
  $dbInterface->update('UPDATE otherTable SET tableId = ? WHERE tableName = ?', [$tableId, $tableName]);
}, $dbInterface, 'myTable', 'Henry');
```

### QUOTE IDENTIFIERS

```php
/**
 * Quotes an identifier, like a table name or column name, so there is no risk
 * of overlap with a reserved keyword
 *
 * @param string $identifier
 * @return string
 */
public function quoteIdentifier(string $identifier) : string;
```

If you want to be safe it is recommended to quote all identifiers for the `select` and `update` function calls. For `insert` and `upsert` the quoting is done for you.

If you quote all identifiers, then changing database systems (where different reserved keywords might exist) or upgrading a database (where new keywords might be reserved) is easier.

#### Examples

```php
$rowsAffected = $dbInterface->update('INSERT INTO ' . $dbInterface->quoteIdentifier('users') . ' (' . $dbInterface->quoteIdentifier('user_id') . ', ' . $dbInterface->quoteIdentifier('first_name') . ') SELECT ' . $dbInterface->quoteIdentifier('user_id') . ', ' . $dbInterface->quoteIdentifier('first_name') . ' FROM ' . $dbInterface->quoteIdentifier('users_backup'));
```

Guideline to use this library
-----------------------------

To use this library to its fullest it is recommended to follow these guidelines:

### Always separate the query from the data

Instead of doing a query like this:

```php
$rowsAffected = $dbInterface->update('UPDATE sessions SET time_zone = \'Europe/Zurich\' WHERE session_id = \'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV\'');
```

Do it like this:

```php
$rowsAffected = $dbInterface->update('UPDATE sessions SET time_zone = ? WHERE session_id = ?', [
  'Europe/Zurich',
  'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV',
]);
```

There are many advantages to separating the query from its data:

1. You can safely use variables coming from a form/user, because SQL injections are impossible
2. Using ? placeholders is much easier than quoting/escaping data, and it does not matter if the data is a string or an int or something else
3. Queries become shorter and more readable
4. Using a different database system becomes easier, as you might use `"` to wrap strings in MySQL, while you would use `'` in PostgreSQL (`"` is used for identifiers). If you use ? placeholders you do not need to use any type of quotes for the data, so your queries become more universal.

### Quote identifiers (table names and column names)

It makes sense to quote all your table names and column names in order to avoid having any overlap with reserved keywords and making your queries more resilient. So instead of

```php
$rowsAffected = $dbInterface->update('UPDATE sessions SET time_zone = ? WHERE session_id = ?', [
  'Europe/Zurich',
  'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV',
]);
```

You would change it to

```php
$rowsAffected = $dbInterface->update('UPDATE ' . $dbInterface->quoteIdentifier('sessions') . ' SET ' . $dbInterface->quoteIdentifier('time_zone') . ' = ? WHERE ' . $dbInterface->quoteIdentifier('session_id') . ' = ?', [
  'Europe/Zurich',
  'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV',
]);
```

While this might seem overly verbose you are making your queries more future proof - if you upgrade your database system new reserved keywords could be added which conflict with a query, or changing the database system could lead to a different set of reserved keywords.

### Use simple queries

Avoid complicated queries if at all possible. Queries become increasingly complicated if:

- more than two tables are involved
- GROUP BY is used
- subqueries are used
- database specific features are used (stored procedures, triggers, views, etc.)

It is often tempting to solve many problems with one query, but the downsides are plentiful:

- Performance decreases the more complex a query becomes
- Multiple short queries can be cached and load-balanced better than one big query
- Porting a complex query to a different database system might necessitate many changes
- Understanding and changing complex queries is a lot harder, so errors are more likely

Sometimes a complex query can make more sense, but it should be the rare exception for less than 1% of cases.

### Use squirrelphp/queries-bundle and squirrelphp/entities

[squirrelphp/entities](https://github.com/squirrelphp/entities) is a library built on top of `squirrelphp/queries` and offers easy manipulation of database tables and follows all the above guidelines. [squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle) is the Symfony bundle integrating entities and repositories into a Symfony project.

Why don't you support X? Why is feature Y not included?
-------------------------------------------------------

This package was built for my needs originally. If you have sensible additional needs which should be considered, please open an issue or make a pull request. Keep in mind that the focus of the DBInterface itself is narrow by design.