<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * Iterator returned by SelectEntries to be used in a foreach loop
 */
class SelectIterator implements \Iterator
{
    use SelectIteratorTrait;

    /**
     * @var DBInterface
     */
    private $source;

    /**
     * @var DBSelectQueryInterface|null
     */
    private $selectReference;

    /**
     * @var array|null
     */
    private $lastResult;

    public function __construct(DBInterface $db, array $query)
    {
        $this->source = $db;
        $this->query = $query;
    }
}
