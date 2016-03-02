<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaMustContainsMixin;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMustContainsMixinTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
        ]);

        $asset = new SchemaMustContainsMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateAddon()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
            new SchemaDescriptor('pbj:vendor3:package3:category3:message3:1-0-0'),
        ]);

        $asset = new SchemaMustContainsMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [], [
            new SchemaDescriptor('pbj:vendor3:package3:category3:message3:1-0-0'),
        ]);

        $asset = new SchemaMustContainsMixin();
        $asset->validate($a, $b);
    }
}
