<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\MissingSchema;
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
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $key => $file) {
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
                $files = preg_grep(sprintf('/%s*/', str_replace([':v', ':'], [':', '\/'], $e->getMessage())), $schemas);

                if ($files === false || count($files) === 0) {
                    throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $e->getMessage()));
                }

                $currentFile = end($files);

                if (in_array($currentFile, $exceptionFile)) {
                    throw new \RuntimeException(sprintf('Recursively requesting schema file "%s".', $currentFile));
                }

                $exceptionFile[] = $currentFile;

                continue;
            }

            unset($schemas[array_search($currentFile, $schemas)]);

            if (in_array($currentFile, $exceptionFile)) {
                unset($exceptionFile[array_search($currentFile, $exceptionFile)]);
            }

            $currentFile = null;
        }

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
     * @throw \InvalidArgumentException
     */
    public function run($language, CompileOptions $options)
    {
        if (!$options->getNamespaces()) {
            throw new \InvalidArgumentException('Missing "namespaces" options.');
        }

        $namespaces = $options->getNamespaces();
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
        $generator = new $class($options->getOutput());

        $outputFiles = [];

        foreach (SchemaStore::getEnums() as $enum) {
            if (!in_array($enum->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            /** @var $response \Gdbots\Pbjc\Generator\GeneratorResponse */
            if ($response = $generator->generateEnum($enum)) {
                $outputFiles = array_merge($outputFiles, $response->getFiles());
            }
        }

        foreach (SchemaStore::getSchemas() as $schema) {
            if (!in_array($schema->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            /** @var $response \Gdbots\Pbjc\Generator\GeneratorResponse */
            if ($response = $generator->generateSchema($schema)) {
                $outputFiles = array_merge($outputFiles, $response->getFiles());
            }
        }

        if ($manifest = $options->getManifest()) {
            /** @var $response \Gdbots\Pbjc\Generator\GeneratorResponse */
            if ($response = $generator->generateManifest(SchemaStore::getSchemasByNamespaces($namespaces), $manifest)) {
                $outputFiles = array_merge($outputFiles, $response->getFiles());
            }
        }

        if ($callback = $options->getCallback()) {
            foreach ($outputFiles as $outputFile) {
                call_user_func($callback, $outputFile);
            }
        }
    }
}
