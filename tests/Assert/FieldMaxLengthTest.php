<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\FieldMaxLength;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMaxLengthTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateNoConfig()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $asset = new FieldMaxLength();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'max_length' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'max_length' => 100
        ]));

        $asset = new FieldMaxLength();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateGreaterThan()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'max_length' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'max_length' => 1000
        ]));

        $asset = new FieldMaxLength();
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
            'max_length' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string',
            'max_length' => 10
        ]));

        $asset = new FieldMaxLength();
        $asset->validate($a, $b);
    }
}
