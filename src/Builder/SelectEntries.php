<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Select query builder as a fluent object - build query and return entries or flattened fields
 *
 * @implements \IteratorAggregate<int,array>
 */
class SelectEntries implements \IteratorAggregate
{
    /**
     * @var DBInterface
     */
    private $db;

    /**
     * @var array<int|string,string> Only retrieve these fields of the tables
     */
    private $fields = [];

    /**
     * @var array<int|string,mixed>
     */
    private $tables = [];

    /**
     * @var array<int|string,mixed> WHERE restrictions in query
     */
    private $where = [];

    /**
     * @var array<int|string,string> ORDER BY sorting in query
     */
    private $orderBy = [];

    /**
     * @var array<int|string,string> GROUP BY aggregating in query
     */
    private $groupBy = [];

    /**
     * @var int How many results should be returned
     */
    private $limitTo = 0;

    /**
     * @var int Where in the result set to start (so many entries are skipped)
     */
    private $startAt = 0;

    /**
     * @var bool Whether the SELECT query should block the scanned entries
     */
    private $blocking = false;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function field(string $getThisField): self
    {
        $this->fields = [$getThisField];
        return $this;
    }

    /**
     * @param array<int|string,string> $getTheseFields
     */
    public function fields(array $getTheseFields): self
    {
        $this->fields = $getTheseFields;
        return $this;
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

    /**
     * @param array<int|string,string>|string $orderByClauses
     * @return SelectEntries
     */
    public function orderBy($orderByClauses): self
    {
        if (\is_string($orderByClauses)) {
            $orderByClauses = [$orderByClauses];
        }

        $this->orderBy = $orderByClauses;
        return $this;
    }

    /**
     * @param array<int|string,string>|string $groupByClauses
     * @return SelectEntries
     */
    public function groupBy($groupByClauses): self
    {
        if (\is_string($groupByClauses)) {
            $groupByClauses = [$groupByClauses];
        }

        $this->groupBy = $groupByClauses;
        return $this;
    }

    public function startAt(int $startAtNumber): self
    {
        $this->startAt = $startAtNumber;
        return $this;
    }

    public function limitTo(int $numberOfEntries): self
    {
        $this->limitTo = $numberOfEntries;
        return $this;
    }

    public function blocking(bool $active = true): self
    {
        $this->blocking = $active;
        return $this;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAllEntries(): array
    {
        return $this->db->fetchAll([
            'fields' => $this->fields,
            'tables' => $this->tables,
            'where' => $this->where,
            'order' => $this->orderBy,
            'group' => $this->groupBy,
            'limit' => $this->limitTo,
            'offset' => $this->startAt,
            'lock' => $this->blocking,
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getOneEntry(): ?array
    {
        return $this->db->fetchOne([
            'fields' => $this->fields,
            'tables' => $this->tables,
            'where' => $this->where,
            'order' => $this->orderBy,
            'group' => $this->groupBy,
            'offset' => $this->startAt,
            'lock' => $this->blocking,
        ]);
    }

    /**
     * @return array<bool|int|float|string|null>
     */
    public function getFlattenedFields(): array
    {
        return $this->db->fetchAll([
            'fields' => $this->fields,
            'tables' => $this->tables,
            'where' => $this->where,
            'order' => $this->orderBy,
            'group' => $this->groupBy,
            'limit' => $this->limitTo,
            'offset' => $this->startAt,
            'lock' => $this->blocking,
            'flattenFields' => true,
        ]);
    }

    /**
     * @return SelectIterator
     */
    public function getIterator()
    {
        return new SelectIterator($this->db, [
            'fields' => $this->fields,
            'tables' => $this->tables,
            'where' => $this->where,
            'order' => $this->orderBy,
            'group' => $this->groupBy,
            'limit' => $this->limitTo,
            'offset' => $this->startAt,
            'lock' => $this->blocking,
        ]);
    }
}
