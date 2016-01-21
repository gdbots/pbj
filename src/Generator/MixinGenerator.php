<?php

namespace Gdbots\Pbjc\Generator;

/**
 * Generates a Mixin class
 *
 *     [php]
 *     $generator = new \Gdbots\Pbjc\Generator\MixinGenerator();
 *     $generator->generate($schema, '/path/to/output');
 *
 */
class MixinGenerator extends Generator
{
    /** @var string */
    protected $template = 'mixin/Mixin.php.twig';

    /** @var string */
    protected $filePrefix = 'Mixin';
}
