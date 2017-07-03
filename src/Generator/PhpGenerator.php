<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

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
        $vendor = StringUtils::toCamelFromSlug($id->getVendor());
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
        $vendor = StringUtils::toCamelFromSlug($id->getVendor());
        return "{$vendor}\\Schemas";
    }

    /**
     * {@inheritdoc}
     */
    public function schemaToNativeNamespace(SchemaDescriptor $schema)
    {
        $ns = $this->schemaToNativePackage($schema);
        $id = $schema->getId();
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        $psr = "{$ns}\\{$package}";
        if ($id->getCategory()) {
            $category = StringUtils::toCamelFromSlug($id->getCategory());
            $psr .= "\\{$category}";
        }

        if ($schema->isMixinSchema()) {
            $message = StringUtils::toCamelFromSlug($id->getMessage());
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
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        return "{$ns}\\{$package}\\Enum";
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMixinMajorInterface(SchemaDescriptor $schema, GeneratorResponse $response)
    {
        $className = $this->schemaToClassName($schema, true);
        $psr = $this->schemaToNativeNamespace($schema);
        $file = str_replace('\\', '/', "{$psr}\\{$className}");
        $response->addFile(
            $this->generateOutputFile('mixin-major-interface.twig', $file, ['mixin' => $schema])
        );
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
                    sprintf('%s.%s()', $this->enumToClassName($enum), strtoupper($enumKey))
                );

                if (strlen($default) === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function xxupdateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            if (!$className = $field->getLanguage(static::LANGUAGE)->get('classname')) {
                $namespace = $enum->getLanguage(static::LANGUAGE)->get('namespace');
                if (substr($namespace, 0, 1) == '\\') {
                    $namespace = substr($namespace, 1);
                }

                $className =
                    sprintf('%s\\%s',
                        $namespace,
                        StringUtils::toCamelFromSlug($enum->getId()->getName())
                    );

                $field->getLanguage(static::LANGUAGE)->set('classname', $className);
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

                $field->getLanguage(static::LANGUAGE)->set('default', sprintf('%s::%s()', substr($className, strrpos($className, '\\') + 1), strtoupper($enumKey)));

                if (strlen($default) === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        $filename = str_replace([
            '{className}',
        ], [
            StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
        ], $filename);

        $directory = str_replace('\\', '/', $schema->getLanguage(static::LANGUAGE)->get('namespace'));

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        $templates = [
            'curie-interface.twig' => '{className}',
            'message.twig'         => '{className}V{major}',
        ];

        if ($schema->isMixinSchema()) {
            $templates = [
                'curie-interface.twig'       => '{className}',
                'curie-major-interface.twig' => '{className}V{major}',
                'mixin.twig'                 => '{className}V{major}Mixin',
            ];

            // ignore empty trait classes
            if (count($schema->getMixins()) || $schema->getLanguage(static::LANGUAGE)->get('insertion-points')) {
                $templates['trait.twig'] = '{className}V{major}Trait';
            }
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function xxgenerateEnum(EnumDescriptor $enum)
    {
        $namespace = $enum->getLanguage(static::LANGUAGE)->get('namespace');
        if (substr($namespace, 0, 1) == '\\') {
            $namespace = substr($namespace, 1);
        }

        $className = StringUtils::toCamelFromSlug($enum->getId()->getName());

        $filename =
            sprintf('%s/%s/%s%s',
                $this->compileOptions->getOutput(),
                str_replace('\\', '/', $namespace),
                str_replace('\\', '/', $className),
                static::EXTENSION
            );

        $response = new GeneratorResponse();

        $response->addFile($this->generateOutputFile(
            'enum.twig',
            $filename,
            [
                'enum'      => $enum,
                'className' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                'isInt'     => is_int(current($enum->getValues())),
            ]
        ));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function xxgenerateManifest(array $schemas)
    {
        $messages = [];

        if (!$filename = $this->compileOptions->getManifest()) {
            return;
        }

        // extract previous schemas
        if (file_exists($filename)) {
            $content = file_get_contents($filename);

            if (preg_match_all('/\'([a-z0-9-]+:[a-z0-9\.-]+:[a-z0-9-]+?:[a-z0-9-]+(:v[0-9]+)?)\' => \'(.*)\'/', $content, $matches) !== false) {
                foreach ($matches[1] as $key => $value) {
                    $messages[$value] = $matches[3][$key];
                }
            }
        }

        // merge with selected schemas (only non-mixin schema's)

        /** @var SchemaDescriptor $schema */
        foreach ($schemas as $schema) {
            if ($schema->isMixinSchema()) {
                continue;
            }

            if (!array_key_exists($schema->getId()->getCurie(), $messages)) {
                $messages[$schema->getId()->getCurie()] = sprintf(
                    '%s\%sV%d',
                    $schema->getLanguage(static::LANGUAGE)->get('namespace'),
                    StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                    $schema->getId()->getVersion()->getMajor()
                );
            }

            if (SchemaStore::hasOtherSchemaMajorRev($schema->getId())) {
                /** @var SchemaDescriptor $s */
                foreach (SchemaStore::getOtherSchemaMajorRev($schema->getId()) as $s) {
                    if (!array_key_exists($s->getId()->getCurieWithMajorRev(), $messages)) {
                        $messages[$s->getId()->getCurieWithMajorRev()] = sprintf(
                            '%s\%sV%d',
                            $s->getLanguage(static::LANGUAGE)->get('namespace'),
                            StringUtils::toCamelFromSlug($s->getId()->getMessage()),
                            $s->getId()->getVersion()->getMajor()
                        );
                    }
                }
            }
        }

        // delete invalid schemas
        foreach ($messages as $key => $value) {
            if (!SchemaStore::getSchemaById($key, true)) {
                unset($messages[$key]);
            }
        }

        $response = new GeneratorResponse();

        $response->addFile($this->generateOutputFile(
            'manifest.twig',
            $filename,
            [
                'messages' => $messages,
            ]
        ));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, array $parameters)
    {
        $code = parent::render($template, $parameters);

        // use statements: removed duplicate
        if (preg_match_all('/\nuse\s(.*);/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            foreach ($matches[1] as $match) {
                if (in_array($match, $unique)) {
                    unset($unique[array_search($match, $unique)]);
                } else {
                    $code = preg_replace(sprintf("/\nuse\\s%s;/", str_replace('\\', '\\\\', $match)), '', $code, 1);
                }
            }
        }

        // use statements: sorting
        if (preg_match_all('/\nuse\s(.*);/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            asort($unique);
            $unique = array_values($unique);

            foreach ($matches[1] as $key => $match) {
                $from = sprintf("\nuse %s;", $match);
                $to = sprintf("\nuse %s[use_tmp];", $unique[$key]);

                $code = str_replace($from, $to, $code);
            }

            $code = preg_replace("/\[use_tmp\];/", ';', $code);
        }

        // generate replacements
        $code = str_replace(
            [
                ';;',
                "\n\n\n",
                "{\n\n",
                "{\n    \n}",
                "}\n\n}",
            ], [
            ';',
            "\n\n",
            "{\n",
            "{\n}",
            "}\n}",
        ],
            $code
        );

        return $code;
    }
}
