<?php

namespace Squirrel\Queries\Tests;

use Squirrel\Queries\DBDebug;
use Squirrel\Queries\DBException;
use Squirrel\Queries\Exception\DBInvalidOptionException;
use Squirrel\Queries\Tests\ExceptionTestClasses\NoExceptionClass;
use Squirrel\Queries\Tests\ExceptionTestClasses\SomeRepository;

class DBDebugTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateException()
    {
        $someRepository = new SomeRepository();

        try {
            $someRepository->someFunction();

            $this->assertFalse(true);
        } catch (DBInvalidOptionException $e) {
            $this->assertEquals('Something went wrong!', $e->getMessage());
            $this->assertEquals(__FILE__, $e->getSqlFile());
            $this->assertEquals(__LINE__-6, $e->getSqlLine());
            $this->assertEquals('SomeRepository->someFunction()', $e->getSqlCmd());
        }
    }

    public function testInvalidExceptionClass()
    {
        $exception = DBDebug::createException(NoExceptionClass::class, [], 'Something went wrong!');

        $this->assertEquals(\Exception::class, \get_class($exception));
    }

    public function testBaseExceptionClass()
    {
        $exception = DBDebug::createException(DBException::class, [], 'Something went wrong!');

        $this->assertEquals(DBException::class, \get_class($exception));
    }

    public function testBinaryData()
    {
        $sanitizedData = DBDebug::sanitizeData(\md5('dada', true));

        $this->assertEquals('0x' . \bin2hex(\md5('dada', true)), $sanitizedData);
    }

    public function testBoolDataTrue()
    {
        $sanitizedData = DBDebug::sanitizeData(true);

        $this->assertEquals('true', $sanitizedData);
    }

    public function testBoolDataFalse()
    {
        $sanitizedData = DBDebug::sanitizeData(false);

        $this->assertEquals('false', $sanitizedData);
    }

    public function testObjectData()
    {
        $sanitizedData = DBDebug::sanitizeData(new SomeRepository());

        $this->assertEquals('object(' . SomeRepository::class . ')', $sanitizedData);
    }

    public function testArrayData()
    {
        $sanitizedData = DBDebug::sanitizeData([
            'dada',
            'mumu' => 'haha',
            5444,
            [
                'ohno' => 'yes',
                'maybe',
            ],
        ]);

        $this->assertEquals("[0 => 'dada', 'mumu' => 'haha', 1 => 5444, 2 => ['ohno' => 'yes', 0 => 'maybe']]", $sanitizedData);
    }

    public function testResourceData()
    {
        $sanitizedData = DBDebug::sanitizeData(\fopen("php://memory", "r"));

        $this->assertEquals("resource(stream)", $sanitizedData);
    }

    public function testSanitizeArguments()
    {
        $sanitizedData = DBDebug::sanitizeArguments([
            'hello',
            [
                'my',
                'weird' => 'friend',
            ],
            56
        ]);

        $this->assertEquals("'hello', [0 => 'my', 'weird' => 'friend'], 56", $sanitizedData);
    }
}
