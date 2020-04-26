<?php

namespace Squirrel\Queries;

use Squirrel\Queries\Builder\CountEntries;
use Squirrel\Queries\Builder\DeleteEntries;
use Squirrel\Queries\Builder\InsertEntry;
use Squirrel\Queries\Builder\InsertOrUpdateEntry;
use Squirrel\Queries\Builder\SelectEntries;
use Squirrel\Queries\Builder\UpdateEntries;

/**
 * Super simple class, just returns new objects, does not depend on anything concrete
 */
class DBBuilder implements DBBuilderInterface
{
    private DBInterface $db;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function count(): CountEntries
    {
        return new CountEntries($this->db);
    }

    public function select(): SelectEntries
    {
        return new SelectEntries($this->db);
    }

    public function insert(): InsertEntry
    {
        return new InsertEntry($this->db);
    }

    public function update(): UpdateEntries
    {
        return new UpdateEntries($this->db);
    }

    public function insertOrUpdate(): InsertOrUpdateEntry
    {
        return new InsertOrUpdateEntry($this->db);
    }

    public function delete(): DeleteEntries
    {
        return new DeleteEntries($this->db);
    }

    public function transaction(callable $func, ...$arguments)
    {
        return $this->db->transaction($func, ...$arguments);
    }

    public function getDBInterface(): DBInterface
    {
        return $this->db;
    }
}
