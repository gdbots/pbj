<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Twig\Extension\SchemaExtension;
use Gdbots\Pbjc\Twig\Extension\StringExtension;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Util\OutputFile;

abstract class Generator
{
    /**
     * The directory to look for templates.
     */
    const SKELETON_DIR = __DIR__.'/templates/';

    /** @var string */
    protected $language;

    /** @var string */
    protected $extension;

    /** @var string */
    protected $output;

    /**
     * @param string $output
     */
    public function __construct($output)
    {
        $this->output = $output;
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
            '{version}',
            '{major}',
        ], [
            $schema->getId()->getVendor(),
            $schema->getId()->getPackage(),
            $schema->getId()->getCategory(),
            $schema->getId()->getVersion()->toString(),
            $schema->getId()->getVersion()->getMajor(),
        ], $filename);

        if ($directory === null) {
            $directory = sprintf('%s/%s/%s',
                StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
                StringUtils::toCamelFromSlug($schema->getId()->getCategory())
            );
        }
        if ($directory) {
            $directory .= '/';
        }

        return sprintf('%s/%s%s%s',
            $this->output,
            $directory,
            $filename,
            $this->extension
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
        return [
            'schema' => $schema,
        ];
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
    }

    /**
     * Generates and writes manifest files.
     *
     * @param SchemaDescriptor[] $schemas
     * @param string             $filename
     *
     * @return GeneratorResponse
     */
    public function generateManifest(array $schemas, $filename)
    {
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    protected function render($template, array $parameters)
    {
        $twig = $this->getTwigEnvironment();

        return $twig->render($template, $parameters);
    }

    /**
     * Get the twig environment that will render skeletons.
     *
     * @return \Twig_Environment
     */
    protected function getTwigEnvironment()
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(self::SKELETON_DIR), array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));

        $twig->addExtension(new SchemaExtension());
        $twig->addExtension(new StringExtension());

        return $twig;
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
        $template = sprintf('%s/%s', $this->language, $template);

        $content = $this->render($template, $parameters);

        return new OutputFile($target, $content);
    }
}
