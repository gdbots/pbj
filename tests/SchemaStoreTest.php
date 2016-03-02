<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    /** SchemaDescriptor */
    protected $schema;

    public function setUp()
    {
        $this->schema = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
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
        SchemaStore::addSchema($this->schema->getId(), $this->schema, true);

        $this->assertEquals(SchemaStore::getSchemaById('pbj:vendor:package:category:message:1-0-0'), $this->schema);
    }
}
