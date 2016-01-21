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
    /**
     * @var string
     */
    protected $template = 'mixin/Mixin.php.twig';

    /**
     * {@inheritdoc}
     */
    protected function isValid(array $schema)
    {
        // todo
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParameters(array $schema)
    {
        return [
            'namespace' => $schema['php_options']['namespace'],
            'class_name' => $this->getFileName(),
            'shema_id' => $schema['id'],
            'fields' => $schema['fields']
        ];
    }
}
