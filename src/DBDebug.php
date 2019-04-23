<?php

namespace Squirrel\Queries;

/**
 * Debug functionality: create exception, sanitize data
 */
class DBDebug
{
    /**
     * Create exception with correct backtrace
     *
     * @param string $exceptionClass
     * @param string|array $backtraceClasses
     * @param string $message
     * @return \Exception
     */
    public static function createException(string $exceptionClass, $backtraceClasses, string $message)
    {
        // Convert backtrace class to an array if it is a string
        if (\is_string($backtraceClasses)) {
            $backtraceClasses = [$backtraceClasses];
        }

        $assignedBacktraceClass = '';

        // Get backtrace to find out where the query error originated
        $backtraceList = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        // Where the DBInterface was called
        $lastInstance = null;

        // Go through backtrace and find the topmost caller
        foreach ($backtraceList as $backtrace) {
            // Replace backtrace instance if we find a valid class insance
            foreach ($backtraceClasses as $backtraceClass) {
                // Check if the class or interface we are looking for is implemented or used
                // by the current backtrace class
                if (\in_array($backtraceClass, \class_implements($backtrace['class'])) ||
                    \in_array($backtraceClass, \class_parents($backtrace['class'])) ||
                    $backtraceClass === $backtrace['class']
                ) {
                    $lastInstance = $backtrace;
                    $assignedBacktraceClass = $backtraceClass;
                }
            }

            // We reached the first non-DBInterface backtrace - we are at the top
            if ($lastInstance !== null) {
                if ($lastInstance !== $backtrace) {
                    break;
                }
            }
        }

        // Shorten the backtrace class to just the class name without namespace
        $parts = \explode('\\', $assignedBacktraceClass);
        $shownClass = \array_pop($parts);

        // Create our own exception with the additional data
        return new $exceptionClass(
            $shownClass . $lastInstance['type'] . $lastInstance['function'] .
            '(' . self::sanitizeArguments($lastInstance['args']) . ')',
            $lastInstance['file'],
            $lastInstance['line'],
            \str_replace("\n", ' ', $message)
        );
    }

    /**
     * Sanitize function arguments for showing what caused an exception
     *
     * @param array $args
     * @return string
     */
    public static function sanitizeArguments(array $args)
    {
        $result = array();

        // Go through all arguments and prepare them for output
        foreach ($args as $key => $value) {
            $result[] = \is_int($key) ? self::sanitizeData($value) : "'" . $key . "' => " . self::sanitizeData($value);
        }

        return \implode(', ', $result);
    }

    /**
     * Convert debug data into a sanitized string
     *
     * @param mixed $data
     * @return mixed
     */
    public static function sanitizeData($data)
    {
        // Convert object to class name
        if (\is_object($data)) {
            return 'object(' . (new \ReflectionClass($data))->getShortName() . ')';
        }

        // Convert resource to its type name
        if (\is_resource($data)) {
            return 'resource(' . \get_resource_type($data) . ')';
        }

        // Convert boolean to integer
        if (\is_bool($data)) {
            return \strtolower(\var_export($data, true));
        }

        // All other non-array values are fine
        if (!\is_array($data)) {
            return \str_replace("\n", '', \var_export($data, true));
        }

        // Go through all values in the array and process them recursively
        foreach ($data as $key => $value) {
            $formattedValue = self::sanitizeData($value);
            $result[] = \is_int($key) ? $formattedValue : "'" . $key . "' => " . $formattedValue;
        }

        return '[' . \implode(', ', $result ?? []) . ']';
    }
}
