<?php

namespace Squirrel\Queries\Tests;

use Hamcrest\Core\IsEqual;
use Squirrel\Connection\ConnectionQueryInterface;

trait SharedFunctionalityTrait
{
    /**
     * Recreated quoteIdentifier function as a standin
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        if (\str_contains($identifier, ".")) {
            $parts = \array_map(
                function ($p) {
                    return '"' . \str_replace('"', '""', $p) . '"';
                },
                \explode(".", $identifier),
            );

            return \implode(".", $parts);
        }

        return '"' . \str_replace('"', '""', $identifier) . '"';
    }

    protected function prepareForQueryWithVars(string $query, array $vars, bool $expectFreeResults = true): ConnectionQueryInterface
    {
        $statement = \Mockery::mock(ConnectionQueryInterface::class);

        $this->connection
            ->shouldReceive('prepareQuery')
            ->once()
            ->with(IsEqual::equalTo($query))
            ->andReturn($statement);

        $this->connection
            ->shouldReceive('executeQuery')
            ->once()
            ->with(IsEqual::equalTo($statement), IsEqual::equalTo($vars));

        if ($expectFreeResults) {
            $this->connection
                ->shouldReceive('freeResults')
                ->once()
                ->with(IsEqual::equalTo($statement));
        }

        return $statement;
    }
}
