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
    public static function create(array $data)
    {
        $schemaId = SchemaId::fromString($data['id']);
        $schema = new SchemaDescriptor($schemaId->toString());

        if (isset($data['mixin']) && $data['mixin']) {
            $schema->setIsMixin(true);
        }

        if (isset($data['is_dependent']) && $data['is_dependent']) {
            $schema->setIsDependent(true);
        }

        // default language options
        $options = self::getLanguageOptions($data);
        foreach ($options as $language => $option) {
            $schema->setOption($language, $option);
        }

        // assign enums
        if (isset($data['enums'])) {
            if (isset($data['enums']['enum'])) {
                $enums = self::fixArray($data['enums']['enum'], 'name');
                foreach ($enums as $enum) {
                    $enum = self::getEnumDescriptor($enum);

                    $schema->setOption('enums', array_merge(
                        $schema->getOption('enums', []),
                        [
                            $enum,
                        ]
                    ));
                }
            }

            // add enums language options
            $options = self::getLanguageOptions($data['enums']);
            foreach ($options as $language => $option) {
                $schema->setOptionSubOption($language, 'enums', $option);
            }
        }

        if (isset($data['fields']['field'])) {
            $fields = self::fixArray($data['fields']['field']);
            foreach ($fields as $field) {
                if ($field = self::getFieldDescriptor($schema, $field)) {
                    $schema->addField($field);
                }
            }
        }

        if (isset($data['mixins']['id'])) {
            $mixins = self::fixArray($data['mixins']['id']);
            foreach ($mixins as $curieWithMajorRev) {
                if ($mixin = self::getMixin($schema, $curieWithMajorRev)) {
                    $schema->setOption('mixins', array_merge(
                        $schema->getOption('mixins', []),
                        [
                            $mixin,
                        ]
                    ));
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
    protected static function fixArray($data, $key = null)
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
    protected static function getLanguageOptions(array $data)
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
     * @return EnumDescriptor
     */
    protected static function getEnumDescriptor(array $enum)
    {
        // force default type to be "string"
        if (!isset($enum['type'])) {
            $enum['type'] = 'string';
        }

        $values = [];
        $keys = self::fixArray($enum['option'], 'key');
        foreach ($keys as $key) {
            $values[$key['key']] = $enum['type'] == 'int'
                ? intval($key['value'])
                : (string) $key['value']
            ;
        }

        return new EnumDescriptor($enum['name'], $enum['type'], $values);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $field
     *
     * @return FieldDescriptor|null
     */
    protected static function getFieldDescriptor(SchemaDescriptor $schema, array $field)
    {
        // ignore if no type was assign
        if (!isset($field['type'])) {
            return;
        }

        if (!isset($field['options'])) {
            $field['options'] = [];
        }

        if (isset($field['any_of']['id'])) {
            $field['any_of'] = self::getAnyOf(
                $schema,
                self::fixArray($field['any_of']['id'])
            );
        }
        if (isset($field['any_of']) && count($field['any_of']) === 0) {
            unset($field['any_of']);
        }

        if (isset($field['enum'])) {
            /** @var $providerSchema SchemaDescriptor */
            $providerSchema = self::getEnumProvider($schema, $field['enum']['provider']);

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

            switch ($field['type']) {
                case 'int-enum':
                case 'string-enum':
                    if (substr($field['type'], 0, -5) != $field['options']['enum']->getType()) {
                        throw new \RuntimeException(sprintf(
                            'Invalid Enum ["%s"] type. A ["%s-enum"] is required.',
                            $field['enum']['name'],
                            $field['options']['enum']->getType()
                        ));
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        'Invalid Enum type.'
                    ));
            }

            unset($field['enum']);
        }

        return new FieldDescriptor($field['name'], $field);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param array            $curies
     *
     * @return array
     */
    protected static function getAnyOf($schema, $curies)
    {
        $schemas = [];

        foreach ($curies as $curie) {
            // can't add yourself to anyof
            if ($curie == $schema->getId()->getCurie()) {
                continue;
            }

            $schema = SchemaStore::getSchemaById($curie);
            if (is_array($schema)) {
                $schema = self::create($schema);
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
    protected static function getEnumProvider(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            return $schema;
        }

        $schema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($schema)) {
            $schema = self::create($schema);
        }

        return $schema;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     */
    protected static function getMixin(SchemaDescriptor $schema, $curieWithMajorRev)
    {
        // can't add yourself to mixins
        if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
            return;
        }

        $schema = SchemaStore::getSchemaById($curieWithMajorRev);
        if (is_array($schema)) {
            $schema = self::create($schema);
        }

        return $schema;
    }
}
