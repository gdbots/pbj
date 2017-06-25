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

            new \Twig_SimpleFunction('schema_as_class_name', [$this, 'schemaAsClassName']),
            new \Twig_SimpleFunction('schema_as_class_name_with_major', [$this, 'schemaAsClassNameWithMajor']),

            new \Twig_SimpleFunction('schema_as_fq_class_name', [$this, 'schemaAsFqClassName']),
            new \Twig_SimpleFunction('schema_as_fq_class_name_with_major', [$this, 'schemaAsFqClassNameWithMajor']),
        ];
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaAsClassName(SchemaDescriptor $schema)
    {
        return StringUtils::toCamelFromSlug($schema->getId()->getMessage());
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaAsClassNameWithMajor(SchemaDescriptor $schema)
    {
        $className = $this->schemaAsClassName($schema);
        return "{$className}V{$schema->getId()->getVersion()->getMajor()}";
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaAsFqClassName(SchemaDescriptor $schema)
    {
        $id = $schema->getId();
        $vendor = StringUtils::toCamelFromSlug($id->getVendor());
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        $className = $this->schemaAsClassName($schema);
        return "{$vendor}{$package}{$className}";
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaAsFqClassNameWithMajor(SchemaDescriptor $schema)
    {
        $className = $this->schemaAsFqClassName($schema);
        return "{$className}V{$schema->getId()->getVersion()->getMajor()}";
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
