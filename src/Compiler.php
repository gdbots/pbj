<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class Compiler
{
    /** @var array */
    const LANGUAGES = ['php', 'json'];

    /** @var string */
    protected $language;

    /** @var string */
    protected $output;

    /**
     * @param string $language
     * @param string $output
     *
     * @throw InvalidLanguage
     */
    public function __construct($language, $output = null)
    {
        $this->language = strtolower($language);
        $this->output = $output;

        if (!in_array($this->language, self::LANGUAGES)) {
            throw new InvalidLanguage(sprintf('Compile does not support "%s" language.', $this->language));
        }

        $this->loadSchemas();
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

        $schemaLatestVersion = [];

        foreach (SchemaStore::getSortedSchemas() as &$schema) {
            if (!isset($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()])) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }

            if ($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()]
                ->getId()->getVersion() < $schema->getId()->getVersion()) {

                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }
        }

        foreach ($schemaLatestVersion as &$schema) {
            $schema->setIsLatestVersion(true);
        }
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
                case 'mixin':
                    break;

                case 'fields':
                    foreach ($value as $name => $attributes) {
                        if (!isset($attributes['type'])) {
                            continue;
                        }

                        $fields[] = Field::fromArray($name, $attributes);
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

        if (isset($data['mixin']) && $data['mixin']) {
            $schema->setIsMixin(true);
        }

        return $schema;
    }

    /**
     * Generates and writes files for each schema.
     *
     * @return this
     */
    public function generate()
    {
        foreach (SchemaStore::getSortedSchemas() as &$schema) {
            if (!$schema->getOptions()->get('isCompiled')) {
                $generator = new Generator($schema, $this->language);
                $generator->generate($this->output);

                $schema->getOptions()->set('isCompiled', true);
            }
        }
    }
}
