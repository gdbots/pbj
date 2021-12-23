<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class JsGenerator extends Generator
{
    const LANGUAGE = 'js';
    const EXTENSION = '.js';
    const MANIFEST = 'index';

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

        return $import;
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
    protected function generateMessage(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $id = $schema->getId();
        $className = $this->schemaToClassName($schema, true);
        $file = "{$id->getVendor()}/{$id->getPackage()}";
        if ($id->getCategory()) {
            $file .= "/{$id->getCategory()}";
        }
        $file .= "/{$className}";

        $imports = [
            "import Message from '@gdbots/pbj/Message.js';",
            "import Schema from '@gdbots/pbj/Schema.js';",
        ];

        foreach ($schema->getMixins() as $mixin) {
            $mixinOptions = $mixin->getLanguage(static::LANGUAGE)->get('insertion-points', []);
            if (isset($mixinOptions['methods'])) {
                $imports[] = sprintf(
                    "import %sMixin from '%s/%sMixin.js';",
                    $this->schemaToFqClassName($mixin, true),
                    $this->schemaToNativeNamespace($mixin),
                    $this->schemaToClassName($mixin, true)
                );
            }
        }

        $options = $schema->getLanguage(static::LANGUAGE);
        $insertionPoints = $options->get('insertion-points', []);

        $fields = $this->resolveFields($schema);
        if (!empty($fields)) {
            $imports[] = "import Fb from '@gdbots/pbj/FieldBuilder.js';";
            $imports[] = "import T from '@gdbots/pbj/types/index.js';";
        }

        $imports = array_merge($imports, $this->extractImportsFromFields($fields));
        $imports = array_merge($imports, explode(PHP_EOL, $insertionPoints['imports'] ?? ''));

        $parameters = [
            'schema'  => $schema,
            'fields'  => $fields,
            'imports' => $this->optimizeImports($imports),
            'methods' => $insertionPoints['methods'] ?? '',
        ];

        $response->addFile($this->generateOutputFile('message.twig', $file, $parameters));
    }

    protected function resolveFields(SchemaDescriptor $schema): array
    {
        $fields = [];

        foreach ($schema->getMixins() as $mixin) {
            foreach ($mixin->getFields() as $field) {
                $fields[$field->getName()] = $field;
            }
        }

        foreach ($schema->getFields() as $field) {
            $fields[$field->getName()] = $field;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMixin(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $options = $schema->getLanguage(static::LANGUAGE);
        $insertionPoints = $options->get('insertion-points', []);
        if (!isset($insertionPoints['methods'])) {
            return;
        }

        $id = $schema->getId();
        $className = $this->schemaToClassName($schema, true);
        $file = "{$id->getVendor()}/{$id->getPackage()}";
        if ($id->getCategory()) {
            $file .= "/{$id->getCategory()}";
        }
        $file .= "/{$id->getMessage()}/{$className}Mixin";

        $parameters = [
            'mixin'   => $schema,
            'imports' => $this->optimizeImports(explode(PHP_EOL, $insertionPoints['imports'] ?? '')),
            'methods' => $insertionPoints['methods'],
        ];

        $response->addFile($this->generateOutputFile('mixin.twig', $file, $parameters));
    }

    /**
     * @param FieldDescriptor[] $fields
     *
     * @return string[]
     */
    protected function extractImportsFromFields(array $fields)
    {
        $imports = [];

        foreach ($fields as $field) {
            $options = $field->getLanguage(static::LANGUAGE);
            $imports = array_merge($imports, explode(PHP_EOL, $options->get('imports', '')));

            if ($field->getFormat()) {
                $imports[] = "import Format from '@gdbots/pbj/enums/Format.js';";
            }

            switch ($field->getType()->getTypeName()) {
                case TypeName::INT_ENUM;
                case TypeName::STRING_ENUM;
                    $enum = $field->getEnum();
                    $imports[] = sprintf(
                        "import %s from '%s/%s.js';",
                        $this->enumToClassName($enum),
                        $this->enumToNativeNamespace($enum),
                        $this->enumToClassName($enum)
                    );
                    break;

                default:
                    break;
            }
        }

        return $imports;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            if (null === $field->getLanguage(static::LANGUAGE)->get('default', null)) {
                $default = $field->getDefault();
                if (is_array($default)) {
                    $default = count($default) ? current($default) : null;
                }

                $enumKey = 'unknown';
                if ($enum->hasValue($default)) {
                    $enumKey = $enum->getKeyByValue($default);
                }

                $field->getLanguage(static::LANGUAGE)->set(
                    'default',
                    sprintf('%s.%s', $this->enumToClassName($enum), strtoupper($enumKey))
                );

                if (strlen($default ?? '') === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }
}
