<?php

namespace Gdbots\Tests\Pbjc\Asset;

use Gdbots\Pbjc\Validator\SchemaValidExtends;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaValidExtendsTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSame()
    {
        $m = new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0');

        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', ['extends' => $m]);
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', ['extends' => $m]);

        $asset = new SchemaValidExtends();
        $asset->validate($a, $b);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function testValidateException()
    {
        $a = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0', ['extends' => new SchemaDescriptor('pbj:vendor2:package2:category2:message2:1-0-0')]);
        $b = new SchemaDescriptor('pbj:vendor:package:category:message:1-0-1', ['extends' => new SchemaDescriptor('pbj:vendor3:package3:category3:message3:1-0-0')]);

        $asset = new SchemaValidExtends();
        $asset->validate($a, $b);
    }
}
