<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\Field;
use Gdbots\Pbjc\Schema;
use Gdbots\Pbjc\SchemaStore;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    /** Schema */
    protected $schema;

    public function setUp()
    {
        $this->schema = new Schema(
            // id
            'pbj:gdbots:pbj:mixin:command:1-0-1',

            // fields
            [
                Field::fromArray('command_id', [
                    'type' => 'time-uuid',
                    'required' => true
                ]),
                Field::fromArray('microtime', [
                    'type' => 'microtime',
                    'required' => true
                ]),
                Field::fromArray('correlator', [
                    'type' => 'message-ref'
                ]),
                Field::fromArray('retries', [
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
        SchemaStore::addDir(__DIR__.'/Fixtures/schemas/pbj/mixin/command');
        SchemaStore::addDir(__DIR__.'/Fixtures/schemas/pbj/mixin/entity');

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
