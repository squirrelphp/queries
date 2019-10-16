<?php

namespace Squirrel\Queries\TestHelpers;

use Squirrel\Queries\DBInterface;

/**
 * Abstract class for partial mocks in test cases
 */
abstract class DBInterfaceForTests implements DBInterface
{
    public function quoteIdentifier(string $identifier): string
    {
        if (strpos($identifier, ".") !== false) {
            $parts = array_map(
                function ($p) {
                    return '"' . str_replace('"', '""', $p) . '"';
                },
                explode(".", $identifier)
            );

            return implode(".", $parts);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
