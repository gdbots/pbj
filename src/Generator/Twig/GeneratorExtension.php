<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\Generator\Generator;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

abstract class GeneratorExtension extends \Twig_Extension
{
    const LANGUAGE = 'unknown';

    /** @var CompileOptions */
    protected $compileOptions;

    /** @var Generator */
    protected $generator;

    /**
     * @param CompileOptions $compileOptions
     * @param Generator      $generator
     */
    public function __construct(CompileOptions $compileOptions, Generator $generator)
    {
        $this->compileOptions = $compileOptions;
        $this->generator = $generator;
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

            new \Twig_SimpleFunction('schema_to_class_name', [$this->generator, 'schemaToClassName']),
            new \Twig_SimpleFunction('schema_to_fq_class_name', [$this->generator, 'schemaToFqClassName']),
            new \Twig_SimpleFunction('enum_to_class_name', [$this->generator, 'enumToClassName']),
            new \Twig_SimpleFunction('schema_to_native_package', [$this->generator, 'schemaToNativePackage']),
            new \Twig_SimpleFunction('enum_to_native_package', [$this->generator, 'enumToNativePackage']),

            new \Twig_SimpleFunction('schema_to_native_import_path', [$this->generator, 'schemaToNativeImportPath']),
            new \Twig_SimpleFunction('enum_to_native_import_path', [$this->generator, 'enumToNativeImportPath']),
        ];
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
