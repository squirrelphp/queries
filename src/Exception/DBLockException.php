<?php

namespace Squirrel\Queries\Exception;

use Squirrel\Queries\DBException;

/**
 * Deadlock or some other lock could not be resolved
 */
class DBLockException extends DBException
{
}
