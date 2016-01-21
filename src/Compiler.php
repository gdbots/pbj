<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\SchemaStore;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class Compiler
{
    /** @var string */
    protected $output = null;

    /**
     * @param string $output
     */
    public function compile($output)
    {
        $this->output = $output;

        $this->loadSchemas()->generate();
    }

    /**
     * Reads all schemas from all stored directories.
     *
     * @return this
     */
    protected function loadSchemas()
    {
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.yml');

            foreach ($files as $file) {
                if ($schema = $this->parseFile($file)) {
                    SchemaStore::addSchema($schema['id'], $schema, true);
                }
            }
        }

        return $this;
    }

    /**
     * Parses a YAML file.
     *
     * @param string $file Path to a file
     *
     * @return array|null
     *
     * @throws \InvalidArgumentException When loading of YAML file returns error
     */
    protected function parseFile($file)
    {
        $yamlParser = new YamlParser();

        try {
            return $yamlParser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            $e->setParsedFile($file);

            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * Generates and writes files for each schema.
     *
     * @return this
     */
    protected function generate()
    {
        throw new \Exception('Not yet implemented');
    }

    /**
     * Returns the type of the schema to generate.
     *
     * @param array $schema
     *
     * @return string
     */
    protected function guestType(array $schema)
    {
        throw new \Exception('Not yet implemented');
    }
}
