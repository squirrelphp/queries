<?php

namespace Squirrel\Queries\Builder;

/**
 * Iterator returned by SelectEntries to be used in a foreach loop - common implementation
 */
trait SelectIteratorTrait
{
    /**
     * @var array SELECT query to execute
     */
    private array $query = [];

    /**
     * @var int Position within the result set, set to -1 because it is incremented before it is accessed
     */
    private int $position = -1;

    /**
     * Clear results once the current instance is destroyed
     */
    public function __destruct()
    {
        $this->clear();
    }

    public function next(): void
    {
        if (isset($this->selectReference)) {
            $this->lastResult = $this->source->fetch($this->selectReference);
            $this->position++;
        }
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return ( $this->lastResult === null ? false : true );
    }

    public function rewind(): void
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
