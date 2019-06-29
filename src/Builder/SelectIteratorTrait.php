<?php

namespace Squirrel\Queries\Builder;

/**
 * Iterator returned by SelectEntries to be used in a foreach loop - common implementation
 */
trait SelectIteratorTrait
{
    /**
     * @var mixed Class which we send the query to and get the results
     */
    private $source;

    /**
     * @var array SELECT query to execute
     */
    private $query = [];

    /**
     * @var mixed Reference to the select that we get from the $source object
     */
    private $selectReference;

    /**
     * @var int
     */
    private $position = -1;

    /**
     * @var mixed Last result we retrieved, usually either null or a result set of some kind
     */
    private $lastResult;

    /**
     * Clear results once the current instance is destroyed
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->lastResult;
    }

    public function next()
    {
        if (isset($this->selectReference)) {
            $this->lastResult = $this->source->fetch($this->selectReference);
            $this->position++;
        }
    }

    /**
     * @return mixed
     */
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

        $this->selectReference = $this->source->select($this->query);

        $this->next();
    }

    /**
     * Clears the result set and resets the class to possibly start over or destroy the object
     */
    public function clear(): void
    {
        if (isset($this->selectReference)) {
            $this->source->clear($this->selectReference);
        }
        $this->position = -1;
        $this->selectReference = null;
        $this->lastResult = null;
    }
}
