<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Util\OutputFile;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $compiler = new Compiler();
        $this->assertInstanceOf('Gdbots\Pbjc\Compiler', $compiler);
    }

    public function testConstruct()
    {
        $compiler = new Compiler();

        $schemaIds = [
            'pbj:acme:blog:entity:article:1-0-0',
            'pbj:acme:blog:entity:article:1-0-1',
            'pbj:acme:blog:entity:comment:1-0-0',
            'pbj:acme:blog:mixin:has-comments:1-0-0',
            'pbj:acme:core:mixin:article:1-0-0',
        ];

        $found = preg_grep('/pbj:acme:*/', array_keys(SchemaStore::getSchemas()));

        $this->assertCount(5, $found);
        $this->assertEquals($schemaIds, $found);
    }

    public function testRun()
    {
        $compiler = new Compiler();

        $count = 0;

        $compiler->run('php', new CompileOptions([
            'namespaces' => [
                'acme:blog',
                'acme:core',
            ],
            'output' => __DIR__.'/../examples/src',
            'manifest' => __DIR__.'/../examples/pbj-schemas.php',
            'callback' => function (OutputFile $file) use (&$count) {
                $count++;
            },
        ]));

        $this->assertEquals(14, $count);
    }
}
