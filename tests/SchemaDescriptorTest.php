<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaDescriptorTest extends \PHPUnit_Framework_TestCase
{
    /** FieldDescriptor */
    protected $schema;

    public function setUp()
    {
        $this->schema = new SchemaDescriptor(
            'pbj:vendor:package:category:message:1-0-0',
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
            [
                new FieldDescriptor('first_name', [
                    'type' => 'string',
                ]),
                new FieldDescriptor('last_name', [
                    'type' => 'string',
                ]),
            ],
            [
                new SchemaDescriptor(
                    'pbj:vendor2:package2:category2:message2:1-0-0',
                    null,
                    [
                        'created_at' => new FieldDescriptor('created_at', [
                            'type' => 'microtime',
                        ]),
                    ]
                ),
            ]
        );
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
