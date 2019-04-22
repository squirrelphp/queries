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
     * @var bool
     */
    private $flattenFields;

    /**
     * @param ResultStatement $statement
     * @param bool $flattenFields
     */
    public function __construct(ResultStatement $statement, bool $flattenFields = false)
    {
        $this->statement = $statement;
        $this->flattenFields = $flattenFields;
    }

    /**
     * @return ResultStatement
     */
    public function getStatement(): ResultStatement
    {
        return $this->statement;
    }

    /**
     * @return bool
     */
    public function hasFlattenFields(): bool
    {
        return $this->flattenFields;
    }
}
