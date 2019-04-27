<?php

namespace Squirrel\Queries;

use Squirrel\Queries\Builder\CountEntries;
use Squirrel\Queries\Builder\DeleteEntries;
use Squirrel\Queries\Builder\InsertEntry;
use Squirrel\Queries\Builder\InsertOrUpdateEntry;
use Squirrel\Queries\Builder\SelectEntries;
use Squirrel\Queries\Builder\UpdateEntries;

/**
 * Simple delegation interface - builder just returns other objects which are self-explanatory
 */
interface DBBuilderInterface
{
    public function count(): CountEntries;

    public function select(): SelectEntries;

    public function insert(): InsertEntry;

    public function update(): UpdateEntries;

    public function insertOrUpdate(): InsertOrUpdateEntry;

    public function delete(): DeleteEntries;

    public function transaction(callable $func, ...$arguments);

    public function getDBInterface(): DBInterface;
}
