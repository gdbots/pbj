<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class JsGenerator extends Generator
{
    const LANGUAGE = 'js';
    const EXTENSION = '.js';

    /**
     * {@inheritdoc}
     */
    public function schemaToNativePackage(SchemaDescriptor $schema)
    {
        return parent::schemaToNativePackage($schema) ?: "@{$schema->getId()->getVendor()}/schemas";
    }

    /**
     * {@inheritdoc}
     */
    public function enumToNativePackage(EnumDescriptor $enum)
    {
        return parent::enumToNativePackage($enum) ?: "@{$enum->getId()->getVendor()}/schemas";
    }

    /**
     * {@inheritdoc}
     */
    public function schemaToNativeNamespace(SchemaDescriptor $schema)
    {
        $package = $this->schemaToNativePackage($schema);
        $id = $schema->getId();
        $import = "{$package}/{$id->getVendor()}/{$id->getPackage()}";
        if ($id->getCategory()) {
            $import .= "/{$id->getCategory()}";
        }

        if ($schema->isMixinSchema()) {
            return "{$import}/{$id->getMessage()}";
        }

        return "{$import}";
    }

    /**
     * {@inheritdoc}
     */
    public function enumToNativeNamespace(EnumDescriptor $enum)
    {
        $package = $this->enumToNativePackage($enum);
        $id = $enum->getId();
        return "{$package}/{$id->getVendor()}/{$id->getPackage()}/enums";
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        $id = $enum->getId();
        $className = $this->enumToClassName($enum);
        $file = "{$id->getVendor()}/{$id->getPackage()}/enums/{$className}";

        $response = new GeneratorResponse();
        $response->addFile($this->generateOutputFile('enum.twig', $file, ['enum' => $enum]));
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMixinTrait(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $id = $schema->getId();
        $className = $this->schemaToClassName($schema, true);
        $file = "{$id->getVendor()}/{$id->getPackage()}";
        if ($id->getCategory()) {
            $file .= "/{$id->getCategory()}";
        }

        $file .= "/{$id->getMessage()}/{$className}";
        $parameters = [
            'mixin' => $schema,
            'imports' => '// imports',
            'methods' => '// methods',
        ];

        $response = new GeneratorResponse();
        $response->addFile($this->generateOutputFile('mixin-trait.twig', $file, $parameters));
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMessage(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $id = $schema->getId();
        $file = str_replace(['::', ':'], [':', '/'], $id->getCurie()) . self::EXTENSION;
        $parameters = [
            'schema' => $schema,
        ];

        $response->addFile($this->generateOutputFile('message.twig', $file, $parameters));
    }

    /**
     * {@inheritdoc}
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            if (!$instance = $field->getLanguage(static::LANGUAGE)->get('instance')) {
                $instance = [
                    'package'   => $enum->getLanguage(static::LANGUAGE)->get('package'),
                    'classname' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                ];

                $field->getLanguage(static::LANGUAGE)->set('instance', $instance);
            }

            if (null === $field->getLanguage(static::LANGUAGE)->get('default', null)) {
                $default = $field->getDefault();
                if (is_array($default)) {
                    $default = count($default) ? current($default) : null;
                }

                $enumKey = 'unknown';
                if ($enum->hasValue($default)) {
                    $enumKey = $enum->getKeyByValue($default);
                }

                $field->getLanguage(static::LANGUAGE)->set('default', sprintf('%s.%s', $instance['classname'], strtoupper($enumKey)));

                if (strlen($default) === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }
}
