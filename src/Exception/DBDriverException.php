<?php

namespace Squirrel\Queries\Exception;

use Squirrel\Queries\DBException;

/**
 * Driver is responsible for the exception, so probably a query went wrong (faulty SQL)
 */
class DBDriverException extends DBException
{
}
