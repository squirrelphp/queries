<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBDebug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Update query builder as a fluent object - build query and execute it
 */
class UpdateEntries
{
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var string
     */
    private $table = '';

    /**
     * @var array SET clauses for the query
     */
    private $changes = [];

    /**
     * @var array WHERE restrictions in query
     */
    private $where = [];

    /**
     * @var bool We need to confirmation before we execute a query without WHERE restriction
     */
    private $confirmNoWhere = false;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function inTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function set(array $changes): self
    {
        $this->changes = $changes;
        return $this;
    }

    public function where(array $whereClauses): self
    {
        $this->where = $whereClauses;
        return $this;
    }

    public function confirmNoWhereRestrictions(): self
    {
        $this->confirmNoWhere = true;
        return $this;
    }

    /**
     * Write changes to database
     */
    public function write(): void
    {
        $this->writeAndReturnAffectedNumber();
    }

    /**
     * Write changes to database and return affected entries number
     *
     * @return int Number of affected entries in database
     */
    public function writeAndReturnAffectedNumber(): int
    {
        // Make sure there is no accidental "delete everything"
        if (\count($this->where) === 0 && $this->confirmNoWhere !== true) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                [self::class],
                'No restricting "where" arguments defined for UPDATE' .
                'and no override confirmation with "confirmNoWhereRestrictions" call'
            );
        }

        return $this->db->update($this->table, $this->changes, $this->where);
    }
}
