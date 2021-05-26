<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Upsert query builder as a fluent object - build query and execute it
 */
class InsertOrUpdateEntry implements BuilderInterface
{
    private string $table = '';

    /**
     * @var array<string,mixed> VALUES clauses for the query
     */
    private array $values = [];

    /**
     * @var string[] Unique index fields to determine when to update and when to insert
     */
    private array $index = [];

    /**
     * @var array<int|string,mixed>|null SET clauses for the update part of the query
     */
    private ?array $valuesOnUpdate = null;

    public function __construct(
        private DBInterface $db,
    ) {
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
    public function index(array|string $indexFields): self
    {
        if (\is_string($indexFields)) {
            $indexFields = [$indexFields];
        }

        $this->index = $indexFields;
        return $this;
    }

    /**
     * @param array<int|string,mixed>|string|null $values
     */
    public function setOnUpdate(array|string|null $values): self
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
