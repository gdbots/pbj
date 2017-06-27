<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

interface Generator
{
    /**
     * Generates all code for the given schema descriptor.
     *
     * This tends to produce (varies by language):
     * - message class (the concrete class - curie major)
     * - message interface (curie)
     * - mixin (the schema fields that are "mixed" into the message)
     * - mixin major interface (curie major for the mixin)
     * - mixin interface (curie)
     * - trait (any methods provided by insertion points)
     *
     * @param SchemaDescriptor $schema
     *
     * @return GeneratorResponse
     */
    public function generateSchema(SchemaDescriptor $schema);

    /**
     * Generates an enum.
     *
     * @param EnumDescriptor $enum
     *
     * @return GeneratorResponse
     */
    public function generateEnum(EnumDescriptor $enum);

    /**
     * Generates a manifest of all messages the store provides.
     * This typically configures the MessageResolver.
     *
     * @param SchemaDescriptor[] $schemas
     *
     * @return GeneratorResponse
     */
    public function generateManifest(array $schemas);

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaToClassName(SchemaDescriptor $schema, $withMajor = false);

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaToFqClassName(SchemaDescriptor $schema, $withMajor = false);

    /**
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToClassName(EnumDescriptor $enum);

    /**
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaToNativePackage(SchemaDescriptor $schema);

    /**
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToNativePackage(EnumDescriptor $enum);

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaToNativeImportPath(SchemaDescriptor $schema, $withMajor = false);

    /**
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToNativeImportPath(EnumDescriptor $enum);
}
