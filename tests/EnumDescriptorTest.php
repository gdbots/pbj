<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\EnumDescriptor;

class EnumDescriptorTest extends \PHPUnit_Framework_TestCase
{
    /** EnumDescriptor */
    protected $enum;

    public function setUp()
    {
        $this->enum = new EnumDescriptor('number', 'int', [1, 2, 3, 4]);
    }

    public function tearDown()
    {
        $this->enum = null;
    }

    public function testGetName()
    {
        $this->assertEquals('number', $this->enum->getName());
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
