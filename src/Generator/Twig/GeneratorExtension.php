<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

abstract class GeneratorExtension extends \Twig_Extension
{
    const LANGUAGE = 'unknown';

    /** @var CompileOptions */
    protected $compileOptions;

    /**
     * @param CompileOptions $compileOptions
     */
    public function __construct(CompileOptions $compileOptions)
    {
        $this->compileOptions = $compileOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getClassName', [$this, 'getClassName']),
            new \Twig_SimpleFunction('hasOtherMajorRev', [$this, 'hasOtherMajorRev']),
            new \Twig_SimpleFunction('isSameNamespace', [$this, 'isSameNamespace']),
            new \Twig_SimpleFunction('getAllVersions', [$this, 'getAllVersions']),

            new \Twig_SimpleFunction('has_other_major_rev', [$this, 'hasOtherMajorRev']),
            new \Twig_SimpleFunction('is_same_namespace', [$this, 'isSameNamespace']),
            new \Twig_SimpleFunction('get_all_versions', [$this, 'getAllVersions']),

            new \Twig_SimpleFunction('schema_class_name', [$this, 'schemaClassName']),
            new \Twig_SimpleFunction('schema_fq_class_name', [$this, 'schemaFqClassName']),
            new \Twig_SimpleFunction('schema_package', [$this, 'schemaPackage']),
            new \Twig_SimpleFunction('schema_import', [$this, 'schemaImport']),
        ];
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaClassName(SchemaDescriptor $schema, $withMajor = false)
    {
        $className = StringUtils::toCamelFromSlug($schema->getId()->getMessage());
        if (!$withMajor) {
            return $className;
        }

        return "{$className}V{$schema->getId()->getVersion()->getMajor()}";
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaFqClassName(SchemaDescriptor $schema, $withMajor = false)
    {
        $id = $schema->getId();
        $vendor = StringUtils::toCamelFromSlug($id->getVendor());
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        $className = "{$vendor}{$package}{$this->schemaClassName($schema)}";
        if (!$withMajor) {
            return $className;
        }

        return "{$className}V{$schema->getId()->getVersion()->getMajor()}";
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaPackage(SchemaDescriptor $schema)
    {
        $packages = $this->compileOptions->getPackages();
        $id = $schema->getId();

        $vendorPackage = "{$id->getVendor()}:{$id->getPackage()}";

        if (isset($packages[$vendorPackage])) {
            return $packages[$vendorPackage];
        }

        if (isset($packages[$id->getVendor()])) {
            return $packages[$id->getVendor()];
        }

        return null;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaImport(SchemaDescriptor $schema, $withMajor = false)
    {
        return null;
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return bool
     */
    public function hasOtherMajorRev(SchemaDescriptor $schema)
    {
        return SchemaStore::hasOtherSchemaMajorRev($schema->getId());
    }

    /**
     * @param SchemaDescriptor $a
     * @param SchemaDescriptor $b
     *
     * @return bool
     */
    public function isSameNamespace(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        // fixme: use new strategy from compile options.
        $ans = $a->getLanguage(static::LANGUAGE)->get('namespace');
        $bns = $b->getLanguage(static::LANGUAGE)->get('namespace');
        return $ans == $bns;
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    public function getAllVersions(SchemaDescriptor $schema)
    {
        return SchemaStore::getAllSchemaVersions($schema->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::LANGUAGE . '_generator';
    }
}
