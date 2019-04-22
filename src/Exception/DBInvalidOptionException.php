<?php

namespace Squirrel\Queries\Exception;

use Squirrel\Queries\DBException;

/**
 * Incorrect option was used - invalid argument, invalid usage, invalid range, or something
 * similar that this library can determine on its own
 */
class DBInvalidOptionException extends DBException
{
}
