<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Driver\ResultStatement;
use Squirrel\Queries\DBSelectQueryInterface;

class DBSelectQuery implements DBSelectQueryInterface
{
    private ResultStatement $statement;

    public function __construct(ResultStatement $statement)
    {
        $this->statement = $statement;
    }

    public function getStatement(): ResultStatement
    {
        return $this->statement;
    }
}
