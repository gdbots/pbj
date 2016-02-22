<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaDependencyVersion implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b /* ignored */)
    {
        $schemaIds = [];

        $schemaIds = array_merge(
            $this->getExtendsSchemas($a),
            $this->getMixinSchemas($a)
        );

        $schemaCurie = [];
        foreach ($schemaIds as $curieWithMajorRev) {
            list($vendor, $package, $category, $message, $major) = explode(':', $curieWithMajorRev);

            $curie = sprintf('%s:%s:%s:%s', $vendor, $package, $category, $message);

            if (!isset($schemaCurie[$curie])) {
                $schemaCurie[$curie] = [];
            }
            if (!in_array($major, $schemaCurie[$curie])) {
                $schemaCurie[$curie][] = $major;
            }
            if (count($schemaCurie[$curie]) > 1) {
                throw new \RuntimeException(sprintf('Invalid dependency version "%s"', $a->getId()->toString()));
            }
        }
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    private function getExtendsSchemas(SchemaDescriptor $schema)
    {
        $schemaIds = [];

        $check = $schema->getExtends();
        while ($check) {
            $schemaIds[] = $check->getId()->getCurieWithMajorRev();

            $check = $check->getExtends();
        }

        return $schemaIds;
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    private function getMixinSchemas(SchemaDescriptor $schema)
    {
        $schemaIds = [];

        foreach ($schema->getMixins() as $mixin) {
            $schemaIds[] = $mixin->getId()->getCurieWithMajorRev();

            $schemaIds = array_merge(
                $schemaIds,
                $this->getExtendsSchemas($mixin),
                $this->getMixinSchemas($mixin)
            );
        }

        return $schemaIds;
    }
}
