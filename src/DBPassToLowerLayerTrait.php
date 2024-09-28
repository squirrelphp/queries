<?php

namespace Squirrel\Queries;

use Squirrel\Connection\ConnectionInterface;

/**
 * Default implementation of all DBRawInterface functions to pass them to lower layer
 */
trait DBPassToLowerLayerTrait
{
    private DBRawInterface $lowerLayer;

    public function transaction(callable $func, mixed ...$arguments): mixed
    {
        return $this->lowerLayer->transaction($func, ...$arguments);
    }

    public function inTransaction(): bool
    {
        return $this->lowerLayer->inTransaction();
    }

    public function select(string|array $query, array $vars = []): DBSelectQueryInterface
    {
        return $this->lowerLayer->select($query, $vars);
    }

    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        return $this->lowerLayer->fetch($selectQuery);
    }

    public function clear(DBSelectQueryInterface $selectQuery): void
    {
        $this->lowerLayer->clear($selectQuery);
    }

    public function fetchOne(string|array $query, array $vars = []): ?array
    {
        return $this->lowerLayer->fetchOne($query, $vars);
    }

    public function fetchAll(string|array $query, array $vars = []): array
    {
        return $this->lowerLayer->fetchAll($query, $vars);
    }

    public function fetchAllAndFlatten(string|array $query, array $vars = []): array
    {
        return $this->lowerLayer->fetchAllAndFlatten($query, $vars);
    }

    public function insert(
        string $table,
        array $row = [],
        string $autoIncrement = '',
    ): ?string {
        return $this->lowerLayer->insert($table, $row, $autoIncrement);
    }

    public function insertOrUpdate(
        string $table,
        array $row = [],
        array $index = [],
        ?array $update = null,
    ): void {
        $this->lowerLayer->insertOrUpdate($table, $row, $index, $update);
    }

    public function update(string $table, array $changes, array $where = []): int
    {
        return $this->lowerLayer->update($table, $changes, $where);
    }

    public function delete(string $table, array $where = []): int
    {
        return $this->lowerLayer->delete($table, $where);
    }

    public function change(string $query, array $vars = []): int
    {
        return $this->lowerLayer->change($query, $vars);
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->lowerLayer->quoteIdentifier($identifier);
    }

    public function quoteExpression(string $expression): string
    {
        return $this->lowerLayer->quoteExpression($expression);
    }

    public function setTransaction(bool $inTransaction): void
    {
        $this->lowerLayer->setTransaction($inTransaction);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->lowerLayer->getConnection();
    }

    public function setLowerLayer(DBRawInterface $lowerLayer): void
    {
        $this->lowerLayer = $lowerLayer;
    }
}
