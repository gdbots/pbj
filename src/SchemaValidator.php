<?php

namespace Gdbots\Pbjc;

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
     * @return array
     */
    public static function validateMapping(SchemaDescriptor $schema)
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = self::create($prevSchema);
        }

        // convert schema's to arra and compare values
        $currentSchemaArray = json_decode(json_encode($schema), true);
        $prevSchemaArray = json_decode(json_encode($prevSchema), true);

        // check if something got removed or cahnged
        $diff = self::arrayRecursiveDiff($prevSchemaArray, $currentSchemaArray);

        // removed schema id - going to be diff ofcorse.. doh
        if (isset($diff['id'])) {
            unset($diff['id']);
        }

        return $diff;
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    protected static function arrayRecursiveDiff(array $array1, array $array2)
    {
        $diff = array();

        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursiveDiff = self::arrayRecursiveDiff($value, $array2[$key]);
                    if (count($recursiveDiff)) {
                        $diff[$key] = $recursiveDiff;
                    }
                } else {
                    if ($value != $array2[$key]) {
                        $diff[$key] = $value;
                    }
                }
            } else {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }
}
