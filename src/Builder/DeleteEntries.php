<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBDebug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;

/**
 * Delete query builder as a fluent object - build query and execute it
 */
class DeleteEntries
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
     * @var array WHERE restrictions in query
     */
    private $where = [];

    /**
     * @var bool We need to confirmation before we delete all entries
     */
    private $deleteAll = false;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function inTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function where(array $whereClauses): self
    {
        $this->where = $whereClauses;
        return $this;
    }

    public function confirmDeleteAll(): self
    {
        $this->deleteAll = true;
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
        $this->accidentalDeleteAllCheck();

        return $this->db->delete($this->table, $this->where);
    }

    /**
     * Make sure there is no accidental "delete everything" because WHERE restrictions were forgotten
     */
    private function accidentalDeleteAllCheck()
    {
        // Make sure there is no accidental "delete everything"
        if (\count($this->where) === 0 && $this->deleteAll !== true) {
            throw DBDebug::createException(
                DBInvalidOptionException::class,
                [self::class],
                'No restricting "where" arguments defined for DELETE'
            );
        }
    }
}
