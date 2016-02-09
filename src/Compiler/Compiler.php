<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Gdbots\Pbjc\Descriptor\EnumDescriptor;
use Gdbots\Pbjc\Descriptor\FieldDescriptor;
use Gdbots\Pbjc\Descriptor\SchemaDescriptor;
use Gdbots\Pbjc\SchemaId;
use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;

abstract class Compiler
{
    /** @var string */
    protected $language;

    /** @var string */
    protected $output;

    /**
     * @param string $output
     *
     * @throw InvalidLanguage
     */
    public function __construct($output = null)
    {
        $this->output = $output;

        $this->loadSchemas();
    }

    /**
     * Reads all schemas from all stored directories.
     *
     * @return this
     *
     * @throws \InvalidArgumentException
     */
    protected function loadSchemas()
    {
        // load all schema and store XML data
        foreach (SchemaStore::getDirs() as $dir => $isDependent) {
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $file) {
                if ($xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../../schema.xsd')) {
                    if ($xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                        $xmlData['entity']['is_dependent'] = $isDependent;

                        $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename())-1);

                        if ($this->validateXmlSchemaId($xmlData['entity']['id'], $filePath)) {
                            if ($schema = SchemaStore::getSchemaById($xmlData['entity']['id'], null, true)) {
                                if ($schema instanceof SchemaDescriptor) {
                                    if (!$schema->getOption($this->language)) {
                                        $schema->setOption(
                                            $this->language,
                                            $this->processXmlLanguageOptions($xmlData)
                                        );

                                        continue;
                                    }
                                }
                            }

                            SchemaStore::addSchema($xmlData['entity']['id'], $xmlData['entity'], true);
                        }
                    }
                }
            }
        }

        // resolve and mark schemas as latest
        $schemaLatestVersion = [];

        foreach (SchemaStore::getSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = $this->createSchema($schema);
            }

            if (!isset($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()])) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }
            if ($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()]
                ->getId()->getVersion()->compare(
                    $schema->getId()->getVersion()
                ) === -1
            ) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }

            // update
            SchemaStore::addSchema($schema->__toString(), $schema, true);
        }

        foreach ($schemaLatestVersion as &$schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Validate the the dir sctructure match the schema id.
     *
     * @param array $schemaId
     * @param array $dir
     *
     * @return bool
     */
    protected function validateXmlSchemaId($schemaId, $dir)
    {
        $schemaId = SchemaId::fromString($schemaId);

        $schemaPath = sprintf(
            '%s/%s/%s',
            $schemaId->getVendor(),
            $schemaId->getPackage(),
            $schemaId->getCategory()
        );

        return (bool) strrpos($dir, $schemaPath);
    }

    /**
     * Converts XML data into SchemaDescriptor instance.
     *
     * @param array $xmlData
     *
     * @return SchemaDescriptor
     *
     * @throw \RuntimeException
     */
    protected function createSchema(array $xmlData)
    {
        $schemaId = SchemaId::fromString($xmlData['id']);
        $schema   = new SchemaDescriptor($schemaId->__toString());

        if (isset($xmlData['mixin']) && $xmlData['mixin']) {
            $schema->setIsMixin(true);
        }

        if (isset($xmlData['is_dependent']) && $xmlData['is_dependent']) {
            $schema->setIsDependent(true);
        }

        // default language options
        $schema->setOption($this->language, $this->processXmlLanguageOptions($xmlData));

        // assign enums
        if (isset($xmlData['enums']['enum'])) {
            $this->processXmlEnums($schema, $xmlData['enums']['enum']);

            // add enums language options
            $langOptionsKey = sprintf('%s_options', $this->language);
            if (isset($xmlData['enums'][$langOptionsKey])) {
                $schema->setOptionSubOption($this->language, 'enums', $xmlData['enums'][$langOptionsKey]);
            }
        }

        if (isset($xmlData['fields']['field'])) {
            $this->processXmlFields($schema, $xmlData['fields']['field']);
        }

        if (isset($xmlData['mixins']['id'])) {
            $this->processXmlMixins($schema, $xmlData['mixins']['id']);
        }

        if ($prevSchema = SchemaStore::getSchemaById($schemaId->getCurieWithMajorRev(), $schemaId, false)) {
            if (is_array($prevSchema)) {
                $prevSchema = $this->createSchema($prevSchema);
            }

            // convert schema's to arra and compare values
            $currentSchemaArray = json_decode(json_encode($schema), true);
            $prevSchemaArray = json_decode(json_encode($prevSchema), true);

            // check if something got removed or cahnged
            $diff = $this->arrayRecursiveDiff($prevSchemaArray, $currentSchemaArray);

            // removed schema id - going to be diff ofcorse.. doh
            if (isset($diff['id'])) {
                unset($diff['id']);
            }

            if (count($diff) > 0) {
                throw new \RuntimeException(sprintf(
                    'Schema ["%s"] is invalid. Schema has changed dramatically from previous version: [%s]',
                    $schemaId->__toString(),
                    json_encode($diff)
                ));
            }
        }

        return $schema;
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    protected function arrayRecursiveDiff(array $array1, array $array2)
    {
        $diff = array();

        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayRecursiveDiff($value, $array2[$key]);
                    if (count($recursiveDiff)) {
                        $diff[$key] = $recursiveDiff;
                    }
                } else {
                    if ($value != $array2[$key]) {
                        $diff[$key] = $value;
                    }
                }
            } else {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * @param array|string $data
     * @param string       $key
     *
     * @return array
     */
    protected function convertXmlDataToArray($data, $key = null)
    {
        if (!is_array($data) || ($key && isset($data[$key]))) {
            $data = [$data];
        }
        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function processXmlLanguageOptions(array $data)
    {
        $langOptionsKey = sprintf('%s_options', $this->language);
        if (isset($data[$langOptionsKey])) {
            return $data[$langOptionsKey];
        }

        return [];
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $data
     */
    protected function processXmlEnums(SchemaDescriptor $schema, array $data)
    {
        $data = $this->convertXmlDataToArray($data, 'name');
        foreach ($data as $item) {
            // force default type to be "string"
            if (!isset($item['type'])) {
                $item['type'] = 'string';
            }

            $values = [];
            $keys = $this->convertXmlDataToArray($item['option'], 'key');
            foreach ($keys as $key) {
                $values[$key['key']] = $item['type'] == 'int'
                    ? intval($key['value'])
                    : (string) $key['value']
                ;
            }

            $enum = new EnumDescriptor($item['name'], $values);

            $schema->setOption('enums', array_merge(
                $schema->getOption('enums', []),
                [
                    $enum
                ]
            ));
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $data
     */
    protected function processXmlFields(SchemaDescriptor $schema, array $data)
    {
        $data = $this->convertXmlDataToArray($data);
        foreach ($data as $item) {
            // ignore if no type was assign
            if (!isset($item['type'])) {
                continue;
            }

            if (!isset($item['options'])) {
                $item['options'] = [];
            }

            if (isset($item['any_of']['id'])) {
                $anyOf = $this->convertXmlDataToArray($item['any_of']['id']);

                /** @var $item['any_of'] SchemaDescriptor[] */
                $item['any_of'] = [];

                foreach ($anyOf as $curie) {
                    // can't add yourself to anyof
                    if ($curie == $schema->getId()->getCurieWithMajorRev()) {
                        continue;
                    }

                    $anyOfSchema = SchemaStore::getSchemaById($curie, $schema->getId());
                    if (is_array($anyOfSchema)) {
                        $anyOfSchema = $this->createSchema($anyOfSchema);
                    }

                    $item['any_of'][] = $anyOfSchema;
                }
            }
            if (isset($item['any_of']) && count($item['any_of']) === 0) {
                unset($item['any_of']);
            }

            if (isset($item['enum'])) {
                if ($item['enum']['provider'] == $schema->getId()->getCurieWithMajorRev()) {
                    $providerSchema = $schema;
                } else {
                    $providerSchema = SchemaStore::getSchemaById($item['enum']['provider'], $schema->getId());
                    if (is_array($providerSchema)) {
                        $providerSchema = $this->createSchema($providerSchema);
                    }
                }

                /** @var $enums EnumDescriptor[] */
                if ($enums = $providerSchema->getOption('enums')) {
                    foreach ($enums as $enum) {
                        if ($enum->getName() == $item['enum']['name']) {
                            $item['options']['enum'] = $enum;

                            break;
                        }
                    }
                }

                if (!isset($item['options']['enum'])) {
                    throw new \RuntimeException(sprintf(
                        'No Enum with provider ["%s"] and name ["%s"] exist.',
                        $item['enum']['provider'],
                        $item['enum']['name']
                    ));
                }

                unset($item['enum']);
            }

            $schema->addField(new FieldDescriptor($item['name'], $item));
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array|string     $data
     */
    protected function processXmlMixins(SchemaDescriptor $schema, $data)
    {
        $data = $this->convertXmlDataToArray($data);
        foreach ($data as $curieWithMajorRev) {
            // can't add yourself to mixins
            if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
                continue;
            }

            $mixinSchema = SchemaStore::getSchemaById($curieWithMajorRev, $schema->getId());
            if (is_array($mixinSchema)) {
                $mixinSchema = $this->createSchema($mixinSchema);
            }

            $schema->setOption('mixins', array_merge(
                $schema->getOption('mixins', []),
                [
                    $mixinSchema
                ]
            ));
        }
    }

    /**
     * Generates and writes files for each schema.
     *
     * @param bool $output
     *
     * @return array|null
     */
    public function generate($output = false)
    {
        $generator = $this->createGenerator();

        foreach (SchemaStore::getSchemas() as &$schema) {
            if (!$schema->isDependent() && !$schema->getOptionSubOption($this->language, 'isCompiled')) {
                $generator->setSchema($schema);
                $generator->generate($this->output);

                if ($schema->getOption('enums')) {
                    $generator->generateEnums($this->output);
                }

                $schema->setOptionSubOption($this->language, 'isCompiled', true);
            }
        }

        return $output ? $generator->getFiles() : null;
    }

    /**
     * Returns a Generator instance.
     *
     * @return \Gdbots\Pbjc\Generator\Generator
     */
    abstract public function createGenerator();
}
