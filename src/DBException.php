<?php

namespace Squirrel\Queries;

/**
 * Database exception with details on which query caused it
 */
class DBException extends \Exception
{
    /**
     * Query which led to the DB exception
     *
     * @var string
     */
    private $sqlCmd = '';

    /**
     * File in which the problematic query was located
     *
     * @var string
     */
    private $sqlFile = '';

    /**
     * Line on which the problematic query was located
     *
     * @var string
     */
    private $sqlLine = '';

    /**
     * @param string $sqlCmd Query which led to the DB exception
     * @param string $sqlFile File in which the problematic query was located
     * @param string $sqlLine Line on which the problematic query was located
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $sqlCmd,
        string $sqlFile,
        string $sqlLine,
        $message = "",
        $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->sqlCmd = $sqlCmd;
        $this->sqlFile = $sqlFile;
        $this->sqlLine = $sqlLine;
    }

    /**
     * @return string
     */
    public function getSqlCmd(): string
    {
        return $this->sqlCmd;
    }

    /**
     * @return string
     */
    public function getSqlFile(): string
    {
        return $this->sqlFile;
    }

    /**
     * @return string
     */
    public function getSqlLine(): string
    {
        return $this->sqlLine;
    }
}
