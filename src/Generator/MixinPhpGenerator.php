<?php

namespace Gdbots\Pbjc\Generator;

class MixinPhpGenerator extends Generator
{
    /** @var string */
    protected $template = 'mixin/Mixin.php.twig';

    /** @var string */
    protected $prefix = 'Mixin';
}
