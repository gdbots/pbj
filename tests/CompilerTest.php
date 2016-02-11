<?php

namespace Gdbots\Tests\Pbjc\Compiler;

use Gdbots\Pbjc\Compiler;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $compiler = new Compiler();
        $this->assertInstanceOf('Gdbots\Pbjc\Compiler', $compiler);
    }
}
