<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\Exception\MissingSchema;

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
     * @throw MissingSchema
     */
    public function parse(array $data)
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
                throw new MissingSchema($data['extends']);
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

        if (isset($field['any_of']) &&
            in_array($field['type'], [
                TypeName::GEO_POINT(),
                TypeName::IDENTIFIER(),
                TypeName::MESSAGE_REF(),
            ])
        ) {
            unset($field['any_of']);
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
            /** @var $enum EnumDescriptor */
            if (!$enum = $this->getEnumById($field['enum']['id'])) {
                throw new \RuntimeException(sprintf(
                    'No Enum with id ["%s"] exist.',
                    $field['enum']['id']
                ));
            }

            switch ($field['type']) {
                case 'int-enum':
                case 'string-enum':
                    if (substr($field['type'], 0, -5) != $enum->getType()) {
                        throw new \RuntimeException(sprintf(
                            'Invalid Enum ["%s"] type. A ["%s-enum"] is required.',
                            $field['enum']['name'],
                            $enum->getType()
                        ));
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        'Invalid Enum type.'
                    ));
            }

            $field['enum'] = $enum;
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
     * @throw MissingSchema
     */
    private function getAnyOf($schema, $curies)
    {
        // can't add yourself to anyof
        if (in_array($schema->getId()->getCurie(), $curies)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add yourself "%s" as to anyof.',
                $schema->getId()->toString()
            ));
        }

        $schemas = [];

        foreach ($curies as $curie) {
            if (!$s = SchemaStore::getSchemaById($curie, true)) {
                throw new MissingSchema($curie);
            }

            $schemas[] = $s;
        }

        return $schemas;
    }

    /**
     * @param string $id
     *
     * @return EnumDescriptor
     *
     * @throw \InvalidArgumentException
     */
    private function getEnumById($id)
    {
        if (!$enum = SchemaStore::getEnumById($id, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot find an enum with id "%s"',
                $id
            ));
        }

        return $enum;
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchema
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
            throw new MissingSchema($curieWithMajorRev);
        }

        return $schema;
    }
}
