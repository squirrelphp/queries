Squirrel Queries
================

[![Build Status](https://img.shields.io/travis/com/squirrelphp/queries.svg)](https://travis-ci.com/squirrelphp/queries) [![Test Coverage](https://api.codeclimate.com/v1/badges/4f12e6ef097b4202bf65/test_coverage)](https://codeclimate.com/github/squirrelphp/queries/test_coverage) ![PHPStan](https://img.shields.io/badge/style-level%207-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/queries.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/queries)  [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/queries.svg)](https://packagist.org/packages/squirrelphp/queries) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

Provides a slimmed down concise interface for low level database queries and transactions (DBInterface) aswell as a query builder to make it easier and more expressive to create queries (DBBuilderInterface). The interfaces are limited to avoid confusion/misuse and encourage fail-safe usage.

Doctrine is used as the underlying connection (and abstraction), what we add are an upsert (MERGE) functionality, structured queries which are easier to write and read (and for which the query builder can be used), and the possibility to layer database concerns (like actual implementation, connections retries, performance measurements, logging, etc.).

By default this library provides two layers, one dealing with Doctrine DBAL (passing the queries, processing and returning the results) and one dealing with errors (DBErrorHandler). DBErrorHandler catches deadlocks and connection problems and tries to repeat the query or transaction, and it unifies the exceptions coming from DBAL so the originating call to DBInterface is provided and the error can easily be found.

Installation
------------

    composer require squirrelphp/queries

Table of contents
-----------------

- [Setting up](#setting-up)
- [DBInterface - low level interface](#dbinterface---low-level-interface)
- [DBBuilderInterface - higher level query builder](#dbbuilderinterface---higher-level-query-builder)
- [Guidelines to use this library](#guidelines-to-use-this-library)

Setting up
----------

Use Squirrel\Queries\DBInterface as a type hint in your services for the low-level interface, and/or Squirrel\Queries\DBBuilderInterface for the query builder. The low-level interface options are based upon Doctrine and PDO with some tweaks, and the builder interface is an expressive way to write structured (and not too complex) queries.

If you know Doctrine or PDO you should be able to use this library easily. You should especially have an extra look at structured queries and UPSERT, as these are an addition to the low-level interface, helping you to make readable queries and taking care of your column field names and parameters automatically, making it easier to write secure queries.

For a solution which integrates easily with the Symfony framework, check out [squirrelphp/queries-bundle](https://github.com/squirrelphp/queries-bundle), and for entity and repository support check out [squirrelphp/entities](https://github.com/squirrelphp/entities) and [squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle).

If you want to assemble a DBInterface object yourself, something like the following code can be a start:

    use Doctrine\DBAL\DriverManager;
    use Squirrel\Queries\DBBuilderInterface;
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
    
    // A builder just needs a DBInterface to be created:
    
    $queryBuilder = new DBBuilderInterface($errorLayer);
    
    // The query builder generates more readable queries, and
    // helps your IDE in terms of type hints / possible options
    // depending on the query you are doing
    $entries = $queryBuilder
        ->select()
        ->fields([
          'id',
          'name',
        ])
        ->where([
          'name' => 'Robert',
        ])
        ->getAllEntries();
    
    // If you want to add more layers, you can create a
    // class which implements DBRawInterface and includes
    // the DBPassToLowerLayer trait and then just overwrite
    // the functions you want to change, and then connect
    // it to the other layers through setLowerLayer
    
    // It is also a good idea to catch \Squirrel\Queries\DBException
    // in your application in case of a DB error so it
    // can be handled gracefully
    
DBInterface - low level interface
---------------------------------

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

It is recommended to use query parameters for any query data, even if it is fixed, because it is secure no matter where the data came from (like user input) and the charset or type does not matter (string, integer, boolean), which means SQL injections are not possible.

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

You can pass a structured SELECT query directly to `fetchOne` and `fetchAll` to retrieve one or all results.

### Change queries

Custom INSERT, UPDATE and DELETE queries (or other custom queries) can be executed with the `change` function, implying that this query changes something in contrast to a SELECT query:

```php
$rowsAffected = $db->change('UPDATE users SET first_name = ?, last_name = ?, login_number = login_number + 1 WHERE user_id = ?', [
    'Liam', // first_name
    'Henry', // last_name
    5, // user_id
]);
```

```php
$rowsAffected = $db->change('DELETE FROM users WHERE user_id = ? AND first_name = ?', [
    5, // user_id
    'Liam', // first_name
]);
```

```php
$rowsAffected = $db->change('INSERT INTO users (user_id, first_name) SELECT user_id, first_name FROM users_backup');
```

It is not recommended to use the `change` function except if you have no other choice - most queries can be done using structured UPDATE, UPSERT, INSERT and DELETE queries. Yet if you need subqueries or other advanced database functionality `change` is your only option.

### Structured UPDATE queries

Instead of using change queries, for updates you can use a structured UPDATE query very similar to the structured SELECT query. An example:

```php
$rowsAffected = $db->update([
    'changes' => [
        'fieldname' => 'string',
        'locationId' => 5,
    ],
    'table' => 'tablename',
    'where' => [
        'restriction' => 5,
        'restriction2' => 8,
    ],
]);
```

This structured query is identical to the following string query:

```php
$rowsAffected = $db->change('UPDATE ´tablename´ SET ´fieldname´=?,`locationId`=? WHERE ´restriction´=? AND ´restriction2´=?', ['string', 5, 5, 8]);
```

Structured UPDATE queries make mistakes less likely and are easier to read. This shows all the possible options:

```php
$rowsAffected = $db->update([
    'changes' => [
        'a.fieldname' => 'some change',
        'b.locationId' => 5,
        ':a.counter: = :a.counter: + 1',
    ],
    'tables' => ['
      'tablename a',
      'othertable b',
    ],
    'where' => [
        ':a.id: = :b.id:',
        'a.restriction' => 5,
        ':a.counter: > ?' => 0,
    ],
    'order' => [
        'a.counter' => 'DESC',
    ],
    'limit' => 3,
]);
```

### INSERT

`insert` does an INSERT query into one table, example:

```php
$rowsAffected = $db->insert('yourdatabase.yourtable', [
    'tableId' => 5,
    'column1' => 'Henry',
    'other_column' => 'Liam',
]);

// Get the last insert ID if you have an autoincrement primary index:
$newInsertedId = $db->lastInsertId();
```

The equivalent string query would be:

```php
$rowsAffected = $db->change('INSERT INTO `yourdatabase`.`yourtable` (`tableId`,`column1`,`other_column`) VALUES (?,?,?)', [5, 'Henry', 'Liam']);

// Get the last insert ID if you have an autoincrement primary index:
$newInsertedId = $db->lastInsertId();
```

### UPSERT / MERGE

#### Definition

UPSERT (update-or-insert) queries are an addition to SQL, known under different queries in different database systems:

- MySQL implemented them as "INSERT ... ON DUPLICATE KEY UPDATE"
- PostgreSQL and SQLite as "INSERT ... ON CONFLICT (index) DO UPDATE"
- The ANSI standard knows them as MERGE queries

An upsert query tries to update a row, but if the row does not exists it does an insert instead, and all of this is done as one atomic operation in the database. If implemented without an UPSERT query you would need at least an UPDATE and then possibly an INSERT query within a transaction to do the same. UPSERT exists to be a faster and easier solution.

PostgreSQL and SQLite need the specific column names which form a unique index (already existing for the table) which is used to determine if an entry already exists or if a new entry is inserted. MySQL does this automatically, but for all database systems it is important to have a unique index involved in an upsert query.

#### Usage and examples

The first two arguments for the `upsert` function are identical to the normal insert function, the third defines the columns which form a unique or primary key for the table in the database. And the last array is the updates to do if the entry already exists in the database, but it is optional.

An example could be:

```php
$db->upsert('users_visits', [
    'userId' => 5,
    'visit' => 1,
], [
    'userId',
], [
    ':visit: = :visit: + 1'
]);
```

For MySQL, this query would be converted to:

```php
$db->change('INSERT INTO `users_visits` (`userId`,`visit`) VALUES (?,?) ON DUPLICATE KEY UPDATE `visit` = `visit` + 1', [5, 1]);
```

For PostgreSQL/SQLite it would be:

```php
$db->change('INSERT INTO `users_visits` (`userId`,`visit`) VALUES (?,?) ON CONFLICT (`userId`) DO UPDATE `visit` = `visit` + 1', [5, 1]);
```

If no entry exists in `users_visits`, one is inserted with `visit` set to 1. But if an entry already exists an UPDATE with `visit = visit + 1` is done instead.

Defining the UPDATE part is optional, and if left empty the UPDATE just does the same changes as the INSERT minus the index columns. Example:

```php
$db->upsert('users_names', [
    'userId' => 5,
    'firstName' => 'Jane',
], [
    'userId',
]);
```

This would INSERT with userId and firstName, but if the row already exists, it would just update firstName to Jane, so for MySQL it would be converted to:

```php
$db->change('INSERT INTO `users_names` (`userId`,`firstName`) VALUES (?,?) ON DUPLICATE KEY UPDATE `firstName`=?, [5, 'Jane', 'Jane']);
```

The most important thing to remember is that you need a unique or primary index involved in an UPSERT query - so you need to know the indexing of the table.

### DELETE

The `delete` function offers a structured way of doing a DELETE query for one table. Example:

```php
$rowsAffected = $db->delete('users_names', [
    'userId' => 13,
]);
```

The first argument is the name of the table, the second argument the WHERE restrictions. So as a pure string query this would be equal to:

```php
$rowsAffected = $db->change('DELETE FROM `users_names` WHERE `userId`=?', [13]);
```

The structured WHERE entries follow the same logic/rules as for structured SELECT and UPDATE queries.

### TRANSACTION

Just pass a callable/function to the `transaction` method and DBInterface will take care of the commit/rollback parts automatically and do its best to make the transaction succeed.

#### Examples

```php
$db->transaction(function(){
    // Do queries in here as much as you want, it will all be one transaction
    // and committed as soon as this function ends
});
```

An actual example might be:

```php
$db->transaction(function() use ($db) {
    $db->insert('myTable', [
      'tableName' => 'Henry',
    ]);
  
    $tableId = $db->lastInsertId();
  
    $db->update([
        'table' => 'otherTable',
        'changes' => [
            'tableName' => 'Henry',
        ],
        'where' => [
            'tableId' => $tableId,
        ],
    ]);
});
```

If you call `transaction` within a transaction function, that function will just become part of the "outer transaction" and will fail or succeed with it:

```php
$db->transaction(function() use ($db) {
    $db->insert('myTable', [
        'tableId' => 5,
        'tableName' => 'Henry',
    ]);
  
    $tableId = $db->lastInsertId();
  
    // This still does exactly the same as in the previous example, because the
    // function will be executed without a "new" transaction being started,
    // the existing one just continues
    $db->transaction(function() use ($db, $tableId)) {
        // If this fails, then the error handler will attempt to repeat the outermost
        // transaction function, which is what you would want / expect, so it starts
        // with the Henry insert again
        $db->update([
            'table' => 'otherTable',
            'changes' => [
                'tableName' => 'Henry',
            ],
            'where' => [
                'tableId' => $tableId,
            ],
        ]);
    });
});
```

If there is a deadlock or connection problem, the error handler (DBErrorHandler) will roll back the transaction and attempt to retry it 10 times, with increasing wait times inbetween. Only if there are 10 failures within about 30 seconds will the exception be escalated with a DBException.

If you want to pass arguments to $func, this would be an example:

```php
$db->transaction(function($db, $table, $tableName) {
    $db->insert($table, [
        'tableName' => $tableName,
    ]);
  
    $tableId = $db->lastInsertId();
  
    $db->update([
        'table' => 'otherTable',
        'changes' => [
            'tableName' => $tableName,
        ],
        'where' => [
            'tableId' => $tableId,
        ],
    ]);
}, $db, 'myTable', 'Henry');
```

When using SELECT queries within a transaction you should always remember that the results are usually not locked (so not protected from UPDATE or DELETE), except if you apply "... FOR UPDATE" (in a string SELECT query) or by setting `lock` to true in a structured SELECT.

### QUOTE IDENTIFIERS

If you want to be safe it is recommended to quote all identifiers (table names and column names) with the DBInterface `quoteIdentifier` function for non-structured `select` and `update` queries.

For `insert` and `upsert` the quoting is done for you, and for structured queries most of the quoting is done for you, except if you use an expression, where you can just use colons to specify a table or column name.

If you quote all identifiers, then changing database systems (where different reserved keywords might exist) or upgrading a database (where new keywords might be reserved) is easier.

#### Examples

```php
$rowsAffected = $db->change('UPDATE ' . $db->quoteIdentifier('users') . ' SET ' . $db->quoteIdentifier('first_name') . ')=? WHERE ' . $db->quoteIdentifier('user_id') . '=?', ['Sandra', 5]);
```

DBBuilderInterface - higher level query builder
-----------------------------------------------

DBBuilderInterface offers the following functions:

- count
- select
- insert
- update
- insertOrUpdate (= UPSERT)
- delete
- transaction (to do a function within a transaction)
- getDBInterface (to get the underlying DBInterface object)

All except the last two return a builder object which helps you easily create a query and get the results. Compared to DBInterface you do not have to remember what data can be contained in a structured query - your IDE will suggest whatever is available. You can also have a look at the builder objects themselves - they are all very short.

It is almost easiest to just try it out yourself - in the background DBBuilder converts the query into a structured query to pass to DBInterface.

There are some examples below for each of the 6 builder functions:

### Count

```php
$usersNumber = $dbBuilder
    ->count()
    ->inTables([
        'users u',
        'users_addresses a',
    ])
    ->where([
        ':u.userId: = :a.userId:',
        'u.zipCode' => 33769,
    ])
    ->getNumber();
```

An easy way to just count the number of rows.

### Select

```php
$userResults = $dbBuilder
    ->select()
    ->fields([
        'u.userId',
        'name' => 'a.firstName',
    ])
    ->inTables([
        'users u',
        'users_addresses a',
    ])
    ->where([
        ':u.userId: = :a.userId:',
        'u.zipCode' => 33769,
    ])
    ->groupBy([
        'u.userId',
    ])
    ->orderBy([
        'u.createDate',
    ])
    ->limitTo(3)
    ->startAt(0)
    ->blocking()
    ->getAllEntries();
```

Select queries can become the most complex, so they have many options - above you can see all of them, except for the last call, where you have different options:

- getAllEntries, to retrieve an array with all the entries at once
- getOneEntry, to just get exactly one entry
- getIterator, to get an object you can iterate over (foreach) so you can get one result after the other
- getFlattenedFields, which means the results are "flattened"

getFlattenedFields can be useful for something like this:

```php
$userIds = $dbBuilder
    ->select()
    ->field('userId')
    ->inTable('users')
    ->where([
        'u.zipCode' => 33769,
    ])
    ->getFlattenedFields();
```

Instead of a list of arrays each with a field "userId", the results are flattened and directly return a list of user IDs. Flattening is mostly useful for IDs or other simple lists of values, where you just need one array instead of an array of an array.

### Insert

```php
$newUserId = $dbBuilder
    ->insert()
    ->inTable('users')
    ->set([
      'userName' => 'Kjell',
    ])
    ->writeAndReturnNewId();
```

You can use `writeAndReturnNewId` if you are expecting/needing an insert ID, or just `write` to insert the entry without a return value.

### Update

```php
$rowsAffected = $dbBuilder
    ->update()
    ->inTables([
        'users',
    ])
    ->set([
        'lastLoginDate' => time(),
        ':visits: = :visits: + 1',
    ])
    ->where([
        'userId' => 33,
    ])
    ->limitTo(1)
    ->writeAndReturnAffectedNumber();
```

You can use `writeAndReturnAffectedNumber` if you are interested in the number of affected/changed rows, or `write` if you do not need that information. Another optionsnot shown above is `orderBy`.

### Insert or Update

This makes the UPSERT functionality in DBInterface a bit easier to digest, using the same information:

```php
$result = $insertBuilder
    ->insertOrUpdate()
    ->inTable('users')
    ->set([
        'userId',
        'firstName' => 'Annemarie',
        'visits' => 1,
    ])
    ->index([
        'userId',
    ])
    ->setOnUpdate([
        ':visits: = :visits: + 1',
    ])
    ->writeAndReturnWhatHappened();
```

`writeAndReturnWhatHappened` returns either "insert", "update" or an empty string "" - this represents what the database did, if a row was inserted, if an existing row was updated, or if nothing happened because the row data was already identical to the query data. You can use `write` if you are not interested in the query result.

### Delete

```php
$rowsAffected = $dbBuilder
    ->delete()
    ->inTable('users')
    ->where([
        'userId' => 33,
    ])
    ->writeAndReturnAffectedNumber();
```

You can use `writeAndReturnAffectedNumber` if you are interested in the number of affected/changed rows, or `write` if you do not need that information.

### Transaction

The transaction function works the same as the one in DBInterface - in fact, DBBuilderInterface just passes it as-is to DBInterface.

Guidelines to use this library
------------------------------

To use this library to its fullest it is recommended to follow these guidelines:

### Use DBBuilderInterface - or structured queries

The easiest and safest option is to use the builder (DBBuilderInterface) - with an IDE you will have an easy time completing your queries while separating the query from the data is very easy and almost automatic.

If you want to use DBInterface instead, use structured SELECT and UPDATE queries, as they are easier to write and read and make separating the query from the data easier, while still containing basically the same information as a "pure" string query, so use them instead of writing SQL queries on your own.

INSERT, UPSERT und DELETE queries are already structured because their focus is limited. With these five query types you should be able to handle 99% of queries.

### Always separate the query from the data

For your application security, separating the query from the data is very important / helpful. Instead of doing a query like this:

```php
$rowsAffected = $db->change('UPDATE sessions SET time_zone = \'Europe/Zurich\' WHERE session_id = \'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV\'');
```

Do it like this: (or use a structured query, see the tip above!)

```php
$rowsAffected = $db->update('UPDATE sessions SET time_zone = ? WHERE session_id = ?', [
    'Europe/Zurich',
    'zzjEe2Jpksrjxsd05m1tOwnc7LJNV4sV',
]);
```

There are many advantages to separating the query from its data:

1. You can safely use variables coming from a form/user, because SQL injections are impossible
2. Using ? placeholders is much easier than quoting/escaping data, and it does not matter if the data is a string or an int or something else
3. Queries become shorter and more readable
4. Using a different database system becomes easier, as you might use `"` to wrap strings in MySQL, while you would use `'` in PostgreSQL (`"` is used for identifiers). If you use ? placeholders you do not need to use any type of quotes for the data, so your queries become more universal.

### Use simple queries

Avoid complicated queries if at all possible. Queries become increasingly complicated if:

- more than two tables are involved
- GROUP BY or HAVING is used
- subqueries are used
- database specific features are used (stored procedures, triggers, views, etc.)

It is often tempting to solve many problems with one query, but the downsides are plentiful:

- Performance decreases the more complex a query becomes
- Multiple short queries can be cached and load-balanced better than one big query
- Porting a complex query to a different database system might necessitate many changes
- Understanding and changing complex queries is a lot harder, so errors are more likely

Sometimes a complex query can make more sense, but it should be the rare exception for less than 1% of cases.

### Use squirrelphp/queries-bundle and squirrelphp/entities

[squirrelphp/queries-bundle](https://github.com/squirrelphp/queries-bundle) is an integration of this library into Symfony, so you can get started quickly.

[squirrelphp/entities](https://github.com/squirrelphp/entities) is a library built on top of `squirrelphp/queries` and offers support for entities and repositories while following all the above guidelines.

[squirrelphp/entities-bundle](https://github.com/squirrelphp/entities-bundle) is the Symfony bundle integrating entities and repositories into a Symfony project.

Why don't you support X? Why is feature Y not included?
-------------------------------------------------------

This package was built for my needs originally. If you have sensible additional needs which should be considered, please open an issue or make a pull request. Keep in mind that the focus of the DBInterface itself is narrow by design.