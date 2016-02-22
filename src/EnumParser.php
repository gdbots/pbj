<?php

namespace Gdbots\Pbjc;

/**
 * The EnumParser is a tool to create/update enum class descriptors.
 */
class EnumParser
{
    /**
     * Builds an Enum instance from a given set of data.
     *
     * @param array $data
     *
     * @return EnumDescriptor
     */
    public function create(array $data)
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
            $values[$key['key']] = $data['type'] == 'int'
                ? intval($key['value'])
                : (string) $key['value']
            ;
        }

        if (count($values) === 0) {
            return;
        }

        $enum = new EnumDescriptor($enumId, $data['type'], $values);

        // add enums language options
        $options = $this->getLanguageOptions($data);
        foreach ($options as $language => $option) {
            $enum->setLanguage($language, $option);
        }

        return $enum;
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
}
