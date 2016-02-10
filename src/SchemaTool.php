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
     * Creates a Schema instance from a given set of metadata.
     *
     * @param array $data
     *
     * @return SchemaDescriptor
     */
    public function createSchema(array $data)
    {
        $schemaId = SchemaId::fromString($data['id']);
        $schema = new SchemaDescriptor($schemaId->__toString());

        if (isset($data['mixin']) && $data['mixin']) {
            $schema->setIsMixin(true);
        }

        if (isset($data['is_dependent']) && $data['is_dependent']) {
            $schema->setIsDependent(true);
        }

        // default language options
        $languages = $this->getLanguageOptions($data);
        foreach ($languages as $language => $value) {
            $schema->setOption($language, $value);
        }

        // assign enums
        if (isset($data['enums'])) {
            if (isset($data['enums']['enum'])) {
                $this->setEnums($schema, $data['enums']['enum']);
            }

            // add enums language options
            $languages = $this->getLanguageOptions($data['enums']);
            foreach ($languages as $language => $value) {
                $schema->setOptionSubOption($language, 'enums', $value);
            }
        }

        if (isset($data['fields']['field'])) {
            $this->setFields($schema, $data['fields']['field']);
        }

        if (isset($data['mixins']['id'])) {
            $this->setMixins($schema, $data['mixins']['id']);
        }

        return $schema;
    }

    /**
     * Validate schema against previous version.
     *
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    public function validate(SchemaDescriptor $schema)
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            return [];
        }

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
     * @param SchemaDescriptor $schema
     * @param array            $data
     */
    protected function setEnums(SchemaDescriptor $schema, array $data)
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

            $schema->setOption('enums', array_merge(
                $schema->getOption('enums', []),
                [
                    $enum,
                ]
            ));
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $data
     */
    protected function setFields(SchemaDescriptor $schema, array $data)
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
                    if ($curie == $schema->getId()->getCurie()) {
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
                if ($item['enum']['provider'] == $schema->getId()->getCurieWithMajorRev()) {
                    $providerSchema = $schema;
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

            $schema->addField(new FieldDescriptor($item['name'], $item));
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array|string     $data
     */
    protected function setMixins(SchemaDescriptor $schema, $data)
    {
        $data = $this->fixArray($data);
        foreach ($data as $curieWithMajorRev) {
            // can't add yourself to mixins
            if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
                continue;
            }

            $mixinSchema = SchemaStore::getSchemaById($curieWithMajorRev);
            if (is_array($mixinSchema)) {
                $mixinSchema = $this->createSchema($mixinSchema);
            }

            $schema->setOption('mixins', array_merge(
                $schema->getOption('mixins', []),
                [
                    $mixinSchema,
                ]
            ));
        }
    }
}
