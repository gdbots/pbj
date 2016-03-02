<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\FieldGreaterOrEqualThan;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldGreaterOrEqualThanTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateNoConfig()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
            ]),
        ]);

        $asset = new FieldGreaterOrEqualThan();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
                'max' => 100,
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
                'max' => 100,
            ]),
        ]);

        $asset = new FieldGreaterOrEqualThan();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    public function testValidateLessThan()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
                'max' => 100,
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
                'max' => 1000,
            ]),
        ]);

        $asset = new FieldGreaterOrEqualThan();
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
                'type' => 'int',
                'max' => 100,
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'int',
                'max' => 10,
            ]),
        ]);

        $asset = new FieldGreaterOrEqualThan();
        $asset->validate($a, $b);
    }
}
