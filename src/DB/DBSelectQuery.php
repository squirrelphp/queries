<?php

namespace Squirrel\Queries\DB;

use Squirrel\Connection\ConnectionQueryInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * @internal
 */
class DBSelectQuery implements DBSelectQueryInterface
{
    public function __construct(
        private ConnectionQueryInterface $statement,
    ) {
    }

    public function getStatement(): ConnectionQueryInterface
    {
        return $this->statement;
    }
}
