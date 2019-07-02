<?php

namespace Squirrel\Queries\Tests\ExceptionTestClasses;

use Squirrel\Queries\DBDebug;
use Squirrel\Queries\Exception\DBInvalidOptionException;

class SomeRepository
{
    public function someFunction()
    {
        return \array_map(function () {
            throw DBDebug::createException(DBInvalidOptionException::class, [SomeRepository::class], 'Something went wrong!', null);
        }, ['dada','mumu']);
    }
}
