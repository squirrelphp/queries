<?php

namespace Squirrel\Queries;

/**
 * Extends DBInterface with access to the "raw" connection underneath
 * in order to add functionality and build layers of DBInterface classes
 *
 * Only use this interface if you need to add layers to existing
 * connections - otherwise only DBInterface should be used
 */
interface DBRawInterface extends DBInterface
{
    /**
     * Set "we are within a transaction" to either true or false
     *
     * @param bool $inTransaction
     */
    public function setTransaction(bool $inTransaction): void;

    /**
     * Set DBRawInterface layer beneath the current class, in order to build layers
     * of classes implementing DBRawInterface and which serve different purposes
     *
     * @param object $lowerLayer
     */
    public function setLowerLayer($lowerLayer): void;
}
