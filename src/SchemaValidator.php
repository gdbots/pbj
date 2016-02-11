<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Validator\Validator;
use Gdbots\Pbjc\Validator\Constraints as Assert;

/**
 * Performs strict validation of the mapping schema.
 */
class SchemaValidator
{
    /**
     * Validates a single schema against prevoius version.
     *
     * @param SchemaDescriptor $schema
     *
     * @throw \InvalidArgumentException
     */
    public static function validateMapping(SchemaDescriptor $schema)
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = self::create($prevSchema);
        }

        if ($violation = Validator::validate($schema->isMixin(), new Assert\EqualTo([
            'value' => $prevSchema->isMixin(),
        ]))) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot change schema mixin state. %s',
                $violation
            ));
        }

        self::validateEnums($schema, $prevSchema);
        self::validateMixins($schema, $prevSchema);
        self::validateFields($schema, $prevSchema);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param SchemaDescriptor $prevSchema
     *
     * @throw \InvalidArgumentException
     */
    protected static function validateEnums(SchemaDescriptor $schema, SchemaDescriptor $prevSchema)
    {
        $current = $schema->getOption('enums', []);
        $previous = $prevSchema->getOption('enums', []);

        foreach ($current as $key => $enum) {
            $current[$enum->getName()] = $enum->toArray();
            unset($current[$key]);
        }

        foreach ($previous as $key => $enum) {
            $previous[$enum->getName()] = $enum->toArray();
            unset($previous[$key]);
        }

        if (count($current) === 0 && count($previous) === 0) {
            return;
        }

        $diff = array_diff(array_keys($previous), array_keys($current));
        if (count($diff)) {
            throw new \InvalidArgumentException(sprintf(
                'One or more of the given enums [%s] was removed.',
                json_encode($diff)
            ));
        }

        foreach ($current as $name => $values) {
            if (!isset($previous[$name])) {
                continue;
            }

            if ($values['type'] !== $previous[$name]['type']) {
                throw new \InvalidArgumentException(sprintf(
                    'The enum "%s" type must be "%s" type.',
                    $name,
                    $previous[$name]['type']
                ));
            }

            $diff = array_diff($previous[$name]['values'], $values['values']);
            if (count($diff)) {
                throw new \InvalidArgumentException(sprintf(
                    'One or more of the given enum "%s" values [%s] was removed.',
                    $name,
                    json_encode($diff)
                ));
            }
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param SchemaDescriptor $prevSchema
     *
     * @throw \InvalidArgumentException
     */
    protected static function validateMixins(SchemaDescriptor $schema, SchemaDescriptor $prevSchema)
    {
        $current = $schema->getOption('mixins', []);
        $previous = $prevSchema->getOption('mixins', []);

        foreach ($current as $key => $mixin) {
            $current[$key] = $mixin->getId()->toString();
        }

        foreach ($previous as $key => $mixin) {
            $previous[$key] = $mixin->getId()->toString();
        }

        if (count($current) === 0 && count($previous) === 0) {
            return;
        }

        $diff = array_diff(array_keys($previous), array_keys($current));
        if (count($diff)) {
            throw new \InvalidArgumentException(sprintf(
                'One or more of the given mixins [%s] was removed.',
                json_encode($diff)
            ));
        }
    }

    /**
     * @param SchemaDescriptor $schema
     * @param SchemaDescriptor $prevSchema
     *
     * @throw \InvalidArgumentException
     */
    protected static function validateFields(SchemaDescriptor $schema, SchemaDescriptor $prevSchema)
    {
        $current = $schema->getFields();
        $previous = $prevSchema->getFields();

        $diff = array_diff(array_keys($previous), array_keys($current));
        if (count($diff)) {
            throw new \InvalidArgumentException(sprintf(
                'One or more of the given fields [%s] was removed.',
                json_encode($diff)
            ));
        }

        foreach ($current as $name => $field) {
            if (!isset($previous[$name])) {
                continue;
            }

            $currentAttributes = $field->toArray();
            $previousAttributes = $previous[$name]->toArray();

            $allowChanges = [
                'pattern',
                'default',
                'use_type_default',
                'overridable',
                'options',
            ];

            $arrayAttributes = [
                'any_of',
            ];

            foreach ($currentAttributes as $attribute => $value) {
                if (in_array($attribute, $allowChanges) || !isset($previousAttributes[$attribute])) {
                    continue;
                }

                $previousValue = $previousAttributes[$attribute];
                switch ($attribute) {
                    case 'any_of':
                        if (!is_array($value)) {
                            $value = [];
                        }
                        foreach ($value as $k => $v) {
                            $value[$k] = $v->getId()->toString();
                        }

                        if (!is_array($previousValue)) {
                            $previousValue = [];
                        }
                        foreach ($previousValue as $k => $v) {
                            $previousValue[$k] = $v->getId()->toString();
                        }

                        break;
                }

                if ($value !== $previousValue) {
                    throw new \InvalidArgumentException(sprintf(
                        'The field "%s" attribute "%s" value must %s %s.',
                        $name,
                        $attribute,
                        in_array($attribute, $arrayAttributes) ? 'contains' : 'be',
                        json_encode($previousValue)
                    ));
                }
            }
        }
    }
}
