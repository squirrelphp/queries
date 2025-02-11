<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedBooleanFields\\(\\) should return array\\<bool\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedFloatFields\\(\\) should return array\\<float\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedIntegerFields\\(\\) should return array\\<int\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedStringFields\\(\\) should return array\\<string\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method Squirrel\\\\Queries\\\\DBInterface\\:\\:select\\(\\) expects array\\{fields\\?\\: array\\<int\\|string, string\\>, field\\?\\: string, tables\\?\\: array\\<int\\|string, mixed\\>, table\\?\\: string, where\\?\\: array\\<int\\|string, mixed\\>, group\\?\\: array\\<int\\|string, string\\>, order\\?\\: array\\<int\\|string, string\\>, limit\\?\\: int, \\.\\.\\.\\}\\|string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type mixed supplied for foreach, only iterables are supported\\.$#',
	'identifier' => 'foreach.nonIterable',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \' WHERE \' and mixed results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and mixed results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 3,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\AbstractImplementation\\:\\:fetchAllAndFlatten\\(\\) should return array\\<bool\\|float\\|int\\|string\\|null\\> but returns array\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$fields of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildFieldSelection\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$groupByOptions of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildGroupBy\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$orderOptions of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildOrderBy\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:prepareQuery\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tables of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildTableJoins\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$whereOptions of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildWhere\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$queryValues of method Squirrel\\\\Queries\\\\DB\\\\ConvertStructuredQueryToSQL\\:\\:buildWhere\\(\\) expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$row of method Squirrel\\\\Queries\\\\DB\\\\AbstractImplementation\\:\\:insert\\(\\) expects array\\<string, mixed\\>, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, array\\<int, mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, list\\<mixed\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$vars of method Squirrel\\\\Queries\\\\DB\\\\AbstractImplementation\\:\\:change\\(\\) expects array\\<int, mixed\\>, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$limit of method Squirrel\\\\Queries\\\\DB\\\\AbstractImplementation\\:\\:addLimitOffsetToQuery\\(\\) expects int\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$offset of method Squirrel\\\\Queries\\\\DB\\\\AbstractImplementation\\:\\:addLimitOffsetToQuery\\(\\) expects int\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'\\(\' and mixed results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between mixed and \' \' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between mixed and \' AS "\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between mixed and \' IN \\(\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between mixed and \' IS NULL\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between mixed and \'\\=\\?\' results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function intval expects array\\|bool\\|float\\|int\\|resource\\|string\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function strval expects bool\\|float\\|int\\|resource\\|string\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ConvertStructuredQueryToSQL.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Exception is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Squirrel\\\\Connection\\\\Exception\\\\ConnectionException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Squirrel\\\\Connection\\\\Exception\\\\DriverException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:change\\(\\) should return int but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:delete\\(\\) should return int but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetch\\(\\) should return array\\<string, mixed\\>\\|null but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchAll\\(\\) should return array\\<int, array\\<string, mixed\\>\\> but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchAllAndFlatten\\(\\) should return array\\<bool\\|float\\|int\\|string\\|null\\> but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchOne\\(\\) should return array\\<string, mixed\\>\\|null but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:insert\\(\\) should return string\\|null but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:select\\(\\) should return Squirrel\\\\Queries\\\\DBSelectQueryInterface but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:update\\(\\) should return int but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function array_map expects \\(callable\\(mixed\\)\\: mixed\\)\\|null, \'intval\' given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$microseconds of function usleep expects int, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between non\\-falsy\\-string and mixed results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/MySQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/MySQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Binary operation "\\." between \'DO UPDATE SET \' and mixed results in an error\\.$#',
	'identifier' => 'binaryOp.invalid',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/PostgreSQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function array_map expects \\(callable\\(mixed\\)\\: mixed\\)\\|null, array\\{\\$this\\(Squirrel\\\\Queries\\\\DB\\\\PostgreSQLImplementation\\), \'quoteIdentifier\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/PostgreSQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$query of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:prepareQuery\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/PostgreSQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/PostgreSQLImplementation.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function substr expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/SQLiteImplementation.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
