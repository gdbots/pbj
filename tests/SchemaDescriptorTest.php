<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaDescriptorTest extends \PHPUnit_Framework_TestCase
{
    /** FieldDescriptor */
    protected $schema;

    public function setUp()
    {
        $this->schema = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $this->schema->setExtends(new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'));
        $this->schema->addField(new FieldDescriptor('first_name', [
            'type' => 'string'
        ]));
        $this->schema->addField(new FieldDescriptor('last_name', [
            'type' => 'string'
        ]));
        $this->schema->addEnum(new EnumDescriptor('number', 'int', [1, 2, 3, 4]));

        $mixin = new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0');
        $mixin->addField(new FieldDescriptor('created_at', [
            'type' => 'microtime'
        ]));
        $this->schema->addMixin($mixin);
    }

    public function tearDown()
    {
        $this->schema = null;
    }

    public function testGetId()
    {
        $this->assertEquals('pbj:vendor:package:category:message:1-0-0', $this->schema->getId());
    }

    public function testGetExtends()
    {
        $this->assertEquals('pbj:vendor2:package2:category2:message2:1-0-0', $this->schema->getExtends()->getId());
    }

    public function testGetField()
    {
        $this->assertEquals('first_name', $this->schema->getField('first_name')->getName());
    }

    public function testGetFields()
    {
        $this->assertCount(2, $this->schema->getFields());
    }

    public function testGetInheritedFields()
    {
        $this->assertEquals(['created_at'], array_keys($this->schema->getInheritedFields()));
    }

    public function testGetEnum()
    {
        $this->assertEquals('number', $this->schema->getEnum('number')->getName());
    }

    public function testGetEnums()
    {
        $this->assertCount(1, $this->schema->getEnums());
    }

    public function testGetMixin()
    {
        $this->assertEquals('pbj:vendor2:package2:category2:message2:1-0-0', $this->schema->getMixin('vendor2:package2:category2:message2:v1')->getId());
    }

    public function testGetMixins()
    {
        $this->assertCount(1, $this->schema->getMixins());
    }

    public function isMixinSchema()
    {
        $this->assertFalse($this->schema->isMixinSchema());
    }

    public function testIsLatestVersion()
    {
        $this->assertFalse($this->schema->isLatestVersion());
    }
}
