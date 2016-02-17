<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Assert\FieldLessOrEqualThan;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldLessOrEqualThanTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateNoConfig()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'int'
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'int'
        ]));

        $asset = new FieldLessOrEqualThan();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'int',
            'min' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'int',
            'min' => 100
        ]));

        $asset = new FieldLessOrEqualThan();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }


    public function testValidateLessThan()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0');
        $a->addField(new FieldDescriptor('f1', [
            'type' => 'int',
            'min' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'int',
            'min' => 10
        ]));

        $asset = new FieldLessOrEqualThan();
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
            'type' => 'int',
            'min' => 100
        ]));

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1');
        $b->addField(new FieldDescriptor('f1', [
            'type' => 'int',
            'min' => 1000
        ]));

        $asset = new FieldLessOrEqualThan();
        $asset->validate($a, $b);
    }
}
