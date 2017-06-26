<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Pbjc\SchemaDescriptor;

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

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaPackage(SchemaDescriptor $schema)
    {
        $package = parent::schemaPackage($schema);
        if (!empty($package)) {
            return $package;
        }

        return "@{$schema->getId()->getVendor()}/schemas";
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaImport(SchemaDescriptor $schema, $withMajor = false)
    {
        $package = $this->schemaPackage($schema);
        $className = $this->schemaClassName($schema, $withMajor);

        $id = $schema->getId();
        $import = "{$package}/{$id->getVendor()}/{$id->getPackage()}";
        if ($id->getCategory()) {
            $import .= "/{$id->getCategory()}";
        }

        if ($schema->isMixinSchema()) {
            return "{$import}/{$id->getMessage()}/{$className}";
        }

        return "{$import}/{$className}";
    }
}
