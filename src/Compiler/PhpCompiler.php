<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Pbjc\Generator\PhpGenerator;

class PhpCompiler extends Compiler
{
    /** @var string */
    protected $language = 'php';

    /**
     * {@inheritdoc}
     */
    public function createGenerator()
    {
        return new PhpGenerator();
    }
}
