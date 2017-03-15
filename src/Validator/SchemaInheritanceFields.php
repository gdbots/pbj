<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Common\Util\StringUtils;
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
                    if (in_array($property->getName(), ['default', 'overridable', 'description', 'languages', 'deprecated'])) {
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

                    if ($fa && $fb) {
                        switch ($method) {
                            case 'getAnyOf':
                                if (!$fa->$method() && !$fb->$method()) {
                                    continue 2;
                                }

                                $ea = [];
                                foreach ((array) $fa->$method() as $schema) {
                                    $ea[] = $this->getClassName($schema);

                                    if ($extends = $schema->getExtends()) {
                                        do {
                                            $ea[] = $this->getClassName($extends);
                                        } while ($extends = $extends->getExtends());
                                    }
                                }

                                $eb = [];
                                foreach ((array) $fb->$method() as $schema) {
                                    $eb[] = $this->getClassName($schema);

                                    if ($extends = $schema->getExtends()) {
                                        do {
                                            $eb[] = $this->getClassName($extends);
                                        } while ($extends = $extends->getExtends());
                                    }
                                }

                                $ea = array_reverse($ea);
                                $eb = array_reverse($eb);

                                $phpClasses = [];
                                foreach ([$ea, $eb] as $classes) {
                                    foreach ($classes as $index => $class) {
                                        if (class_exists($class)) {
                                            continue;
                                        }

                                        if ($index === 0) {
                                            $class = sprintf('class %s {};', $class);
                                        } else {
                                            $class = sprintf('class %s extends %s {};', $class, $classes[$index-1]);
                                        }

                                        if (!in_array($class, $phpClasses)) {
                                            $phpClasses[] = $class;
                                        }
                                    }
                                }

                                if (count($phpClasses)) {
                                    eval(implode("\n", $phpClasses));
                                }

                                // missing exnteded anyOf class
                                if (!isset($ea[0]) || !isset($eb[0])) {
                                    throw new \RuntimeException(sprintf(
                                        'The schema "%s" field "%s" is invalid. See inherited mixin fields.',
                                        $a->getId()->toString(),
                                        $property->getName()
                                    ));
                                }

                                $oa = new $ea[0]();
                                $ob = new $eb[0]();

                                // not class is not inherited
                                if (!$oa instanceof $ob) {
                                    throw new \RuntimeException(sprintf(
                                        'The schema "%s" field "%s" is invalid. See inherited mixin fields.',
                                        $a->getId()->toString(),
                                        $property->getName()
                                    ));
                                }

                                break;

                            default:
                                if ($fa->$method() != $fb->$method()) {
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
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    private function getClassName(SchemaDescriptor $schema)
    {
        return sprintf(
            '%s%s%sV%s',
            StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
            StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
            StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
            preg_replace('/-/', '', $schema->getId()->getVersion())
        );
    }
}
