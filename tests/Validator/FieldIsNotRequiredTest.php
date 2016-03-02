<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\FieldIsNotRequired;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldIsNotRequiredTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
            ])
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
            ])
        ]);

        $asset = new FieldIsNotRequired();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
            ])
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
                'required' => true,
            ])
        ]);

        $asset = new FieldIsNotRequired();
        $asset->validate($a, $b);
    }
}
