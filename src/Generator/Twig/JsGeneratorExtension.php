<?php

namespace Gdbots\Pbjc\Generator\Twig;

class JsGeneratorExtension extends GeneratorExtension
{
    const LANGUAGE = 'js';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array_merge(parent::getFunctions(), [
            // new \Twig_SimpleFunction('getClassName', [$this, 'getClassName']),
        ]);
    }
}
