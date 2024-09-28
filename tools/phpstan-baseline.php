<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedBooleanFields\\(\\) should return array\\<bool\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedFloatFields\\(\\) should return array\\<float\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedIntegerFields\\(\\) should return array\\<int\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\Builder\\\\SelectEntries\\:\\:getFlattenedStringFields\\(\\) should return array\\<string\\> but returns array\\<bool\\|float\\|int\\|string\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/Builder/SelectEntries.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$values of method Squirrel\\\\Connection\\\\ConnectionInterface\\:\\:executeQuery\\(\\) expects array\\<bool\\|float\\|int\\|Squirrel\\\\Connection\\\\LargeObject\\|string\\>, array\\<int, mixed\\> given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../src/DB/AbstractImplementation.php',
];
$ignoreErrors[] = [
	// identifier: catch.neverThrown
	'message' => '#^Dead catch \\- Squirrel\\\\Connection\\\\Exception\\\\ConnectionException is never thrown in the try block\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: catch.neverThrown
	'message' => '#^Dead catch \\- Squirrel\\\\Connection\\\\Exception\\\\DriverException is never thrown in the try block\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:change\\(\\) should return int but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:delete\\(\\) should return int but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetch\\(\\) should return array\\<string, mixed\\>\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchAll\\(\\) should return array\\<int, array\\<string, mixed\\>\\> but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchAllAndFlatten\\(\\) should return array\\<bool\\|float\\|int\\|string\\|null\\> but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:fetchOne\\(\\) should return array\\<string, mixed\\>\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:insert\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:select\\(\\) should return Squirrel\\\\Queries\\\\DBSelectQueryInterface but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Squirrel\\\\Queries\\\\DB\\\\ErrorHandler\\:\\:update\\(\\) should return int but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../src/DB/ErrorHandler.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
