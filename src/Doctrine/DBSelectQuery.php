<?php

namespace Squirrel\Queries\Doctrine;

use Doctrine\DBAL\Driver\ResultStatement;
use Squirrel\Queries\DBSelectQueryInterface;

class DBSelectQuery implements DBSelectQueryInterface
{
    /**
     * @var ResultStatement
     */
    private $statement;

    /**
     * @param ResultStatement $statement
     */
    public function __construct(ResultStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @return ResultStatement
     */
    public function getStatement(): ResultStatement
    {
        return $this->statement;
    }
}
