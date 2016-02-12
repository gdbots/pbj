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

        $validator = Validator::createValidator()
            ->add(
                $schema->isMixinSchema(),
                new Assert\IsMixin([
                    'value' => $prevSchema->isMixinSchema(),
                ])
            )
            ->add(
                array_keys($schema->getMixins()),
                new Assert\ExtendChoice([
                    'choices' => array_keys($prevSchema->getMixins()),
                ])
            )
            ->add(
                array_keys($schema->getEnums()),
                new Assert\ExtendChoice([
                    'choices' => array_keys($prevSchema->getEnums()),
                ])
            )
            ->add(
                array_keys($schema->getFields()),
                new Assert\ExtendChoice([
                    'choices' => array_keys($prevSchema->getFields()),
                ])
            )
        ;

        // add enums validator rules
        foreach ($schema->getEnums() as $enum) {
            if (!$compare = $prevSchema->getEnum($enum->getName())) {
                continue;
            }

            $validator->add($enum, new Assert\Enum(['value' => $compare]));
        }

        // add fields validator rules
        foreach ($schema->getFields() as $field) {
            if (!$compare = $prevSchema->getField($field->getName())) {
                continue;
            }

            $validator->add(
                $field,
                new Assert\Field([
                    'value' => $compare,
                    'ignore' => [
                        'pattern',
                        'default',
                        'use_type_default',
                        'overridable',
                        'any_of',
                        'enum',
                        'languages',
                    ]
                ])
            );
        }

        // run..
        $validator->validate();
    }
}
