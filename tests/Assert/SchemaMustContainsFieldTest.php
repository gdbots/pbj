<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaMustContainsField;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMustContainsFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $asset = new SchemaMustContainsField();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateAddon()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'string'
        ]));
        $b->addField(new FieldDescriptor('f2', [
            'type' => 'string'
        ]));

        $asset = new SchemaMustContainsField();
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
            'type' => 'string'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f2', [
            'type' => 'string'
        ]));

        $asset = new SchemaMustContainsField();
        $asset->validate($a, $b);
    }
}
