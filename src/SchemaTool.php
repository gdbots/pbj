<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Descriptor\EnumDescriptor;
use Gdbots\Pbjc\Descriptor\FieldDescriptor;
use Gdbots\Pbjc\Descriptor\SchemaDescriptor;

/**
 * The SchemaTool is a tool to create/update schemas class descriptors.
 */
class SchemaTool
{
    /**
     * Holds the current processed schema.
     *
     * @var SchemaDescriptor
     */
    protected $schema;

    /**
     * Creates a Schema instance from a given set of metadata.
     *
     * @param array $data
     *
     * @return this
     */
    public function createSchema(array $data)
    {
        $schemaId = SchemaId::fromString($data['id']);
        $this->schema = new SchemaDescriptor($schemaId->__toString());

        if (isset($data['mixin']) && $data['mixin']) {
            $this->schema->setIsMixin(true);
        }

        if (isset($data['is_dependent']) && $data['is_dependent']) {
            $this->schema->setIsDependent(true);
        }

        // default language options
        $options = $this->getLanguageOptions($data);
        foreach ($options as $language => $option) {
            $this->schema->setOption($language, $option);
        }

        // assign enums
        if (isset($data['enums'])) {
            if (isset($data['enums']['enum'])) {
                $this->setEnums($data['enums']['enum']);
            }

            // add enums language options
            $options = $this->getLanguageOptions($data['enums']);
            foreach ($options as $language => $option) {
                $this->schema->setOptionSubOption($language, 'enums', $option);
            }
        }

        if (isset($data['fields']['field'])) {
            $this->setFields($data['fields']['field']);
        }

        if (isset($data['mixins']['id'])) {
            $this->setMixins($data['mixins']['id']);
        }

        return $this;
    }

    /**
     * @return SchemaDescriptor
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Validate schema against previous version.
     *
     * @return array
     */
    public function validate()
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($this->schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = $this->createSchema($prevSchema);
        }

        // convert schema's to arra and compare values
        $currentSchemaArray = json_decode(json_encode($this->schema), true);
        $prevSchemaArray = json_decode(json_encode($prevSchema), true);

        // check if something got removed or cahnged
        $diff = $this->arrayRecursiveDiff($prevSchemaArray, $currentSchemaArray);

        // removed schema id - going to be diff ofcorse.. doh
        if (isset($diff['id'])) {
            unset($diff['id']);
        }

        return $diff;
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
    protected function fixArray($data, $key = null)
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
    protected function getLanguageOptions(array $data)
    {
        $options = [];

        foreach ($data as $key => $value) {
            if (substr($key, -8) == '_options') {
                $language = substr($key, 0, -8); // remove "_options"

                $options[$language] = $value;
            }
        }

        return $options;
    }

    /**
     * @param array            $data
     */
    protected function setEnums(array $data)
    {
        $data = $this->fixArray($data, 'name');
        foreach ($data as $item) {
            // force default type to be "string"
            if (!isset($item['type'])) {
                $item['type'] = 'string';
            }

            $values = [];
            $keys = $this->fixArray($item['option'], 'key');
            foreach ($keys as $key) {
                $values[$key['key']] = $item['type'] == 'int'
                    ? intval($key['value'])
                    : (string) $key['value']
                ;
            }

            $enum = new EnumDescriptor($item['name'], $values);

            $this->schema->setOption('enums', array_merge(
                $this->schema->getOption('enums', []),
                [
                    $enum,
                ]
            ));
        }
    }

    /**
     * @param array            $data
     */
    protected function setFields(array $data)
    {
        $data = $this->fixArray($data);
        foreach ($data as $item) {
            // ignore if no type was assign
            if (!isset($item['type'])) {
                continue;
            }

            if (!isset($item['options'])) {
                $item['options'] = [];
            }

            if (isset($item['any_of']['id'])) {
                $anyOf = $this->fixArray($item['any_of']['id']);

                /* @var $item['any_of'] SchemaDescriptor[] */
                $item['any_of'] = [];

                foreach ($anyOf as $curie) {
                    // can't add yourself to anyof
                    if ($curie == $this->schema->getId()->getCurie()) {
                        continue;
                    }

                    $anyOfSchema = SchemaStore::getSchemaById($curie);
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
                if ($item['enum']['provider'] == $this->schema->getId()->getCurieWithMajorRev()) {
                    $providerSchema = $this->schema;
                } else {
                    $providerSchema = SchemaStore::getSchemaById($item['enum']['provider']);
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

            $this->schema->addField(new FieldDescriptor($item['name'], $item));
        }
    }

    /**
     * @param array|string     $data
     */
    protected function setMixins($data)
    {
        $data = $this->fixArray($data);
        foreach ($data as $curieWithMajorRev) {
            // can't add yourself to mixins
            if ($curieWithMajorRev == $this->schema->getId()->getCurieWithMajorRev()) {
                continue;
            }

            $mixinSchema = SchemaStore::getSchemaById($curieWithMajorRev);
            if (is_array($mixinSchema)) {
                $mixinSchema = $this->createSchema($mixinSchema);
            }

            $this->schema->setOption('mixins', array_merge(
                $this->schema->getOption('mixins', []),
                [
                    $mixinSchema,
                ]
            ));
        }
    }
}
