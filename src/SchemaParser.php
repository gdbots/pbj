<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Exception\MissingSchemaException;

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
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchemaException
     */
    public function create(array $data)
    {
        $schema = new SchemaDescriptor($data['id']);

        // can't extends yourself
        if (isset($data['extends'])) {
            if ($data['extends'] == $schema->getId()->getCurieWithMajorRev()) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot extends yourself "%s".',
                    $schema->getId()->toString()
                ));
            }
            if (!$extendsSchema = SchemaStore::getSchemaById($data['extends'], true)) {
                throw new MissingSchemaException($data['extends']);
            }

            // recursivly check that chain not pointing back to schema
            $check = $extendsSchema->getExtends();
            while ($check) {
                if ($check->getId()->getCurieWithMajorRev() == $schema->getId()->getCurieWithMajorRev()) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid extends chain. Schema "%s" pointing back to you "%s".',
                        $check->getId()->toString(),
                        $schema->getId()->toString()
                    ));
                }

                $check = $check->getExtends();
            }

            $schema->setExtends($extendsSchema);
        }

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

        if (isset($data['mixins']['curie_major'])) {
            $mixins = $this->fixArray($data['mixins']['curie_major']);
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
        // force default type to be "string"
        if (!isset($field['type'])) {
            $field['type'] = 'string';
        }

        if (!isset($field['options'])) {
            $field['options'] = [];
        }

        if (isset($field['any_of']['curie'])) {
            $field['any_of'] = $this->getAnyOf(
                $schema,
                $this->fixArray($field['any_of']['curie'])
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
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchemaException
     */
    private function getAnyOf($schema, $curies)
    {
        $schemas = [];

        foreach ($curies as $curie) {
            // can't add yourself to anyof
            if ($curie == $schema->getId()->getCurie()) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot add yourself "%s" as to anyof.',
                    $schema->getId()->toString()
                ));
            }

            if (!$schema = SchemaStore::getSchemaById($curie, true)) {
                throw new MissingSchemaException($curie);
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
     *
     * @throw MissingSchemaException
     */
    private function getEnumProvider(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            return $schema;
        }

        if (!$schema = SchemaStore::getSchemaById($curieWithMajorRev, true)) {
            throw new MissingSchemaException($curieWithMajorRev);
        }

        return $schema;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchemaException
     */
    private function getMixin(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        // can't add yourself to mixins
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add yourself "%s" as to mixins.',
                $schema->getId()->toString()
            ));
        }

        if (!$schema = SchemaStore::getSchemaById($curieWithMajorRev, true)) {
            throw new MissingSchemaException($curieWithMajorRev);
        }

        return $schema;
    }
}
