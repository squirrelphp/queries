<?php

namespace Squirrel\Queries\Tests;

use Squirrel\Queries\DBInterface;

/**
 * Abstract class for partial mocks in test cases
 */
abstract class DBInterfaceForTests implements DBInterface
{
    /**
     * @inheritDoc
     */
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
