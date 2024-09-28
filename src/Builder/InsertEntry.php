<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Insert query builder as a fluent object - build query and execute it
 */
class InsertEntry implements BuilderInterface
{
    private string $table = '';

    /**
     * @var array<string,mixed> VALUES clauses for the query
     */
    private array $values = [];

    public function __construct(
        private readonly DBInterface $db,
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
     * Write changes to database
     */
    public function write(): void
    {
        $this->db->insert($this->table, $this->values);
    }

    /**
     * Write changes to database and return new insert ID
     *
     * @param string $autoIncrementIndex Column / field name for which an autoincrement ID should be returned
     * @return string Return new autoincrement insert ID from database
     */
    public function writeAndReturnNewId(string $autoIncrementIndex): string
    {
        return $this->db->insert($this->table, $this->values, $autoIncrementIndex) ?? '';
    }
}
