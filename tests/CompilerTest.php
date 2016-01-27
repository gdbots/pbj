<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\Compiler;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $compiler = new Compiler('php');
        $this->assertInstanceOf('Gdbots\Pbjc\Compiler', $compiler);
    }
}
