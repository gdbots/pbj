<?php

namespace Gdbots\Tests\Pbjc\Validator;

use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Validator\SchemaIsMixin;

class SchemaIsMixinTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', ['is-mixin' => true]);
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', ['is-mixin' => true]);

        $asset = new SchemaIsMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', ['is-mixin' => true]);
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');

        $asset = new SchemaIsMixin();
        $asset->validate($a, $b);
    }
}
