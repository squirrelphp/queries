<?php

namespace Squirrel\Queries\Tests;

use Squirrel\Queries\LargeObject;

class LargeObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testObject(): void
    {
        $obj = new LargeObject('some string');

        $this->assertEquals('some string', $obj->getString());
        $this->assertEquals(true, \is_resource($obj->getStream()));
        $this->assertEquals('some string', \stream_get_contents($obj->getStream()));
    }
}
