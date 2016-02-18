<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\FieldSameEnum;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldSameEnumTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'enum' => new EnumDescriptor('e1', 'string', [])
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'enum' => new EnumDescriptor('e1', 'string', [])
        ]));

        $asset = new FieldSameEnum();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'enum' => new EnumDescriptor('e1', 'string', [])
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'enum' => new EnumDescriptor('e2', 'string', [])
        ]));

        $asset = new FieldSameEnum();
        $asset->validate($a, $b);
    }
}
