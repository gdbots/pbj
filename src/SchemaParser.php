<?php

namespace Gdbots\Pbjc;

/**
 * The SchemaParser is a tool to create/update schemas class descriptors.
 */
class SchemaParser
{
    /**
     * Builds a Schema instance from a given set of data.
     *
     * @param array $data
     *
     * @return SchemaDescriptor
     */
    public function create(array $data)
    {
        $schemaId = SchemaId::fromString($data['id']);
        $schema = new SchemaDescriptor($schemaId->toString());

        if (isset($data['mixin']) && $data['mixin']) {
            $schema->setIsMixin(true);
        }

        // default language options
        $options = $this->getLanguageOptions($data);
        foreach ($options as $language => $option) {
            $schema->setLanguage($language, $option);
        }

        // assign enums
        if (isset($data['enums'])) {
            if (isset($data['enums']['enum'])) {
                $enums = $this->fixArray($data['enums']['enum'], 'name');
                foreach ($enums as $enum) {
                    if ($enum = $this->getEnumDescriptor($enum)) {
                        $schema->addEnum($enum);
                    }
                }
            }

            // add enums language options
            $options = $this->getLanguageOptions($data['enums']);
            foreach ($options as $language => $option) {
                $schema->setLanguageKey($language, 'enums', $option);
            }
        }

        if (isset($data['fields']['field'])) {
            $fields = $this->fixArray($data['fields']['field']);
            foreach ($fields as $field) {
                if ($field = $this->getFieldDescriptor($schema, $field)) {
                    $schema->addField($field);
                }
            }
        }

        if (isset($data['mixins']['id'])) {
            $mixins = $this->fixArray($data['mixins']['id']);
            foreach ($mixins as $curieWithMajorRev) {
                if ($mixin = $this->getMixin($schema, $curieWithMajorRev)) {
                    $schema->addMixin($mixin);
                }
            }
        }

        return $schema;
    }

    /**
     * @param array|string $data
     * @param string       $key
     *
     * @return array
     */
    private function fixArray($data, $key = null)
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
    private function getLanguageOptions(array $data)
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
     *
     * @return EnumDescriptor|null
     */
    private function getEnumDescriptor(array $enum)
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

        if (count($values) === 0) {
            return;
        }

        return new EnumDescriptor($enum['name'], $enum['type'], $values);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $field
     *
     * @return FieldDescriptor|null
     */
    private function getFieldDescriptor(SchemaDescriptor $schema, array $field)
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
                $schema,
                $this->fixArray($field['any_of']['id'])
            );
        }
        if (isset($field['any_of']) && count($field['any_of']) === 0) {
            unset($field['any_of']);
        }

        if (isset($field['enum'])) {
            /** @var $providerSchema SchemaDescriptor */
            $providerSchema = $this->getEnumProvider($schema, $field['enum']['provider']);

            /* @var $enums EnumDescriptor[] */
            $matchEnum = null;
            if ($enums = $providerSchema->getEnums()) {
                foreach ($enums as $enum) {
                    if ($enum->getName() == $field['enum']['name']) {
                        $matchEnum = $enum;
                        break;
                    }
                }
            }

            if (!$matchEnum) {
                throw new \RuntimeException(sprintf(
                    'No Enum with provider ["%s"] and name ["%s"] exist.',
                    $field['enum']['provider'],
                    $field['enum']['name']
                ));
            }

            switch ($field['type']) {
                case 'int-enum':
                case 'string-enum':
                    if (substr($field['type'], 0, -5) != $matchEnum->getType()) {
                        throw new \RuntimeException(sprintf(
                            'Invalid Enum ["%s"] type. A ["%s-enum"] is required.',
                            $field['enum']['name'],
                            $matchEnum->getType()
                        ));
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        'Invalid Enum type.'
                    ));
            }

            $field['enum'] = $matchEnum;
        }

        return new FieldDescriptor($field['name'], $field);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $curies
     *
     * @return array
     */
    private function getAnyOf($schema, $curies)
    {
        $schemas = [];

        foreach ($curies as $curie) {
            // can't add yourself to anyof
            if ($curie == $schema->getId()->getCurie()) {
                continue;
            }

            $schema = SchemaStore::getSchemaById($curie);
            if (is_array($schema)) {
                $schema = $this->create($schema);
            }

            $schemas[] = $schema;
        }

        return $schemas;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $curieWithMajorRev
     *
     * @return SchemaDescriptor
     */
    private function getEnumProvider(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            return $schema;
        }

        $schema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($schema)) {
            $schema = $this->create($schema);
        }

        return $schema;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     */
    private function getMixin(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        // can't add yourself to mixins
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            return;
        }

        $schema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($schema)) {
            $schema = $this->create($schema);
        }

        return $schema;
    }
}
