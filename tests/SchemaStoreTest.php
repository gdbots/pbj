<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\Descriptor\FieldDescriptor;
use Gdbots\Pbjc\Descriptor\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    /** Schema */
    protected $schema;

    public function setUp()
    {
        $this->schema = new SchemaDescriptor(
            // id
            'pbj:gdbots:pbj:mixin:command:1-0-1',

            // fields
            [
                new FieldDescriptor('command_id', [
                    'type' => 'time-uuid',
                    'required' => true
                ]),
                new FieldDescriptor('microtime', [
                    'type' => 'microtime',
                    'required' => true
                ]),
                new FieldDescriptor('correlator', [
                    'type' => 'message-ref'
                ]),
                new FieldDescriptor('retries', [
                    'type' => 'tiny-int'
                ])
            ],

            // mixins
            [],

            // languages
            [
                'php' => [
                    'namespace' => 'Gdbots\Schemas\Pbj\Command'
                ]
            ]
        );
    }

    public function tearDown()
    {
        $this->schema = null;
    }

    public function testAddDir()
    {
        SchemaStore::addDir(__DIR__.'/../vendor/gdbots/schemas/schemas/gdbots/pbj');
        SchemaStore::addDir(__DIR__.'/../vendor/gdbots/schemas/schemas/gdbots/pbjx');

        $this->assertCount(2, SchemaStore::getDirs());
    }

    public function testAddSchema()
    {
        SchemaStore::addSchema($this->schema->__toString(), $this->schema, true);

        $this->assertEquals(SchemaStore::getSchemaById('pbj:gdbots:pbj:mixin:command:1-0-1'), $this->schema);
    }

    /**
     * @depends testAddSchema
     * @expectedException RuntimeException
     *
     * @param Schema $schema
     */
    public function testAddDuplicateSchema()
    {
        SchemaStore::addSchema($this->schema->__toString(), $this->schema);
    }
}
