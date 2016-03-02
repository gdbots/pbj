<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\FieldMustContainsAnyOfClasses;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMustContainsAnyOfClassesTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
                'any_of' => [
                    new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
                ],
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
                'any_of' => [
                    new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
                ],
            ]),
        ]);

        $asset = new FieldMustContainsAnyOfClasses();
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
                'any_of' => [
                    new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0'),
                ],
            ]),
        ]);

        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', null, [
            new FieldDescriptor('f1', [
                'type' => 'string',
                'any_of' => [
                    new SchemaDescriptor('pbj:vendor3:package3:category3:message3:1-0-0'),
                ],
            ]),
        ]);

        $asset = new FieldMustContainsAnyOfClasses();
        $asset->validate($a, $b);
    }
}
