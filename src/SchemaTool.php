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
     * Returns the singleton instance.
     *
     * @return this
     */
    public static function getInstance()
    {
        return new self();
    }

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
                $enums = $this->fixArray($data['enums']['enum'], 'name');
                foreach ($enums as $enum) {
                    $this->addEnum($enum);
                }
            }

            // add enums language options
            $options = $this->getLanguageOptions($data['enums']);
            foreach ($options as $language => $option) {
                $this->schema->setOptionSubOption($language, 'enums', $option);
            }
        }

        if (isset($data['fields']['field'])) {
            $fields = $this->fixArray($data['fields']['field']);
            foreach ($fields as $field) {
                $this->addField($field);
            }
        }

        if (isset($data['mixins']['id'])) {
            $mixins = $this->fixArray($data['mixins']['id']);
            foreach ($mixins as $curieWithMajorRev) {
                $this->addMixin($curieWithMajorRev);
            }
        }

        if (count($diff = $this->validate()) > 0) {
            throw new \RuntimeException(sprintf(
                'Schema ["%s"] is invalid. Schema has changed dramatically from previous version: [%s]',
                $this->schema->getId()->__toString(),
                json_encode($diff)
            ));
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
    protected function validate()
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($this->schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = self::getInstance()->createSchema($prevSchema);
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
     * @param array $enum
     */
    protected function addEnum(array $enum)
    {
        // force default type to be "string"
        if (!isset($enum['type'])) {
            $enum['type'] = 'string';
        }

        $values = [];
        $keys = $this->fixArray($enum['option'], 'key');
        foreach ($keys as $key) {
            $values[$key['key']] = $enum['type'] == 'int'
                ? intval($key['value'])
                : (string) $key['value']
            ;
        }

        $enum = new EnumDescriptor($enum['name'], $values);

        $this->schema->setOption('enums', array_merge(
            $this->schema->getOption('enums', []),
            [
                $enum,
            ]
        ));
    }

    /**
     * @param array $field
     */
    protected function addField(array $field)
    {
        // ignore if no type was assign
        if (!isset($field['type'])) {
            return;
        }

        if (!isset($field['options'])) {
            $field['options'] = [];
        }

        if (isset($field['any_of']['id'])) {
            $field['any_of'] = $this->getAnyOf(
                $this->fixArray($field['any_of']['id'])
            );
        }
        if (isset($field['any_of']) && count($field['any_of']) === 0) {
            unset($field['any_of']);
        }

        if (isset($field['enum'])) {
            /** @var $providerSchema SchemaDescriptor */
            $providerSchema = $this->getEnumProvider($field['enum']['provider']);

            /** @var $enums EnumDescriptor[] */
            if ($enums = $providerSchema->getOption('enums')) {
                foreach ($enums as $enum) {
                    if ($enum->getName() == $field['enum']['name']) {
                        $field['options']['enum'] = $enum;

                        break;
                    }
                }
            }

            if (!isset($field['options']['enum'])) {
                throw new \RuntimeException(sprintf(
                    'No Enum with provider ["%s"] and name ["%s"] exist.',
                    $field['enum']['provider'],
                    $field['enum']['name']
                ));
            }

            unset($field['enum']);
        }

        $this->schema->addField(new FieldDescriptor($field['name'], $field));
    }

    /**
     * @param array $curies
     *
     * @return array
     */
    protected function getAnyOf($curies)
    {
        $schemas = [];

        foreach ($curies as $curie) {
            // can't add yourself to anyof
            if ($curie == $this->schema->getId()->getCurie()) {
                continue;
            }

            $schema = SchemaStore::getSchemaById($curie);
            if (is_array($schema)) {
                $schema = self::getInstance()->createSchema($schema);
            }

            $schemas[] = $schema;
        }

        return $schemas;
    }

    /**
     * @param string $curieWithMajorRev
     *
     * @return SchemaDescriptor
     */
    protected function getEnumProvider($curieWithMajorRev)
    {
        if ($curieWithMajorRev == $this->schema->getId()->getCurieWithMajorRev()) {
            return $this->schema;
        }

        $schema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($schema)) {
            $schema = self::getInstance()->createSchema($schema);
        }

        return $schema;
    }

    /**
     * @param string $curieWithMajorRev
     */
    protected function addMixin($curieWithMajorRev)
    {
        // can't add yourself to mixins
        if ($curieWithMajorRev == $this->schema->getId()->getCurieWithMajorRev()) {
            continue;
        }

        $mixinSchema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($mixinSchema)) {
            $mixinSchema = self::getInstance()->createSchema($mixinSchema);
        }

        $this->schema->setOption('mixins', array_merge(
            $this->schema->getOption('mixins', []),
            [
                $mixinSchema,
            ]
        ));
    }
}
