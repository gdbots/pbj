<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\MissingSchema;
use Gdbots\Pbjc\Util\ParameterBag;
use Symfony\Component\Finder\Finder;

final class Compiler
{
    /** @\Closure */
    private $dispatcher;

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
     * @param string       $language
     * @param ParameterBag $options
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

        $outputFiles = [];

        foreach (SchemaStore::getEnums() as $enum) {
            if (!in_array($enum->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            if ($result = $generator->generateEnum($enum)) {
                $outputFiles = array_merge($outputFiles, $result);
            }
        }

        foreach (SchemaStore::getSchemas() as $schema) {
            if (!in_array($schema->getId()->getNamespace(), $namespaces)) {
                continue;
            }

            if ($result = $generator->generateSchema($schema)) {
                $outputFiles = array_merge($outputFiles, $result);
            }
        }

        if ($options->has('manifest')) {
            if ($result = $generator->generateManifest(SchemaStore::getSchemasByNamespaces($namespaces), $options->get('manifest'))) {
                $outputFiles = array_merge($outputFiles, $result);
            }
        }

        if ($this->dispatcher) {
            foreach ($outputFiles as $outputFile) {
                call_user_func($this->dispatcher, $outputFile);
            }
        }
    }

    /**
     * Sets a dispatcher call for handling generator response.
     *
     * @param \Closure $dispatcher
     *
     * @return this
     */
    public function setDispatcher(\Closure $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }
}
