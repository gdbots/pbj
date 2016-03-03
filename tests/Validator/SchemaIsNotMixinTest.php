<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaIsNotMixin;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaIsNotMixinTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');

        $asset = new SchemaIsNotMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', ['is-mixin' => true]);

        $asset = new SchemaIsNotMixin();
        $asset->validate($a, $b);
    }
}
