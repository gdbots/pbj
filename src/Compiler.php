<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\MissingSchema;
use Gdbots\Pbjc\Util\ParameterBag;
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

        /**
         * Enums
         */

        $parser = new EnumParser();

        foreach ($enums as $file) {
            $enums = $parser->fromFile($file);

            foreach ($enums as $enum) {
                SchemaStore::addEnum($enum->getId(), $enum);
            }
        }

        /**
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
                $schema = $parser->fromFile($file);

            } catch (MissingSchema $e) {
                $files = preg_grep(sprintf('/%s*/', str_replace([':v', ':'], [':', '\/'], $e->getMessage())), $schemas);

                if (count($files) === 0) {
                    throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $e->getMessage()));
                }

                $currentFile = end($files);

                if (in_array($currentFile, $exceptionFile)) {
                    throw new \RuntimeException(sprintf('Recursively requesting schema file "%s".', $currentFile));
                }

                $exceptionFile[] = $currentFile;

                continue;
            }

            SchemaStore::addSchema($schema->getId(), $schema);

            $validator->validate($schema);

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
     * @param string       $language
     * @param ParameterBag $options
     *
     * @return \Gdbots\Pbjc\Generator\Generator
     *
     * @throw \InvalidArgumentException
     */
    public function run($language, ParameterBag $options)
    {
        if (!$options->has('namespaces')) {
            throw new \InvalidArgumentException('Missing "namespaces" options.');
        }

        $namespaces = $options->get('namespaces');
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

        if (!$options->has('output')) {
            throw new \InvalidArgumentException('Missing "output" directory options.');
        }

        $class = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', StringUtils::toCamelFromSlug($language));
        $generator = new $class($options->get('output'));

        foreach (SchemaStore::getEnums() as $enum) {
            if (!in_array($enum->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            $generator->generateEnum($enum);
        }

        foreach (SchemaStore::getSchemas() as $schema) {
            if (!in_array($schema->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            $generator->generateSchema($schema);
        }

        if (!$options->has('manifest')) {
            $generator->generateManifest(SchemaStore::getSchemasByNamespaces($namespaces), $options->get('manifest'));
        }

        return $generator;
    }
}
