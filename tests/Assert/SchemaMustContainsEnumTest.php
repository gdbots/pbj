<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Assert\SchemaMustContainsEnum;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMustContainsEnumTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', []));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e1', 'string', []));

        $asset = new SchemaMustContainsEnum();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateAddon()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', []));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e1', 'string', []));
        $b->addEnum(new EnumDescriptor('e2', 'int', []));

        $asset = new SchemaMustContainsEnum();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', []));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e2', 'int', []));

        $asset = new SchemaMustContainsEnum();
        $asset->validate($a, $b);
    }
}
