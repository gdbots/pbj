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
     * Generates and writes schema related files.
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

        foreach ($this->getSchemaTemplates($schema) as $template => $filename) {
            $response->addFile($this->renderFile(
                $template,
                $this->getSchemaTarget($schema, $filename),
                $this->getSchemaParameters($schema)
            ));
        }

        if ($schema->isLatestVersion()) {
            foreach ($this->getSchemaTemplates($schema) as $template => $filename) {
                if ($this->getSchemaTarget($schema, $filename) != $this->getSchemaTarget($schema, $filename, null, true)) {
                    $response->addFile($this->renderFile(
                        $template,
                        $this->getSchemaTarget($schema, $filename, null, true),
                        $this->getSchemaParameters($schema)
                    ));
                }
            }
        }

        return $response;
    }

    /**
     * Generates and writes enum files.
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
     * Generates and writes manifest files.
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
     * Adds and updates field php options.
     *
     * @param SchemaDescriptor $schema
     * @param FieldDescriptor  $field
     *
     * @return FieldDescriptor
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
    }

    /**
     * @param SchemaDescriptor $schema
     * @param string           $filename
     * @param string           $directory
     * @param bool             $isLatest
     *
     * @return string
     */
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        $filename = str_replace([
            '{vendor}',
            '{package}',
            '{category}',
            '{message}',
            '{version}',
            '{major}',
        ], [
            $schema->getId()->getVendor(),
            $schema->getId()->getPackage(),
            $schema->getId()->getCategory(),
            $schema->getId()->getMessage(),
            $schema->getId()->getVersion(),
            $schema->getId()->getVersion()->getMajor(),
        ], $filename);

        if ($directory === null) {
            $directory = sprintf('%s/%s/%s',
                StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
                StringUtils::toCamelFromSlug($schema->getId()->getCategory())
            );
        }

        $directory = trim($directory, '/') . '/';

        return sprintf('%s/%s%s%s',
            $this->compileOptions->getOutput(),
            $directory,
            $filename,
            static::EXTENSION
        );
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        return [];
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return array
     */
    protected function getSchemaParameters(SchemaDescriptor $schema)
    {
        return ['schema' => $schema];
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
        $parameters['compileOptions'] = $this->compileOptions;
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
            $this->twig->addExtension(new $class($this->compileOptions));
        }

        return $this->twig;
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     *
     * @return OutputFile
     */
    protected function renderFile($template, $target, array $parameters)
    {
        $template = sprintf('%s/%s', static::LANGUAGE, $template);
        $content = $this->render($template, $parameters);
        return new OutputFile($target, $content);
    }
}
