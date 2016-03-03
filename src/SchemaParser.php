<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\Util\LanguageBag;
use Gdbots\Pbjc\Util\XmlUtils;
use Gdbots\Pbjc\Exception\MissingSchema;

/**
 * The SchemaParser is a tool to create/update schemas class descriptors.
 */
class SchemaParser
{
    /** @var array */
    protected $files = [];

    /**
     * Reads and validate XML file.
     *
     * @param string $file
     *
     * @return SchemaDescriptor|null
     *
     * @throw \RuntimeException
     * @throw MissingSchema
     */
    public function fromFile($file)
    {
        if (!array_key_exists($file, $this->files)) {

            // invalid schema
            if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../xsd/schema.xsd')) {
                throw new \RuntimeException(sprintf(
                    'Invalid schema xml file "%s".',
                    $file
                ));
            }

            // bad \DOMDocument
            if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                throw new \RuntimeException('Invalid schema DOM object.');
            }

            $schemaId = SchemaId::fromString($xmlData['schema']['id']);

            $filePath = substr($file, 0, -strlen(basename($file)) - 1);
            $schemaPath = str_replace(':', '/', $schemaId->getCurie());

            // invalid schema file location
            if (substr($filePath, -strlen($schemaPath)) !== $schemaPath) {
                throw new \RuntimeException(sprintf(
                    'Invalid schema xml directory "%s". Expected sub-directory "%s".',
                    $filePath,
                    $schemaPath
                ));
            }

            // validate version to file
            if (basename($file) != 'latest.xml'
                && basename($file) != sprintf('%s.xml', $schemaId->getVersion()->toString())
            ) {
                throw new \RuntimeException(sprintf(
                    'Invalid schema xml file "%s" name. Expected name "%s.xml".',
                    $file,
                    $schemaId->getVersion()->toString()
                ));
            }

            // check "latest.xml"
            $latestPath = sprintf('%s/latest.xml', $filePath);
            if (!file_exists($latestPath)) {
                file_put_contents($latestPath, file_get_contents($file));

                $this->files[$latestPath] = $xmlData['schema'];
            }
            if (isset($this->files[$latestPath])) {
                $version = SchemaId::fromString($this->files[$latestPath]['id'])->getVersion()->toString();

                if (version_compare($schemaId->getVersion()->toString(), $version) === 1) {
                    file_put_contents($latestPath, file_get_contents($file));

                    $this->files[$latestPath] = $xmlData['schema'];
                }
            }

            // override or create "latest" version file
            $versionPath = sprintf('%s/%s.xml', $filePath, $schemaId->getVersion()->toString());
            if (basename($file) == 'latest.xml' && !file_exists($versionPath)) {
                file_put_contents($versionPath, file_get_contents($file));
            }

            $this->files[$file] = $xmlData['schema'];
        }

        return $this->parse($this->files[$file]);
    }

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
    private function parse(array $data)
    {
        $schemaId = SchemaId::fromString($data['id']);

        $parameters = [
            'deprecated' => isset($data['deprecated']) && $data['deprecated'],
        ];

        // can't extends yourself
        if (isset($data['extends'])) {
            if ($data['extends'] == $schemaId->getCurieWithMajorRev()) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot extends yourself "%s".',
                    $schemaId->toString()
                ));
            }
            if (!$extendsSchema = SchemaStore::getSchemaById($data['extends'], true)) {
                throw new MissingSchema($data['extends']);
            }

            // recursivly check that chain not pointing back to schema
            $check = $extendsSchema->getExtends();
            while ($check) {
                if ($check->getId()->getCurieWithMajorRev() == $schemaId->getCurieWithMajorRev()) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid extends chain. Schema "%s" pointing back to you "%s".',
                        $check->getId()->toString(),
                        $schemaId->toString()
                    ));
                }

                $check = $check->getExtends();
            }

            $parameters['extends'] = $extendsSchema;
        }

        if (isset($data['mixin']) && $data['mixin']) {
            $parameters['isMixin'] = true;
        }

        // default language options
        $parameters['languages'] = $this->getLanguageOptions($data);

        if (isset($data['fields']['field'])) {
            $fieldsData = $this->fixArray($data['fields']['field'], 'name');
            if (count($fieldsData)) {
                $parameters['fields'] = [];
            }
            foreach ($fieldsData as $field) {
                if ($field = $this->getFieldDescriptor($schemaId, $field)) {
                    $parameters['fields'][] = $field;
                }
            }
        }

        if (isset($data['mixins']['curie-major'])) {
            $mixinsData = $this->fixArray($data['mixins']['curie-major']);
            if (count($mixinsData)) {
                $parameters['mixins'] = [];
            }
            foreach ($mixinsData as $curieWithMajorRev) {
                if ($mixin = $this->getMixin($schemaId, $curieWithMajorRev)) {
                    $parameters['mixins'][] = $mixin;
                }
            }
        }

        return new SchemaDescriptor($schemaId, $parameters);
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
        $options = new LanguageBag();

        foreach ($data as $key => $value) {
            if (substr($key, -8) == '-options') {
                $language = substr($key, 0, -8); // remove "-options"

                if (is_array($value)) {
                    $value = new LanguageBag($value);
                }

                $options->set($language, $value);
            }
        }

        return $options;
    }

    /**
     * @param SchemaId $schemaId
     * @param array    $field
     *
     * @return FieldDescriptor|null
     */
    private function getFieldDescriptor(SchemaId $schemaId, array $field)
    {
        // force default type to be "string"
        if (!isset($field['type'])) {
            $field['type'] = 'string';
        }

        if (!isset($field['options'])) {
            $field['options'] = [];
        }

        if (isset($field['any-of']) &&
            in_array($field['type'], [
                TypeName::GEO_POINT(),
                TypeName::IDENTIFIER(),
                TypeName::MESSAGE_REF(),
            ])
        ) {
            unset($field['any-of']);
        }
        if (isset($field['any-of']['curie'])) {
            $field['any-of'] = $this->getAnyOf(
                $schemaId,
                $this->fixArray($field['any-of']['curie'])
            );
        }
        if (isset($field['any-of']) && count($field['any-of']) === 0) {
            unset($field['any-of']);
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
     * @param SchemaId $schemaId
     * @param array    $curies
     *
     * @return array
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchema
     */
    private function getAnyOf($schemaId, $curies)
    {
        // can't add yourself to anyof
        if (in_array($schemaId->getCurie(), $curies)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add yourself "%s" as to anyof.',
                $schemaId->toString()
            ));
        }

        $schemas = [];

        foreach ($curies as $curie) {
            if (!$schema = SchemaStore::getSchemaById($curie, true)) {
                throw new MissingSchema($curie);
            }

            $schemas[] = $schema;
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
     * @param SchemaId $schemaId
     * @param string   $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     *
     * @throw \InvalidArgumentException
     * @throw MissingSchema
     */
    private function getMixin(SchemaId $schemaId, $curieWithMajorRev)
    {
        // can't add yourself to mixins
        if ($curieWithMajorRev == $schemaId->getCurieWithMajorRev()) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add yourself "%s" as to mixins.',
                $schemaId->toString()
            ));
        }

        if (!$schema = SchemaStore::getSchemaById($curieWithMajorRev, true)) {
            throw new MissingSchema($curieWithMajorRev);
        }

        return $schema;
    }
}
