<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;
use Squirrel\Queries\DBSelectQueryInterface;

/**
 * Iterator returned by SelectEntries to be used in a foreach loop
 */
class SelectIterator implements \Iterator
{
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var array SELECT query to execute
     */
    private $query = [];

    /**
     * @var DBSelectQueryInterface|null
     */
    private $selectReference;

    /**
     * @var int
     */
    private $position = -1;

    /**
     * @var array|null
     */
    private $lastResult;

    public function __construct(DBInterface $db, array $query)
    {
        $this->db = $db;
        $this->query = $query;
    }

    public function current()
    {
        return $this->lastResult;
    }

    public function next()
    {
        if (isset($this->selectReference)) {
            $this->lastResult = $this->db->fetch($this->selectReference);
            $this->position++;
        }
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return ( $this->lastResult === null ? false : true );
    }

    public function rewind()
    {
        $this->clear();

        $this->selectReference = $this->db->select($this->query);

        $this->next();
    }

    public function clear()
    {
        if (isset($this->selectReference)) {
            $this->db->clear($this->selectReference);
        }
        $this->position = -1;
        $this->selectReference = null;
        $this->lastResult = null;
    }
}
