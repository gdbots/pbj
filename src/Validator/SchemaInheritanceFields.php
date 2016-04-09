<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\FieldDescriptor;

class SchemaInheritanceFields implements Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b /* ignored */)
    {
        /** @var FieldDescriptor[] $currentFields */
        /** @var FieldDescriptor[] $inheritedFields */
        $currentFields = $a->getFields();
        $inheritedFields = $a->getInheritedFields();

        $diff = array_intersect(
            array_keys($currentFields),
            array_keys($inheritedFields)
        );
        if (count($diff)) {
            /** @var \ReflectionClass $ref */
            $ref = new \ReflectionClass(new FieldDescriptor('reflection', ['type' => 'string']));

            foreach ($diff as $name) {
                foreach($ref->getProperties() as $property) {
                    // skip
                    if (in_array($property->getName(), ['description', 'languages', 'deprecated'])) {
                        continue;
                    }

                    $method = 'get'.ucfirst($property->getName());
                    if (!$ref->hasMethod($method)) {
                        $method = 'is'.ucfirst($property->getName());
                        if (!$ref->hasMethod($method)) {
                            continue;
                        }
                    }

                    /** @var FieldDescriptor $fa */
                    /** @var FieldDescriptor $fb */
                    $fa = $currentFields[$name];
                    $fb = $inheritedFields[$name];

                    if ($fa && $fb && $fa->$method() != $fb->$method()) {
                        throw new \RuntimeException(sprintf(
                            'The schema "%s" field "%s" is invalid. See inherited mixin fields.',
                            $a->getId()->toString(),
                            $property->getName()
                        ));
                    }
                }
            }
        }
    }
}
