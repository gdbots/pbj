<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaMixinsMustContainsMixin;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMixinsMustContainsMixinTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $m = new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0');
        $m->setIsMixin(true);

        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addMixin($m);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addMixin($m);

        $asset = new SchemaMixinsMustContainsMixin();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $m = new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0');
        $m->setIsMixin(true);

        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addMixin($m);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addMixin(new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'));

        $asset = new SchemaMixinsMustContainsMixin();
        $asset->validate($a, $b);
    }
}
