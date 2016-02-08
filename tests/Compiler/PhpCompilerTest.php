<?php

namespace Gdbots\Tests\Pbjc\Compiler;

use Gdbots\Pbjc\Compiler\PhpCompiler;

class PhpCompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $compiler = new PhpCompiler();
        $this->assertInstanceOf('Gdbots\Pbjc\Compiler\PhpCompiler', $compiler);
    }
}
