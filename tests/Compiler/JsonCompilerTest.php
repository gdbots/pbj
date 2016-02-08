<?php

namespace Gdbots\Tests\Pbjc\Compiler;

use Gdbots\Pbjc\Compiler\JsonCompiler;

class JsonCompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $compiler = new JsonCompiler();
        $this->assertInstanceOf('Gdbots\Pbjc\Compiler\JsonCompiler', $compiler);
    }
}
