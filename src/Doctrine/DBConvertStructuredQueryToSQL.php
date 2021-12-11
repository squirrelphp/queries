<?php

namespace Squirrel\Queries\Doctrine;

use Squirrel\Debug\Debug;
use Squirrel\Queries\DBInterface;
use Squirrel\Queries\Exception\DBInvalidOptionException;
use Squirrel\Queries\LargeObject;

/**
 * Converts parts of a structured query to pure (and safe) SQL
 */
class DBConvertStructuredQueryToSQL
{
    /**
     * @var callable Function to quote an identifier (table name or field name)
     */
    private $quoteIdentifier;

    /**
     * @var callable Function to quote table and field names in an expression
     */
    private $quoteExpression;

    public function __construct(callable $quoteIdentifier, callable $quoteExpression)
    {
        $this->quoteIdentifier = $quoteIdentifier;
        $this->quoteExpression = $quoteExpression;
    }

    /**
     * Process options and make sure all values are valid
     *
     * @param array $validOptions List of valid options and default values for them
     * @param array $options List of provided options which need to be processed
     */
    public function verifyAndProcessOptions(array $validOptions, array $options): array
    {
        // Convert "table" to "tables"
        if (
            isset($options['table'])
            && !isset($options['tables'])
            && isset($validOptions['tables'])
        ) {
            $options['tables'] = [$options['table']];
            unset($options['table']);
        }

        // Convert "field" to "fields"
        if (
            isset($options['field'])
            && !isset($options['fields'])
            && isset($validOptions['fields'])
        ) {
            $options['fields'] = [$options['field']];
            unset($options['field']);
        }

        // Copy over the default valid options as a starting point for our options
        $sanitizedOptions = $validOptions;

        // Options were defined
        foreach ($options as $optKey => $optVal) {
            // Defined option is not in the list of valid options
            if (!isset($validOptions[$optKey])) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Unknown option key ' . Debug::sanitizeData($optKey),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Make sure the variable type for the defined option is valid
            switch ($optKey) {
                case 'lock':
                    // Conversion of value does not match the original value, so we have a very wrong type
                    if (!\is_bool($optVal) && $optVal !== 1 && $optVal !== 0) {
                        throw Debug::createException(
                            DBInvalidOptionException::class,
                            'Option key ' . Debug::sanitizeData($optKey)
                            . ' had an invalid value which cannot be converted correctly',
                            ignoreClasses: DBInterface::class,
                        );
                    }

                    $optVal = \boolval($optVal);
                    break;
                case 'limit':
                case 'offset':
                    // Conversion of value does not match the original value, so we have a very wrong type
                    if (
                        \is_bool($optVal)
                        || (
                            !\is_int($optVal)
                            && \strval(\intval($optVal)) !== \strval($optVal)
                        )
                    ) {
                        throw Debug::createException(
                            DBInvalidOptionException::class,
                            'Option key ' . Debug::sanitizeData($optKey) .
                            ' had an invalid value which cannot be converted correctly',
                            ignoreClasses: DBInterface::class,
                        );
                    }

                    $optVal = \intval($optVal);
                    break;
                default:
                    if (!\is_array($optVal)) {
                        throw Debug::createException(
                            DBInvalidOptionException::class,
                            'Option key ' . Debug::sanitizeData($optKey) . ' had a non-array value',
                            ignoreClasses: DBInterface::class,
                        );
                    }
                    break;
            }

            $sanitizedOptions[$optKey] = $optVal;
        }

        // Make sure tables array was defined
        if (!isset($sanitizedOptions['tables']) || \count($sanitizedOptions['tables']) === 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                'No tables specified for query',
                ignoreClasses: DBInterface::class,
            );
        }

        // Limit must be a positive integer if defined
        if (isset($validOptions['limit']) && $sanitizedOptions['limit'] < 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                'Below zero "limit" definition',
                ignoreClasses: DBInterface::class,
            );
        }

        // Offset must be a positive integer if defined
        if (isset($validOptions['offset']) && $sanitizedOptions['offset'] < 0) {
            throw Debug::createException(
                DBInvalidOptionException::class,
                'Below zero "offset" definition',
                ignoreClasses: DBInterface::class,
            );
        }

        // Return all processed options and object-to-table information
        return $sanitizedOptions;
    }

    /**
     * Build fields selection part of the query (for SELECT)
     */
    public function buildFieldSelection(array $fields): string
    {
        // No fields mean we select all fields!
        if (\count($fields) === 0) {
            return '*';
        }

        // Calculated select fields
        $fieldSelectionList = [];

        // Go through all the select fields
        foreach ($fields as $name => $field) {
            // Field always has to be a string
            if (!\is_string($field)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "fields" definition, value for ' .
                    Debug::sanitizeData($name) . ' is not a string',
                    ignoreClasses: DBInterface::class,
                );
            }

            // No expressions allowed in name part!
            if (!\is_int($name) && \str_contains($name, ':')) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "fields" definition, name ' .
                    Debug::sanitizeData($name) . ' contains a colon',
                    ignoreClasses: DBInterface::class,
                );
            }

            // Whether this was an expression (according to special characters found)
            $isExpression = false;

            if (
                \str_contains($field, ':')
                || \str_contains($field, ' ')
                || \str_contains($field, '(')
                || \str_contains($field, ')')
                || \str_contains($field, '*')
            ) { // Special characters found, so this is an expression
                $fieldProcessed = (\str_contains($field, ':') ? ($this->quoteExpression)($field) : $field);

                // This is now a special expression
                $isExpression = true;
            } else { // No colons, we assume it is just a field definition to escape - no expression
                $fieldProcessed = ($this->quoteIdentifier)($field);
            }

            // If no (unique) name was given, we always just use the processed field as is
            if (\is_int($name) || $name === $field) {
                $fieldSelectionList[] = $fieldProcessed;
            } else { // Explicit name different from field name was given
                if ($isExpression) {
                    $fieldSelectionList[] = '(' . $fieldProcessed . ') AS "' . $name . '"';
                } else {
                    $fieldSelectionList[] = $fieldProcessed . ' AS "' . $name . '"';
                }
            }
        }

        return \implode(',', $fieldSelectionList);
    }

    /**
     * Build FROM or UPDATE part for the query (both are built the same)
     */
    public function buildTableJoins(array $tables): array
    {
        // List of query values for PDO
        $queryValues = [];

        // List of table selection, needs to be imploded with a comma for SQL query
        $joinedTables = [];

        // Go through table selection
        foreach ($tables as $expression => $values) {
            // No values, only an expression
            if (\is_int($expression)) {
                $expression = $values;
                $values = [];
            }

            // Expression always has to be a string
            if (!\is_string($expression)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "tables" definition, expression is not a string: ' .
                    Debug::sanitizeData($expression),
                    ignoreClasses: DBInterface::class,
                );
            }

            // No variable expression with colons
            if (\str_contains($expression, ':') === false) {
                // Count number of spaces in expression
                $spacesNumber = \substr_count($expression, ' ');

                if ($spacesNumber === 0) { // No space found, we assume it is a pure table name
                    $expression = ($this->quoteIdentifier)($expression);
                } elseif ($spacesNumber === 1) { // One space found, we assume table name + alias
                    $expression = \implode(' ', \array_map($this->quoteIdentifier, \explode(' ', $expression)));
                }

                // Everything else is left as-is - maybe an expression or something we do not understand
            } else { // An expression with : variables
                $expression = ($this->quoteExpression)($expression);
            }

            // Add to list of joined tables
            $joinedTables[] = $expression;

            // Add new parameters to query parameters
            $queryValues = $this->addQueryVariablesNoNull($queryValues, $values);
        }

        return [\implode(',', $joinedTables), $queryValues];
    }

    /**
     * Build UPDATE SET clause and add query values
     */
    public function buildChanges(array $changes, array $queryValues): array
    {
        // List of finished change expressions, to be imploded with ,
        $changesList = [];

        // Go through table selection
        foreach ($changes as $expression => $values) {
            if (\is_int($expression)) {
                $expression = $values;
                $values = [];
            }

            // Expression always has to be a string
            if (!\is_string($expression)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "changes" definition, expression is not a string: ' . Debug::sanitizeData($expression),
                    ignoreClasses: DBInterface::class,
                );
            }

            // No assignment operator, meaning we have a fieldName => value entry
            if (\str_contains($expression, '=') === false) {
                // No value was given, we just have a field name without new value
                if (\is_array($values) && \count($values) === 0) {
                    throw Debug::createException(
                        DBInvalidOptionException::class,
                        'Invalid "changes" definition, no value specified: ' .
                        Debug::sanitizeData($expression) . ' => ' . Debug::sanitizeData($values),
                        ignoreClasses: DBInterface::class,
                    );
                }

                // Colons are not allowed in a variable name
                if (\str_contains($expression, ':')) {
                    throw Debug::createException(
                        DBInvalidOptionException::class,
                        'Invalid "changes" definition, colon used in a field name ' .
                        'to value assignment: ' . Debug::sanitizeData($expression),
                        ignoreClasses: DBInterface::class,
                    );
                }

                // Simple assignment expression
                $expression = ($this->quoteIdentifier)($expression) . '=?';
            } else { // Assignment operator exists in expression
                // Process variables if any exist in the string
                if (\str_contains($expression, ':')) {
                    $expression = ($this->quoteExpression)($expression);
                }
            }

            // Add to list of finished WHERE expressions
            $changesList[] = $expression;

            // Skip this entry for values - this is just an expression
            if (\is_array($values) && \count($values) === 0) {
                continue;
            }

            // Add new parameters to query parameters - Only scalar values and NULL are allowed
            if (!\is_scalar($values) && !\is_null($values) && !($values instanceof LargeObject)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid query variable specified, it is non-scalar and no large object: ' .
                    Debug::sanitizeData($expression) . ' => ' . Debug::sanitizeData($values),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Convert bool to int
            if (\is_bool($values)) {
                $values = \intval($values);
            }

            $queryValues[] = $values;
        }

        return [\implode(',', $changesList), $queryValues];
    }

    /**
     * Build WHERE clause and add query values
     */
    public function buildWhere(array $whereOptions, array $queryValues = []): array
    {
        // If no WHERE restrictions are defined, we just do "WHERE 1"
        if (\count($whereOptions) === 0) {
            return ['1', $queryValues];
        }

        // List of finished WHERE expressions, to be imploded with ANDs
        $whereProcessed = [];

        // Go through table selection
        foreach ($whereOptions as $expression => $values) {
            // Switch around expression and values if there are no values
            if (\is_int($expression)) {
                $expression = $values;
                $values = [];
            }

            // Expression always has to be a string
            if (!\is_string($expression)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "where" definition, expression is not a string: ' .
                    Debug::sanitizeData($expression),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Check if this is a custom expression, not just a field name to value expression
            if (
                \str_contains($expression, ' ')
                || \str_contains($expression, '=')
                || \str_contains($expression, '<')
                || \str_contains($expression, '>')
                || \str_contains($expression, '(')
                || \str_contains($expression, ')')
            ) {
                // Colons found, which are used to escape variables
                if (\str_contains($expression, ':')) {
                    $expression = ($this->quoteExpression)($expression);
                }

                // Add to list of finished WHERE expressions
                $whereProcessed[] = '(' . $expression . ')';
            } else { // We assume just a field name to value(s) expression
                // Values have to be defined for us to make a predefined equals query
                if (\is_array($values) && \count($values) === 0) {
                    throw Debug::createException(
                        DBInvalidOptionException::class,
                        'Invalid "where" definition, simple expression has no values: ' .
                        Debug::sanitizeData($expression),
                        ignoreClasses: DBInterface::class,
                    );
                }

                // Special case for NULL - then we need the IS NULL expression
                if (\is_null($values)) {
                    $expression = ($this->quoteIdentifier)($expression) . ' IS NULL';
                    $values = [];
                } elseif (\is_array($values) && \count($values) > 1) { // Array values => IN where query
                    $expression = ($this->quoteIdentifier)($expression) .
                        ' IN (' . \implode(',', \array_fill(0, \count($values), '?')) . ')';
                } else { // Scalar value, so we do a regular equal query
                    $expression = ($this->quoteIdentifier)($expression) . '=?';
                }

                // Add to list of finished WHERE expressions
                $whereProcessed[] = $expression;
            }

            // Add new parameters to query parameters
            $queryValues = $this->addQueryVariablesNoNull($queryValues, $values);
        }

        return [\implode(' AND ', $whereProcessed), $queryValues];
    }

    /**
     * Build GROUP BY clause
     */
    public function buildGroupBy(array $groupByOptions): string
    {
        // List of finished WHERE expressions, to be imploded with ANDs
        $groupByProcessed = [];

        // Go through table selection
        foreach ($groupByOptions as $expression => $values) {
            // Switch around expression and values if there are no values
            if (\is_int($expression)) {
                $expression = $values;
                $values = null;
            }

            // Expression always has to be a string
            if (!\is_string($expression)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "group" definition, expression is not a string: ' .
                    Debug::sanitizeData($expression),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Add to list of finished expressions
            $groupByProcessed[] = ($this->quoteIdentifier)($expression);
        }

        return \implode(',', $groupByProcessed);
    }

    /**
     * Build ORDER BY clause
     */
    public function buildOrderBy(array $orderOptions): string
    {
        // List of finished WHERE expressions, to be imploded with ANDs
        $orderProcessed = [];

        // Go through table selection
        foreach ($orderOptions as $expression => $order) {
            // If there is no explicit order we set it to ASC
            if (\is_int($expression)) {
                $expression = $order;
                $order = 'ASC';
            }

            // Expression always has to be a string
            if (!\is_string($expression)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "order" definition, expression is not a string: ' .
                    Debug::sanitizeData($expression),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Make sure the order is ASC or DESC - nothing else is allowed
            if (!\is_string($order) || ($order !== 'ASC' && $order !== 'DESC')) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid "order" definition, order is not ASC or DESC: ' .
                    Debug::sanitizeData($order),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Wether variable was found or not
            $variableFound = \str_contains($expression, ':');

            // Expression contains not just the field name
            if (
                $variableFound === true
                || \str_contains($expression, ' ')
                || \str_contains($expression, '(')
                || \str_contains($expression, ')')
            ) {
                if ($variableFound === true) {
                    $expression = ($this->quoteExpression)($expression);
                }
            } else { // Expression is just a field name
                $expression = ($this->quoteIdentifier)($expression);
            }

            $orderProcessed[] = $expression . ' ' . $order;
        }

        return \implode(',', $orderProcessed);
    }

    /**
     * Add query variables to existing values - but NULL is not allowed as a value
     */
    private function addQueryVariablesNoNull(array $existingValues, mixed $newValues): array
    {
        // Convert to array of values if not already done
        if (!\is_array($newValues)) {
            $newValues = [$newValues];
        }

        // Add all the values to the query values
        foreach ($newValues as $value) {
            // Only scalar values are allowed
            if (!\is_scalar($value)) {
                throw Debug::createException(
                    DBInvalidOptionException::class,
                    'Invalid query variable specified, it is non-scalar: ' .
                    Debug::sanitizeData($newValues),
                    ignoreClasses: DBInterface::class,
                );
            }

            // Convert bool to int
            if (\is_bool($value)) {
                $value = \intval($value);
            }

            $existingValues[] = $value;
        }

        return $existingValues;
    }
}
