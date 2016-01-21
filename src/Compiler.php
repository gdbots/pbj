<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class Compiler
{
    /** @var string */
    protected $output = null;

    /**
     * @param string $output
     */
    public function compile($output = null)
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
                if ($data = $this->parseFile($file)) {
                    $schema = $this->createSchema($data);

                    SchemaStore::addSchema($schema->__toString(), $schema, true);
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
     * Converts YAML data into Schema instance.
     *
     * @param array $data
     *
     * @return Schema
     */
    protected function createSchema(array $data)
    {
        $fields  = [];
        $mixins  = [];
        $options = new ParameterBag();

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'id':
                    break;

                case 'fields':
                    foreach ($value as $name => $attributes) {
                        $field = new ParameterBag($attributes);

                        if (!$field->has('type')) {
                            continue;
                        }

                        $fields[] = new Field(
                            $name,
                            $field->get('type'),
                            (int) $field->get('rule'),
                            (bool) $field->get('required', false),
                            (int) $field->get('min_length'),
                            (int) $field->get('max_length'),
                            $field->get('pattern'),
                            $field->get('format'),
                            (int) $field->get('min'),
                            (int) $field->get('max'),
                            (int) $field->get('precision', 10),
                            (int) $field->get('scale', 2),
                            $field->get('default'),
                            $field->get('className'),
                            (array) $field->get('any_of'),
                            $field->get('assertion'),
                            (bool) $field->get('overridable', false)
                        );
                    }
                    break;

                case 'mixins':
                    $mixins = $value;
                    break;

                default:
                    if (is_array($value)) {
                        $value = new ParameterBag($value);
                    }

                    $options->set($key, $value);
            }
        }

        $schema = new Schema($data['id'], $fields, $mixins, $options);

        return $schema;
    }

    /**
     * Generates and writes files for each schema.
     *
     * @return this
     */
    protected function generate()
    {
        foreach (SchemaStore::getSchemas() as &$schema) {
            if (!$schema->getOptions()->get('isCompiled')) {
                if (!$typeClass = $this->guestTypeClass($schema)) {
                    continue;
                }

                $generator = new $typeClass();
                $generator->generate($schema, $this->output);

                $schema->getOptions()->set('isCompiled', true);

                // todo: handle recursive logic, if needed
            }
        }
    }

    /**
     * Returns the type class of the schema to generate.
     *
     * @param Schema $schema
     *
     * @return string
     */
    protected function guestTypeClass(Schema $schema)
    {
        if ($schema->getOptions()->get('mixin') === true) {
            return '\\Gdbots\\Pbjc\\Generator\\MixinGenerator';
        }

        // todo: handle other generator classes

        return null;
    }
}
