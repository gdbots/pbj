<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class PhpGenerator extends Generator
{
    const LANGUAGE = 'php';
    const EXTENSION = '.php';

    /**
     * {@inheritdoc}
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        $className = $this->enumToClassName($enum);
        $psr = $this->enumToNativeNamespace($enum);
        $file = str_replace('\\', '/', "{$psr}\\{$className}");

        $response = new GeneratorResponse();
        $response->addFile($this->generateOutputFile('enum.twig', $file, ['enum' => $enum]));
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function schemaToNativePackage(SchemaDescriptor $schema)
    {
        $ns = parent::schemaToNativePackage($schema);
        if (null !== $ns) {
            return $ns;
        }

        $id = $schema->getId();
        $vendor = StringUtil::toCamelFromSlug($id->getVendor());
        return "{$vendor}\\Schemas";
    }

    /**
     * {@inheritdoc}
     */
    public function enumToNativePackage(EnumDescriptor $enum)
    {
        $ns = parent::enumToNativePackage($enum);
        if (null !== $ns) {
            return $ns;
        }

        $id = $enum->getId();
        $vendor = StringUtil::toCamelFromSlug($id->getVendor());
        return "{$vendor}\\Schemas";
    }

    /**
     * {@inheritdoc}
     */
    public function schemaToNativeNamespace(SchemaDescriptor $schema)
    {
        $ns = $this->schemaToNativePackage($schema);
        $id = $schema->getId();
        $package = StringUtil::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        $psr = "{$ns}\\{$package}";
        if ($id->getCategory()) {
            $category = StringUtil::toCamelFromSlug($id->getCategory());
            $psr .= "\\{$category}";
        }

        if ($schema->isMixinSchema()) {
            $message = StringUtil::toCamelFromSlug($id->getMessage());
            return "{$psr}\\{$message}";
        }

        return $psr;
    }

    /**
     * {@inheritdoc}
     */
    public function enumToNativeNamespace(EnumDescriptor $enum)
    {
        $ns = $this->enumToNativePackage($enum);
        $id = $enum->getId();
        $package = StringUtil::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        return "{$ns}\\{$package}\\Enum";
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMessage(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $className = $this->schemaToClassName($schema, true);
        $psr = $this->schemaToNativeNamespace($schema);
        $file = str_replace('\\', '/', "{$psr}\\{$className}");

        $imports = [
            'use Gdbots\Pbj\AbstractMessage;',
            'use Gdbots\Pbj\Schema;',
        ];

        foreach ($schema->getMixins() as $mixin) {
            $mixinOptions = $mixin->getLanguage(static::LANGUAGE)->get('insertion-points', []);
            if (isset($mixinOptions['methods'])) {
                $imports[] = sprintf(
                    'use %s\%sTrait as %sTrait;',
                    $this->schemaToNativeNamespace($mixin),
                    $this->schemaToClassName($mixin, true),
                    $this->schemaToFqClassName($mixin, true)
                );
            }
        }

        $options = $schema->getLanguage(static::LANGUAGE);
        $insertionPoints = $options->get('insertion-points', []);

        $fields = $this->resolveFields($schema);
        if (!empty($fields)) {
            $imports[] = 'use Gdbots\Pbj\FieldBuilder as Fb;';
            $imports[] = 'use Gdbots\Pbj\Type as T;';
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
        $className = $this->schemaToClassName($schema, true);
        $psr = $this->schemaToNativeNamespace($schema);
        $file = str_replace('\\', '/', "{$psr}\\{$className}Mixin");

        $imports = [
            'use Gdbots\Pbj\Field;',
            'use Gdbots\Pbj\SchemaId;',
        ];

        if ($schema->hasFields()) {
            $imports[] = 'use Gdbots\Pbj\FieldBuilder as Fb;';
            $imports[] = 'use Gdbots\Pbj\Type as T;';
        }

        $imports = array_merge($imports, $this->extractImportsFromFields($schema->getFields()));
        $parameters = [
            'mixin'   => $schema,
            'imports' => $this->optimizeImports($imports),
        ];

        $response->addFile($this->generateOutputFile('mixin.twig', $file, $parameters));
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMixinTrait(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $options = $schema->getLanguage(static::LANGUAGE);
        $insertionPoints = $options->get('insertion-points', []);
        if (!isset($insertionPoints['methods'])) {
            return;
        }

        $className = $this->schemaToClassName($schema, true);
        $psr = $this->schemaToNativeNamespace($schema);
        $file = str_replace('\\', '/', "{$psr}\\{$className}Trait");

        $imports = ['use Gdbots\Pbj\Schema;'];
        $imports = array_merge($imports, explode(PHP_EOL, $insertionPoints['imports'] ?? ''));

        $parameters = [
            'mixin'   => $schema,
            'imports' => $this->optimizeImports($imports),
            'methods' => $insertionPoints['methods'],
        ];

        $response->addFile($this->generateOutputFile('mixin-trait.twig', $file, $parameters));
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
            $imports = array_merge($imports, explode(PHP_EOL, $options->get('imports')));

            if ($field->getFormat()) {
                $imports[] = 'use Gdbots\Pbj\Enum\Format;';
            }

            switch ($field->getType()->getTypeName()->getValue()) {
                case TypeName::INT_ENUM;
                case TypeName::STRING_ENUM;
                    $enum = $field->getEnum();
                    $imports[] = sprintf(
                        'use %s\%s;',
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
                    sprintf('%s::%s()', $this->enumToClassName($enum), strtoupper($enumKey))
                );

                if (strlen($default) === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }
}
