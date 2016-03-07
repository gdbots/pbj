<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    public function testAddDir()
    {
        SchemaStore::addDir(__DIR__.'/../examples/schemas');

        $this->assertCount(1, SchemaStore::getDirs());
    }

    public function testAddSchema()
    {
        $schema100 = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $schema101 = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $schema200 = new SchemaDescriptor('pbj:vendor:package:category:message:2-0-0');

        SchemaStore::addSchema($schema100->getId(), $schema100);
        SchemaStore::addSchema($schema101->getId(), $schema101);
        SchemaStore::addSchema($schema200->getId(), $schema200);

        $this->assertEquals(SchemaStore::getSchemaById('pbj:vendor:package:category:message:1-0-0'), $schema100);
        $this->assertEquals(SchemaStore::getPreviousSchema($schema101->getId()), $schema100);
        $this->assertTrue(SchemaStore::hasOtherSchemaMajorRev($schema100->getId()));
        $this->assertEquals(SchemaStore::getOtherSchemaMajorRev($schema101->getId()), [$schema101, $schema200]);
    }

    public function testAddEnum()
    {
        $enum = new EnumDescriptor('vendor:package:number', 'int', [1, 2, 3, 4]);

        SchemaStore::addEnum($enum->getId(), $enum);

        $this->assertEquals(SchemaStore::getEnumById('vendor:package:number'), $enum);
    }
}
