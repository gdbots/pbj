<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Util\LanguageBag;
use Gdbots\Pbjc\Util\XmlUtils;

/**
 * The EnumParser is a tool to create/update enum class descriptors.
 */
class EnumParser
{
    /**
     * Reads and validate XML file.
     *
     * @param string $file
     *
     * @return EnumDescriptor[]
     *
     * @throw \RuntimeException
     */
    public function fromFile($file)
    {
        /** @var \DOMDocument $xmlDomDocument */
        if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../xsd/enums.xsd')) {
            throw new \RuntimeException(sprintf(
                'Invalid enums xml file "%s".',
                $file
            ));
        }

        /** @var array $xmlData */
        if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
            throw new \RuntimeException('Invalid enum DOM object.');
        }

        $namespace = $xmlData['enums']['namespace'];

        $filePath = substr($file, 0, -strlen(basename($file)) - 1);
        $enumsPath = str_replace(':', '/', $namespace);

        // invalid enum file location
        if (substr($filePath, -strlen($enumsPath)) !== $enumsPath) {
            throw new \RuntimeException(sprintf(
                'Invalid enums xml directory "%s". Expected sub-directory "%s".',
                $filePath,
                $enumsPath
            ));
        }

        // get language options
        $languages = [];
        foreach ($xmlData['enums'] as $key => $value) {
            if (substr($key, -8) == '-options') {
                $languages[$key] = $value;
            }
        }

        $enums = [];

        if (isset($xmlData['enums']['enum'])) {
            foreach ($xmlData['enums']['enum'] as $enum) {
                $enumId = EnumId::fromString(sprintf('%s:%s', $namespace, $enum['name']));

                // duplicate schema
                if (array_key_exists($enumId->toString(), $enums)) {
                    throw new \RuntimeException(sprintf(
                        'Duplicate enum "%s" in file "%s".',
                        $enumId->toString(),
                        $file
                    ));
                }

                $enums[] = $this->parse(array_merge($enum, $languages, ['namespace' => $namespace]));
            }
        }

        return $enums;
    }

    /**
     * Builds an Enum instance from a given set of data.
     *
     * @param array $data
     *
     * @return EnumDescriptor|null
     *
     * @throw \InvalidArgumentException
     */
    private function parse(array $data)
    {
        // generate id
        $enumId = EnumId::fromString(sprintf('%s:%s', $data['namespace'], $data['name']));

        // force default type to be "string"
        if (!isset($data['type'])) {
            $data['type'] = 'string';
        }

        $values = [];
        $keys = $this->fixArray($data['option'], 'key');
        foreach ($keys as $key) {
            $values[strtoupper($key['key'])] = $data['type'] == 'int'
                ? intval($key['value'])
                : (string) $key['value']
            ;
        }

        if (count($values) === 0) {
            return;
        }

        if (array_search('unknown', array_map('strtolower', array_keys($values))) === false) {
            throw new \InvalidArgumentException(sprintf(
                'Enum "%s" require an "UNKNOWN" key that will be used as default value.',
                $enumId->toString()
            ));
        }

        // add enums language options
        $languages = $this->getLanguageOptions($data);

        $isDeprecated = isset($data['deprecated']) && $data['deprecated'];

        return new EnumDescriptor($enumId, $data['type'], $values, $languages, $isDeprecated);
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
}
