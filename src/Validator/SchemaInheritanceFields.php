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
                        $error = null;

                        switch ($method) {
                            case 'getAnyOf':
                                if (!$fa->$method() && !$fb->$method()) {
                                    continue 2;
                                }

                                $ea = [];
                                foreach ((array) $fa->$method() as $schema) {
                                    $ea[(string) $schema] = $this->getClassName($schema);

                                    if ($extends = $schema->getExtends()) {
                                        do {
                                            $ea[(string) $extends] = $this->getClassName($extends);
                                        } while ($extends = $extends->getExtends());
                                    }
                                }

                                $eb = [];
                                foreach ((array) $fb->$method() as $schema) {
                                    $eb[(string) $schema] = $this->getClassName($schema);

                                    if ($extends = $schema->getExtends()) {
                                        do {
                                            $eb[(string) $extends] = $this->getClassName($extends);
                                        } while ($extends = $extends->getExtends());
                                    }
                                }

                                $ea = array_reverse($ea);
                                $eb = array_reverse($eb);

                                $phpClasses = [];
                                foreach ([$ea, $eb] as $classes) {
                                    $i = -1;
                                    $isBaseClass = false;
                                    foreach ($classes as $class) {
                                        $i++;

                                        if (class_exists($class)) {
                                            continue;
                                        }

                                        if (!$isBaseClass) {
                                            $class = sprintf('class %s {};', $class);
                                            $isBaseClass = true;
                                        } else {
                                            $class = sprintf('class %s extends %s {};', $class, array_values($classes)[$i-1]);
                                        }

                                        if (!in_array($class, $phpClasses)) {
                                            $phpClasses[] = $class;
                                        }
                                    }
                                }

                                if (count($phpClasses)) {
                                    eval(implode("\n", $phpClasses));
                                }

                                if (0 === count($ea)) {
                                    $error = sprintf(
                                        'The schema "%s" field "%s" required at least schema ("%s")',
                                        $a->getId()->toString(),
                                        $property->getName(),
                                        implode('", "', array_keys($eb))
                                    );
                                }
                                if (0 === count($eb)) {
                                    $error = sprintf(
                                        'The schema "%s" field "%s" can\'t include schema',
                                        $a->getId()->toString(),
                                        $property->getName()
                                    );
                                }

                                foreach ($ea as $schemadId => $class) {
                                    $oa = new $class();

                                    $found = false;
                                    foreach ($eb as $class) {
                                        $ob = new $class();

                                        if ($oa instanceof $ob) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if (!$found) {
                                        $error = sprintf(
                                            'The schema "%s" field "%s" contains an invalid "%s" schema',
                                            $a->getId()->toString(),
                                            $property->getName(),
                                            $schemadId
                                        );
                                    }
                                }

                                break;

                            default:
                                if ($fa->$method() != $fb->$method()) {
                                    $error = sprintf(
                                        'The schema "%s" field "%s" is invalid',
                                        $a->getId()->toString(),
                                        $property->getName()
                                    );
                                }
                        }

                        if ($error) {
                            throw new \RuntimeException(sprintf('%s. See inherited mixin fields.', $error));
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
