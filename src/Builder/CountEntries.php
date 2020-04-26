<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Count query builder as a fluent object - build query and return entries or flattened fields
 */
class CountEntries
{
    private DBInterface $db;

    /**
     * @var array<int|string,mixed> Explicit connections between the repositories
     */
    private array $tables = [];

    /**
     * @var array<int|string,mixed> WHERE restrictions in query
     */
    private array $where = [];

    /**
     * @var bool Whether the SELECT query should block the scanned entries
     */
    private bool $blocking = false;

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
        $results = $this->db->fetchAllAndFlatten([
            'fields' => [
                'num' => 'COUNT(*)',
            ],
            'tables' => $this->tables,
            'where' => $this->where,
            'lock' => $this->blocking,
        ]);

        return \intval($results[0] ?? 0);
    }
}
