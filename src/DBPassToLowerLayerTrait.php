<?php

namespace Squirrel\Queries;

/**
 * Default implementation of all DBRawInterface functions to pass them to lower layer
 */
trait DBPassToLowerLayerTrait
{
    /**
     * @var DBRawInterface
     */
    private $lowerLayer;

    /**
     * @inheritDoc
     */
    public function transaction(callable $func, ...$arguments)
    {
        return $this->lowerLayer->transaction($func, ...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
    {
        return $this->lowerLayer->inTransaction();
    }

    /**
     * @inheritDoc
     */
    public function select(string $query, array $vars = []): DBSelectQueryInterface
    {
        return $this->lowerLayer->select($query, $vars);
    }

    /**
     * @inheritDoc
     */
    public function fetch(DBSelectQueryInterface $selectQuery): ?array
    {
        return $this->lowerLayer->fetch($selectQuery);
    }

    /**
     * @inheritDoc
     */
    public function clear(DBSelectQueryInterface $selectQuery): void
    {
        $this->lowerLayer->clear($selectQuery);
    }

    /**
     * @inheritDoc
     */
    public function fetchOne(string $query, array $vars = []): ?array
    {
        return $this->lowerLayer->fetchOne($query, $vars);
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(string $query, array $vars = []): array
    {
        return $this->lowerLayer->fetchAll($query, $vars);
    }

    /**
     * @inheritDoc
     */
    public function insert(string $tableName, array $row = [], string $autoIncrementIndex = ''): ?string
    {
        return $this->lowerLayer->insert($tableName, $row, $autoIncrementIndex);
    }

    /**
     * @inheritDoc
     */
    public function insertOrUpdate(string $tableName, array $row = [], array $indexColumns = [], ?array $rowUpdates = null): void
    {
        $this->lowerLayer->insertOrUpdate($tableName, $row, $indexColumns, $rowUpdates);
    }

    /**
     * @inheritDoc
     */
    public function update(array $query): int
    {
        return $this->lowerLayer->update($query);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $tableName, array $where = []): int
    {
        return $this->lowerLayer->delete($tableName, $where);
    }

    /**
     * @inheritDoc
     */
    public function change(string $query, array $vars = []): int
    {
        return $this->lowerLayer->change($query, $vars);
    }

    /**
     * @inheritDoc
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->lowerLayer->quoteIdentifier($identifier);
    }

    /**
     * @inheritDoc
     */
    public function setTransaction(bool $inTransaction): void
    {
        $this->lowerLayer->setTransaction($inTransaction);
    }

    /**
     * @inheritDoc
     */
    public function getConnection(): object
    {
        return $this->lowerLayer->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function setLowerLayer(DBRawInterface $lowerLayer): void
    {
        $this->lowerLayer = $lowerLayer;
    }
}
