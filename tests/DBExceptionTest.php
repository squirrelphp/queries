<?php

namespace Squirrel\Queries\Tests;

use Squirrel\Queries\DBException;

class DBExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * DBException - test that all the variables are correctly processed in the exception
     */
    public function test()
    {
        // Values for the exception
        $sqlCmd = 'SELECT FROM';
        $sqlFile = 'dada.php';
        $sqlLine = '748';
        $message = 'Exception occured with query';
        $code = 75;

        // Previous exception
        $e = new \Exception('Previous exception', 88);

        // Create DBException
        $dbE = new DBException($sqlCmd, $sqlFile, $sqlLine, $message, $code, $e);

        // Test all the values we provided
        $this->assertSame($sqlCmd, $dbE->getSqlCmd());
        $this->assertSame($sqlFile, $dbE->getSqlFile());
        $this->assertSame($sqlLine, $dbE->getSqlLine());
        $this->assertSame($message, $dbE->getMessage());
        $this->assertSame($code, $dbE->getCode());
        $this->assertSame($e, $dbE->getPrevious());
    }
}
