<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Assert\FieldIsRequired;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldIsRequiredTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'required' => true
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'required' => true
        ]));

        $asset = new FieldIsRequired();
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
            'required' => true
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $asset = new FieldIsRequired();
        $asset->validate($a, $b);
    }
}
