<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * Iterator returned by SelectEntries to be used in a foreach loop
 *
 * @implements \Iterator<int,array>
 */
class SelectIterator implements \Iterator
{
    use SelectIteratorTrait;

    private DBInterface $source;
    private ?DBSelectQueryInterface $selectReference = null;
    private ?array $lastResult = null;

    public function __construct(DBInterface $db, array $query)
    {
        $this->source = $db;
        $this->query = $query;
    }

    public function current(): array
    {
        if ($this->lastResult === null) {
            throw new \LogicException('Cannot get current value if no result has been retrieved');
        }

        return $this->lastResult;
    }
}
