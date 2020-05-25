<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\Generator\Twig\StringExtension;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Util\OutputFile;

abstract class Generator
{
    const TEMPLATE_DIR = __DIR__ . '/Twig/';
    const LANGUAGE = 'unknown';
    const EXTENSION = '.unk';
    const MANIFEST = 'pbj-schemas';

    /** @var CompileOptions */
    protected $compileOptions;

    /** @var \Twig_Environment */
    protected $twig;

    /**
     * @param CompileOptions $compileOptions
     */
    public function __construct(CompileOptions $compileOptions)
    {
        $this->compileOptions = $compileOptions;
    }

    /**
     * Generates code for the given SchemaDescriptor.
     *
     * Produces files for (varies by language):
     * - message class (the concrete class - curie major)
     * - mixin (the schema fields that are "mixed" into the message)
     * - mixin trait (any methods provided by insertion points)
     *
     * @param SchemaDescriptor $schema
     *
     * @return GeneratorResponse
     */
    public function generateSchema(SchemaDescriptor $schema)
    {
        $response = new GeneratorResponse();

        foreach ($schema->getFields() as $field) {
            $this->updateFieldOptions($schema, $field);
        }

        if ($schema->isMixinSchema()) {
            $this->generateMixin($schema, $response);
            $this->generateMixinTrait($schema, $response);
        } else {
            $this->generateMessage($schema, $response);
        }

        return $response;
    }

    /**
     * Generates code for an Enum.
     *
     * @param EnumDescriptor $enum
     *
     * @return GeneratorResponse
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        return new GeneratorResponse();
    }

    /**
     * Generates a manifest of all messages and mixins the store provides.
     * This is used to configure the MessageResolver.
     *
     * @param SchemaDescriptor[] $schemas
     *
     * @return GeneratorResponse
     */
    public function generateManifest(array $schemas)
    {
        $response = new GeneratorResponse();
        $manifests = ['messages' => [], 'mixins' => [], 'files' => []];

        foreach ($schemas as $schema) {
            $id = $schema->getId();
            if ($schema->isMixinSchema()) {
                if (!isset($manifests['mixins'][$id->getCurieWithMajorRev()])) {
                    $manifests['mixins'][$id->getCurieWithMajorRev()] = [];
                }
                continue;
            }

            if (isset($manifests['messages'][$id->getCurieWithMajorRev()])) {
                continue;
            }

            $manifests['messages'][$id->getCurieWithMajorRev()] = $schema;
            foreach ($schema->getMixins() as $mixin) {
                $mixinId = $mixin->getId()->getCurieWithMajorRev();
                if (!isset($manifests['mixins'][$mixinId])) {
                    $manifests['mixins'][$mixinId] = [];
                }

                $manifests['mixins'][$mixinId][] = $schema;
            }
        }

        foreach ($manifests['mixins'] as $curie => $mschemas) {
            $schema = SchemaStore::getSchemaById($curie);
            $filename = 'manifests/' . str_replace(':', '/', $curie);
            $response->addFile(
                $this->generateOutputFile('mixin-manifest.twig', $filename, [
                    'mixin'   => $schema,
                    'schemas' => $mschemas,
                ])
            );
        }

        $response->addFile(
            $this->generateOutputFile('message-manifest.twig', 'manifests/messages', [
                'schemas' => $manifests['messages'],
            ])
        );

        return $this->generateResolver($manifests, $response);
    }

    /**
     * Generates a file that configures the MessageResolver.
     *
     * @param SchemaDescriptor[] $schemas
     *
     * @return GeneratorResponse
     */
    public function generateResolver(array $manifests, GeneratorResponse $response)
    {
        $response->addFile(
            $this->generateOutputFile('resolver.twig', static::MANIFEST, [
                'manifests' => $manifests,
            ])
        );

        return $response;
    }

    /**
     * Returns the class name to be used for the given SchemaDescriptor.
     *
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaToClassName(SchemaDescriptor $schema, $withMajor = false)
    {
        $className = StringUtils::toCamelFromSlug($schema->getId()->getMessage());
        if (!$withMajor) {
            return $className;
        }

        return "{$className}V{$schema->getId()->getVersion()->getMajor()}";
    }

    /**
     * Returns a fully qualified class name to be used for the given SchemaDescriptor.
     * Use this in generated code to avoid name collisions.
     *
     * @param SchemaDescriptor $schema
     * @param bool             $withMajor
     *
     * @return string
     */
    public function schemaToFqClassName(SchemaDescriptor $schema, $withMajor = false)
    {
        $id = $schema->getId();
        $vendor = StringUtils::toCamelFromSlug($id->getVendor());
        $package = StringUtils::toCamelFromSlug(str_replace('.', '-', $id->getPackage()));
        return "{$vendor}{$package}{$this->schemaToClassName($schema, $withMajor)}";
    }

    /**
     * Returns the class name to be used for the given EnumDescriptor.
     *
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToClassName(EnumDescriptor $enum)
    {
        return StringUtils::toCamelFromSlug($enum->getId()->getName());
    }

    /**
     * Returns the native package name for the SchemaDescriptor as
     * looked up in compile options or created automatically.
     *
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaToNativePackage(SchemaDescriptor $schema)
    {
        $id = $schema->getId();
        return $this->getNativePackage($id->getVendor(), $id->getPackage());
    }

    /**
     * Returns the native package name for the EnumDescriptor as
     * looked up in compile options or created automatically.
     *
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToNativePackage(EnumDescriptor $enum)
    {
        $id = $enum->getId();
        return $this->getNativePackage($id->getVendor(), $id->getPackage());
    }

    /**
     * Returns the native namespace for the SchemaDescriptor
     * by combining native package and curie.
     *
     * @param SchemaDescriptor $schema
     *
     * @return string
     * @example
     *  es6: import Article from '@acme/schemas/acme/blog/node';
     *  php: use Acme\Schemas\Blog\Node;
     *
     */
    public function schemaToNativeNamespace(SchemaDescriptor $schema)
    {
    }

    /**
     * Returns the native class path for the SchemaDescriptor
     * by combining native namespace and class name with major.
     *
     * @param SchemaDescriptor $schema
     *
     * @return string
     * @example
     *  es6: @acme/schemas/acme/blog/node/ArticleV1
     *  php: Acme\Schemas\Blog\Node\ArticleV1
     *
     */
    public function schemaToNativeClassPath(SchemaDescriptor $schema)
    {
        $path = $this->schemaToNativeNamespace($schema);
        $class = $this->schemaToClassName($schema, true);
        $delim = 'php' === static::LANGUAGE ? '\\' : '/';
        return "{$path}{$delim}{$class}";
    }

    /**
     * Returns the native namespace for the EnumDescriptor
     * by combining native package and curie.
     *
     * @param EnumDescriptor $enum
     *
     * @return string
     * @example
     *  es6: import SomeEnum from '@acme/schemas/acme/blog/enums';
     *  php: use Acme\Schemas\Blog\Enum;
     *
     */
    public function enumToNativeNamespace(EnumDescriptor $enum)
    {
    }

    /**
     * Generate a message (the concrete class)
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMessage(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Generates a mixin (schema fields "mixed" into messages).
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMixin(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Generates a mixin trait (the methods provided by a mixin).
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMixinTrait(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Adds and updates field php options.
     *
     * @param SchemaDescriptor $schema
     * @param FieldDescriptor  $field
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
    }

    /**
     * @param string $template
     * @param string $file
     * @param array  $parameters
     *
     * @return OutputFile
     */
    protected function generateOutputFile($template, $file, array $parameters)
    {
        $template = sprintf('%s/%s', static::LANGUAGE, $template);
        $content = $this->render($template, $parameters);
        $ext = static::EXTENSION;
        $addNewLine = static::LANGUAGE !== 'json-schema';
        return new OutputFile(
            "{$this->compileOptions->getOutput()}/{$file}$ext",
            trim($content) . ($addNewLine ? PHP_EOL : '')
        );
    }

    /**
     * @param string $vendor
     * @param string $package
     *
     * @return ?string
     */
    protected function getNativePackage($vendor, $package)
    {
        $packages = $this->compileOptions->getPackages();
        $vendorPackage = "{$vendor}:{$package}";

        if (isset($packages[$vendorPackage])) {
            return $packages[$vendorPackage];
        }

        if (isset($packages[$vendor])) {
            return $packages[$vendor];
        }

        return null;
    }

    /**
     * @param array $imports
     *
     * @return string
     */
    protected function optimizeImports(array $imports)
    {
        $imports = array_map('trim', $imports);
        $imports = array_filter($imports);
        $imports = array_unique($imports);
        asort($imports);
        return implode(PHP_EOL, $imports);
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    protected function render($template, array $parameters)
    {
        $twig = $this->getTwig();
        $parameters['compile_options'] = $this->compileOptions;
        return $twig->render($template, $parameters);
    }

    /**
     * Get the twig environment that will render skeletons.
     *
     * @return \Twig_Environment
     */
    protected function getTwig()
    {
        if (null === $this->twig) {
            $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(self::TEMPLATE_DIR), [
                'debug'            => true,
                'cache'            => false,
                'strict_variables' => true,
                'autoescape'       => false,
            ]);

            $this->twig->addExtension(new StringExtension());

            $class = sprintf(
                '\Gdbots\Pbjc\Generator\Twig\%sGeneratorExtension',
                StringUtils::toCamelFromSlug(static::LANGUAGE)
            );

            $this->twig->addExtension(new $class($this->compileOptions, $this));
        }

        return $this->twig;
    }
}
