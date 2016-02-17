<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    /** Schema */
    protected $schema;

    public function setUp()
    {
        $this->schema = new SchemaDescriptor('pbj:acme:blog:entity:comment:1-0-0');

        $this->schema->addField(new FieldDescriptor('_id', [
            'type' => 'identifier',
            'required' => true
        ]));

        $this->schema->addField(new FieldDescriptor('comment', [
            'type' => 'text',
            'required' => true
        ]));

        $this->schema->addField(new FieldDescriptor('published_at', [
            'type' => 'microtime'
        ]));
    }

    public function tearDown()
    {
        $this->schema = null;
    }

    public function testAddDir()
    {
        SchemaStore::addDir(__DIR__.'/../examples/schemas');

        $this->assertCount(1, SchemaStore::getDirs());
    }

    public function testAddSchema()
    {
        SchemaStore::addSchema($this->schema->__toString(), $this->schema, true);

        $this->assertEquals(SchemaStore::getSchemaById('pbj:acme:blog:entity:comment:1-0-0'), $this->schema);
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
