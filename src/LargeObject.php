<?php

namespace Squirrel\Queries;

/**
 * Large object - mainly necessary for Postgres, to support correctly saving BYTEA values
 */
class LargeObject
{
    /**
     * @var string
     */
    private $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getString(): string
    {
        return $this->data;
    }

    public function getStream()
    {
        /**
         * @var resource $fp
         */
        $fp = \fopen('php://temp', 'rb+');
        \fwrite($fp, $this->data);
        \fseek($fp, 0);
        return $fp;
    }
}
