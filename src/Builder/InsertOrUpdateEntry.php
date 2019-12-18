<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Upsert query builder as a fluent object - build query and execute it
 */
class InsertOrUpdateEntry
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
     * @var array<string,mixed> VALUES clauses for the query
     */
    private $values = [];

    /**
     * @var string[] Unique index fields to determine when to update and when to insert
     */
    private $index = [];

    /**
     * @var array<int|string,mixed> SET clauses for the update part of the query
     */
    private $valuesOnUpdate = [];

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function inTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param array<string,mixed> $values
     */
    public function set(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @param string[]|string $indexFields
     */
    public function index($indexFields): self
    {
        if (\is_string($indexFields)) {
            $indexFields = [$indexFields];
        }

        $this->index = $indexFields;
        return $this;
    }

    /**
     * @param array<int|string,mixed>|string $values
     */
    public function setOnUpdate($values): self
    {
        if (\is_string($values)) {
            $values = [$values];
        }

        $this->valuesOnUpdate = $values;
        return $this;
    }

    /**
     * Write changes to database
     */
    public function write(): void
    {
        $this->db->insertOrUpdate($this->table, $this->values, $this->index, $this->valuesOnUpdate);
    }
}
