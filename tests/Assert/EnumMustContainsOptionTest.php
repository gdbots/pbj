<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\EnumMustContainsOption;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class EnumMustContainsOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op2',
            'op3'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op2',
            'op3'
        ]));

        $asset = new EnumMustContainsOption();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateAddon()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op2',
            'op3'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op2',
            'op3',
            'op4',
            'op5'
        ]));

        $asset = new EnumMustContainsOption();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op2',
            'op3'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addEnum(new EnumDescriptor('e1', 'string', [
            'op1',
            'op3'
        ]));

        $asset = new EnumMustContainsOption();
        $asset->validate($a, $b);
    }
}
