<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\EnumDescriptor;

class EnumDescriptorTest extends \PHPUnit_Framework_TestCase
{
    /** EnumDescriptor */
    protected $enum;

    public function setUp()
    {
        $this->enum = new EnumDescriptor('vendor:package:number', 'int', [1, 2, 3, 4]);
    }

    public function tearDown()
    {
        $this->enum = null;
    }

    public function testGetId()
    {
        $this->assertEquals('vendor:package:number', $this->enum->getId()->toString());
    }

    public function testGetType()
    {
        $this->assertEquals('int', $this->enum->getType());
    }

    public function testGetValues()
    {
        $this->assertEquals([1, 2, 3, 4], $this->enum->getValues());
    }
}
