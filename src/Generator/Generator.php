<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\Generator\Twig\StringExtension;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Util\OutputFile;

abstract class Generator
{
    const TEMPLATE_DIR = __DIR__ . '/Twig/';
    const LANGUAGE = 'unknown';
    const EXTENSION = '.unk';

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
    public function generateSchema(SchemaDescriptor $schema)
    {
        $response = new GeneratorResponse();

        foreach ($schema->getFields() as $field) {
            $this->updateFieldOptions($schema, $field);
        }

        if ($schema->isMixinSchema()) {
            $this->generateMixin($schema, $response);
            $this->generateMixinInterface($schema, $response);
            $this->generateMixinMajorInterface($schema, $response);
            $this->generateMixinTrait($schema, $response);
        } else {
            $this->generateMessage($schema, $response);
            $this->generateMessageInterface($schema, $response);
        }

        return $response;
//
//        foreach ($this->getSchemaTemplates($schema) as $template => $filename) {
//            $response->addFile($this->renderFile(
//                $template,
//                $this->getSchemaTarget($schema, $filename),
//                $this->getSchemaParameters($schema)
//            ));
//        }
//
//        if ($schema->isLatestVersion()) {
//            foreach ($this->getSchemaTemplates($schema) as $template => $filename) {
//                if ($this->getSchemaTarget($schema, $filename) != $this->getSchemaTarget($schema, $filename, null, true)) {
//                    $response->addFile($this->renderFile(
//                        $template,
//                        $this->getSchemaTarget($schema, $filename, null, true),
//                        $this->getSchemaParameters($schema)
//                    ));
//                }
//            }
//        }
//
//        return $response;
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
     * Generates a manifest of all messages the store provides.
     * This is used to configure the MessageResolver.
     *
     * @param SchemaDescriptor[] $schemas
     *
     * @return GeneratorResponse
     */
    public function generateManifest(array $schemas)
    {
        return new GeneratorResponse();
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
     * @example
     *  es6: import Article from '@acme/schemas/acme/blog/node';
     *  php: use Acme\Schemas\Blog\Node;
     *
     * @param SchemaDescriptor $schema
     *
     * @return string
     */
    public function schemaToNativeNamespace(SchemaDescriptor $schema)
    {
    }

    /**
     * Returns the native namespace for the EnumDescriptor
     * by combining native package and curie.
     *
     * @example
     *  es6: import SomeEnum from '@acme/schemas/acme/blog/enums';
     *  php: use Acme\Schemas\Blog\Enum;
     *
     * @param EnumDescriptor $enum
     *
     * @return string
     */
    public function enumToNativeNamespace(EnumDescriptor $enum)
    {
    }

    /**
     * Generate a message (concrete class)
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMessage(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Generate a message interface and add an output file
     * to the response.
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMessageInterface(SchemaDescriptor $schema, GeneratorResponse $response)
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
     * Generates a mixin interface.
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMixinInterface(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Generates a mixin major (as in curie major) interface.
     *
     * @param SchemaDescriptor  $schema
     * @param GeneratorResponse $response
     */
    protected function generateMixinMajorInterface(SchemaDescriptor $schema, GeneratorResponse $response)
    {
    }

    /**
     * Generates a mixin trait (the functions/behavior provided by a mixin).
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
        return new OutputFile("{$this->compileOptions->getOutput()}/{$file}$ext", trim($content).PHP_EOL);
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
