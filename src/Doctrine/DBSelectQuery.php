<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Result;
use Squirrel\Queries\DBSelectQueryInterface;

class DBSelectQuery implements DBSelectQueryInterface
{
    private Result $statement;

    public function __construct(Result $statement)
    {
        $this->statement = $statement;
    }

    public function getStatement(): Result
    {
        return $this->statement;
    }
}
