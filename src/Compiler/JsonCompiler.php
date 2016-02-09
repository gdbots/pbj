<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Pbjc\Descriptor\SchemaDescriptor;
use Gdbots\Pbjc\Generator\JsonGenerator;

class JsonCompiler extends Compiler
{
    /** @var string */
    protected $language = 'json';

    /**
     * {@inheritdoc}
     */
    public function createGenerator(SchemaDescriptor $schema)
    {
        return new JsonGenerator($schema);
    }
}
