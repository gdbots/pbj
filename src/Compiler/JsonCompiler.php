<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Pbjc\Generator\JsonGenerator;

class JsonCompiler extends Compiler
{
    /** @var string */
    protected $language = 'json';

    /**
     * {@inheritdoc}
     */
    public function createGenerator()
    {
        return new JsonGenerator();
    }
}
