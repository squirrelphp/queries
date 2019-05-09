<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Queries\DBInterface;

/**
 * Insert query builder as a fluent object - build query and execute it
 */
class InsertEntry
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
     * @var array VALUES clauses for the query
     */
    private $values = [];

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    public function inTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

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
    public function writeAndReturnNewId(string $autoIncrementIndex = ''): string
    {
        return $this->db->insert($this->table, $this->values, $autoIncrementIndex) ?? '';
    }
}
