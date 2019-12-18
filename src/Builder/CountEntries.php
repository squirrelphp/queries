<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Count query builder as a fluent object - build query and return entries or flattened fields
 */
class CountEntries
{
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var array<int|string,mixed> Explicit connections between the repositories
     */
    private $tables = [];

    /**
     * @var array<int|string,mixed> WHERE restrictions in query
     */
    private $where = [];

    /**
     * @var bool Whether the SELECT query should block the scanned entries
     */
    private $blocking = false;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function inTable(string $table): self
    {
        return $this->inTables([$table]);
    }

    /**
     * @param array<int|string,mixed> $tables
     */
    public function inTables(array $tables): self
    {
        $this->tables = $tables;
        return $this;
    }

    /**
     * @param array<int|string,mixed> $whereClauses
     */
    public function where(array $whereClauses): self
    {
        $this->where = $whereClauses;
        return $this;
    }

    public function blocking(bool $active = true): self
    {
        $this->blocking = $active;
        return $this;
    }

    /**
     * Execute SELECT query and return number of entries
     */
    public function getNumber(): int
    {
        $results = $this->db->fetchAll([
            'fields' => [
                'num' => 'COUNT(*)',
            ],
            'tables' => $this->tables,
            'where' => $this->where,
            'flattenFields' => true,
            'lock' => $this->blocking,
        ]);

        return $results[0] ?? 0;
    }
}
