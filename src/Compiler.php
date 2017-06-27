<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\MissingSchema;
use Gdbots\Pbjc\Generator\Generator;
use Symfony\Component\Finder\Finder;

final class Compiler
{
    /**
     * Construct.
     */
    public function __construct()
    {
        $enums = [];
        $schemas = [];

        foreach (SchemaStore::getDirs() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = Finder::create()->files()->in($dir)->name('*.xml');

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->getFilename() == 'enums.xml') {
                    $enums[] = $file->getPathName();
                } else {
                    $schemas[] = $file->getPathName();
                }
            }
        }

        ksort($enums);
        ksort($schemas);

        /*
         * Enums
         */

        $parser = new EnumParser();

        foreach ($enums as $file) {
            $enums = $parser->fromFile($file);

            foreach ($enums as $enum) {
                SchemaStore::addEnum($enum->getId(), $enum);
            }
        }

        /*
         * Schemas
         */

        $parser = new SchemaParser();
        $validator = new SchemaValidator();

        $currentFile = null;
        $exceptionFile = [];

        while (count($schemas) > 0) {
            if (!$currentFile) {
                $currentFile = current($schemas);
            }

            $file = $currentFile;

            try {
                if ($schema = $parser->fromFile($file)) {
                    SchemaStore::addSchema($schema->getId(), $schema);

                    $validator->validate($schema);
                }
            } catch (MissingSchema $e) {
                // remove "v" (version) from schemaId,
                // and replace colons with slashes (convert to path format)
                $pattern = sprintf(
                    '/%s%s(.*)/',
                    str_replace([':v', ':'], [':', '\/'], $e->getMessage()),
                    strpos($e->getMessage(), ':v') === false ? '\/' : null
                );

                // remove duplicate slashes
                $pattern = str_replace('\/\/', '\/', $pattern);

                // get matched files
                $files = preg_grep($pattern, $schemas);

                if ($files === false || count($files) === 0) {
                    throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $e->getMessage()));
                }

                if (in_array($currentFile, $exceptionFile)) {
                    throw new \RuntimeException(sprintf('Recursively requesting schema id "%s" from file "%s".', $e->getMessage(), $currentFile));
                }

                $exceptionFile[] = $currentFile;

                $currentFile = strpos($e->getMessage(), ':v')
                    // curie + version
                    ? current($files)
                    // curie
                    : end($files);

                continue;
            }

            unset($schemas[array_search($currentFile, $schemas)]);

            $currentFile = null;
            $exceptionFile = [];
        }

        /** @var SchemaDescriptor $schema */
        foreach (SchemaStore::getSchemasByCurieMajor() as $schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Generates and writes files for each schema.
     *
     * @param string         $language
     * @param CompileOptions $options
     *
     * @throws \InvalidArgumentException
     */
    public function run($language, CompileOptions $options)
    {
        $namespaces = $options->getNamespaces();

        if (!$namespaces || count($namespaces) === 0) {
            throw new \InvalidArgumentException('Missing "namespaces" options.');
        }

        if (!is_array($namespaces)) {
            $namespaces = [$namespaces];
        }

        foreach ($namespaces as $namespace) {
            if (!preg_match('/^([a-z0-9-]+):([a-z0-9\.-]+)$/', $namespace)) {
                throw new \InvalidArgumentException(sprintf(
                    'The namespace "%s" must follow "vendor:package" format.',
                    $namespace
                ));
            }
        }

        if (!$options->getOutput()) {
            throw new \InvalidArgumentException('Missing "output" directory options.');
        }

        $class = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', StringUtils::toCamelFromSlug($language));

        /** @var Generator $generator */
        $generator = new $class($options);
        $outputFiles = [];

        foreach (SchemaStore::getEnums() as $enum) {
            if (!$options->getIncludeAll() && !in_array($enum->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            $outputFiles = array_merge($outputFiles, $generator->generateEnum($enum)->getFiles());
        }

        foreach (SchemaStore::getSchemas() as $schema) {
            if (!$options->getIncludeAll() && !in_array($schema->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            $outputFiles = array_merge($outputFiles, $generator->generateSchema($schema)->getFiles());
        }

        if ($options->getManifest()) {
            $outputFiles = array_merge($outputFiles, $generator->generateManifest(SchemaStore::getSchemas())->getFiles());
        }

        if ($callback = $options->getCallback()) {
            foreach ($outputFiles as $outputFile) {
                call_user_func($callback, $outputFile);
            }
        }
    }
}
