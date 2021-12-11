<?php

namespace Squirrel\Queries\Builder;

use Squirrel\Debug\Debug;
use Squirrel\Queries\Exception\DBInvalidOptionException;

trait FlattenedFieldsWithTypeTrait
{
    /**
     * @return int[]
     */
    public function getFlattenedIntegerFields(): array
    {
        $values = $this->getFlattenedFields();

        foreach ($values as $key => $value) {
            if (!\is_integer($value) && !\is_string($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened integers requested, but not all values were int or string',
                    ignoreClasses: [BuilderInterface::class],
                );
            }

            // Convert non-int values which do not change when type casted
            if (
                !\is_integer($value)
                && \strval(\intval($value)) === \strval($value)
            ) {
                $values[$key] = \intval($value);
                continue;
            }

            if (!\is_int($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened integers requested, but not all values were integers',
                    ignoreClasses: [BuilderInterface::class],
                );
            }
        }

        return $values;
    }

    /**
     * @return float[]
     */
    public function getFlattenedFloatFields(): array
    {
        $values = $this->getFlattenedFields();

        foreach ($values as $key => $value) {
            if (\is_int($value)) {
                $values[$key] = \floatval($value);
                continue;
            }

            if (!\is_float($value) && !\is_string($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened floats requested, but not all values were float or string',
                    ignoreClasses: [BuilderInterface::class],
                );
            }

            // Convert non-float values which do not change when type casted
            if (
                !\is_float($value)
                && \strval(\floatval($value)) === \strval($value)
            ) {
                $values[$key] = \floatval($value);
                continue;
            }

            if (!\is_float($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened floats requested, but not all values were floats',
                    ignoreClasses: [BuilderInterface::class],
                );
            }
        }

        return $values;
    }

    /**
     * @return string[]
     */
    public function getFlattenedStringFields(): array
    {
        $values = $this->getFlattenedFields();

        foreach ($values as $key => $value) {
            // Integers and floats can be converted to strings without problems
            if (
                \is_int($value)
                || \is_float($value)
            ) {
                $values[$key] = \strval($value);
                continue;
            }

            if (!\is_string($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened strings requested, but not all values were strings',
                    ignoreClasses: [BuilderInterface::class],
                );
            }
        }

        return $values;
    }

    /**
     * @return bool[]
     */
    public function getFlattenedBooleanFields(): array
    {
        $values = $this->getFlattenedFields();

        foreach ($values as $key => $value) {
            // Convert non-boolean values which can reasonably be converted to boolean
            if (
                $value === 0
                || $value === '0'
            ) {
                $values[$key] = false;
                continue;
            } elseif (
                $value === 1
                || $value === '1'
            ) {
                $values[$key] = true;
                continue;
            }

            if (!\is_bool($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Flattened booleans requested, but not all values were booleans',
                    ignoreClasses: [BuilderInterface::class],
                );
            }
        }

        return $values;
    }
}
