<?php

namespace Squirrel\Queries;

/**
 * Default implementation of all DBRawInterface functions to pass them to lower layer
 */
trait DBPassToLowerLayerTrait
{
    private DBRawInterface $lowerLayer;

    public function transaction(callable $func, ...$arguments)
    {
        return $this->lowerLayer->transaction($func, ...$arguments);
    }

    public function inTransaction(): bool
    {
        return $this->lowerLayer->inTransaction();
    }

    public function select($query, array $vars = []): DBSelectQueryInterface
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

    public function fetchOne($query, array $vars = []): ?array
    {
        return $this->lowerLayer->fetchOne($query, $vars);
    }

    public function fetchAll($query, array $vars = []): array
    {
        return $this->lowerLayer->fetchAll($query, $vars);
    }

    public function fetchAllAndFlatten($query, array $vars = []): array
    {
        return $this->lowerLayer->fetchAllAndFlatten($query, $vars);
    }

    public function insert(
        string $tableName,
        array $row = [],
        string $autoIncrementIndex = ''
    ): ?string {
        return $this->lowerLayer->insert($tableName, $row, $autoIncrementIndex);
    }

    public function insertOrUpdate(
        string $tableName,
        array $row = [],
        array $indexColumns = [],
        ?array $rowUpdates = null
    ): void {
        $this->lowerLayer->insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);
    }

    public function update(string $tableName, array $changes, array $where = []): int
    {
        return $this->lowerLayer->update($tableName, $changes, $where);
    }

    public function delete(string $tableName, array $where = []): int
    {
        return $this->lowerLayer->delete($tableName, $where);
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

    public function getConnection(): object
    {
        return $this->lowerLayer->getConnection();
    }

    public function setLowerLayer(DBRawInterface $lowerLayer): void
    {
        $this->lowerLayer = $lowerLayer;
    }
}
