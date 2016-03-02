<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaMixinsMustContainsMixin;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMixinsMustContainsMixinTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $m = new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0', null, [], [], null, true);

        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [], [$m]);
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [], [$m]);

        $asset = new SchemaMixinsMustContainsMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0', null, [], [], null, true)
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [], [
            new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0')
        ]);

        $asset = new SchemaMixinsMustContainsMixin();
        $asset->validate($a, $b);
    }
}
