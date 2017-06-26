<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\SchemaDescriptor;

class PhpGeneratorExtension extends GeneratorExtension
{
    const LANGUAGE = 'php';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array_merge(parent::getFunctions(), [
            new \Twig_SimpleFunction('getClassName', [$this, 'getClassName']),
            new \Twig_SimpleFunction('schema_psr_namespace', [$this, 'schemaPsrNamespace']),
        ]);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $majorRev
     * @param bool             $addBase
     * @param bool             $withAs
     * @param string           $postfix
     *
     * @return string
     */
    public function getClassName(SchemaDescriptor $schema, $majorRev = false, $addBase = false, $withAs = false, $postfix = null)
    {
        $className = StringUtils::toCamelFromSlug($schema->getId()->getMessage());

        if ($majorRev) {
            $className = sprintf(
                '%sV%d',
                StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                $schema->getId()->getVersion()->getMajor()
            );
        }

        $newClassName = $className;

        if ($addBase) {
            $newClassName = sprintf(
                '%s%s%s',
                StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
                $className
            );
        }

        if ($withAs) {
            $className = sprintf('%s%s as %s%s', $className, $postfix, $newClassName, $postfix);
        } else {
            $className = sprintf('%s%s', $newClassName, $postfix);
        }

        return $className;
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

        $vendor = StringUtils::toCamelFromSlug($schema->getId()->getVendor());
        return "{$vendor}\Schemas";
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaImport(SchemaDescriptor $schema, $withMajor = false)
    {
        $ns = $this->schemaPsrNamespace($schema);
        $className = $this->schemaClassName($schema, $withMajor);

        $id = $schema->getId();
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));

        $import = "{$ns}\\{$package}";
        if ($id->getCategory()) {
            $import .= '\\' . StringUtils::toCamelFromSlug($id->getCategory());
        }

        if ($schema->isMixinSchema()) {
            $mixinName = $this->schemaClassName($schema);
            return "{$import}\\{$mixinName}\\{$className}";
        }

        return "{$import}\\{$className}";
    }


    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaPsrNamespace(SchemaDescriptor $schema)
    {
        $schemaPackage = $this->schemaPackage($schema);
        $id = $schema->getId();
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));

        $import = "{$schemaPackage}\\{$package}";
        if ($id->getCategory()) {
            $import .= '\\' . StringUtils::toCamelFromSlug($id->getCategory());
        }

        if ($schema->isMixinSchema()) {
            $mixinName = $this->schemaClassName($schema);
            return "{$import}\\{$mixinName}";
        }

        return $import;
    }
}
