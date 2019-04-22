<?php

namespace Squirrel\Queries;

/**
 * Type hint for select query functions - it is empty because no functions should be
 * called on these select results, instead they are only to be provided back to the
 * DBInterface to fetch results
 */
interface DBSelectQueryInterface
{
}
