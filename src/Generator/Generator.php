<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Twig\Extension\ClassExtension;
use Gdbots\Pbjc\Twig\Extension\StringExtension;
use Gdbots\Pbjc\SchemaDescriptor;

abstract class Generator
{
    /**
     * The directory to look for templates.
     */
    const SKELETON_DIR = __DIR__.'/../Resources/skeleton/';

    /** @var string */
    protected $language;

    /** @var string */
    protected $extension;

    /** @var SchemaDescriptor */
    protected $schema;

    /** @var string */
    protected $output;

    /** @var bool */
    protected $outputDisabled = false;

    /** @var array */
    protected $files = [];

    /**
     * @param string $output
     */
    public function __construct($output = null)
    {
        $this->output = $output;

        if (!$this->output) {
            $this->outputDisabled = true;
        }
    }

    /**
     * Returns list of files (with output target).
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Generates and writes files.
     *
     * @param SchemaDescriptor $schema
     */
    public function generate(SchemaDescriptor $schema)
    {
        $this->schema = $schema;

        foreach ($this->getTemplates() as $template => $filename) {
            $this->renderFile(
                $template,
                $this->getTarget($filename),
                $this->getParameters()
            );
        }

        if ($this->schema->isLatestVersion()) {
            foreach ($this->getTemplates() as $template => $filename) {
                if ($this->getTarget($filename) != $this->getTarget($filename)) {
                    $this->renderFile(
                        $template,
                        $this->getTarget($filename),
                        $this->getParameters()
                    );
                }
            }
        }

        if (count($this->schema->getEnums())) {
            $this->generateEnums();
        }

        $this->schema = null;
    }

    /**
     * @return array
     */
    abstract protected function getTemplates();

    /**
     * @return string
     */
    protected function getEnumTemplate()
    {
        return;
    }

    /**
     * Generates enums files.
     */
    protected function generateEnums()
    {
    }

    /**
     * @param string $filename
     * @param string $directory
     *
     * @return string
     */
    protected function getTarget($filename, $directory = null)
    {
        $filename = str_replace([
            '{vendor}',
            '{package}',
            '{category}',
            '{version}',
            '{major}',
        ], [
            $this->schema->getId()->getVendor(),
            $this->schema->getId()->getPackage(),
            $this->schema->getId()->getCategory(),
            $this->schema->getId()->getVersion()->toString(),
            $this->schema->getId()->getVersion()->getMajor(),
        ], $filename);

        if ($directory === null) {
            $directory = sprintf('%s/%s/%s',
                StringUtils::toCamelFromSlug($this->schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($this->schema->getId()->getPackage()),
                StringUtils::toCamelFromSlug($this->schema->getId()->getCategory())
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
     * @return array
     */
    protected function getParameters()
    {
        return [
            'schema' => $this->schema,
        ];
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    protected function render($template, $parameters)
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

        $twig->addExtension(new ClassExtension());
        $twig->addExtension(new StringExtension());

        return $twig;
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     */
    protected function renderFile($template, $target, $parameters)
    {
        $template = sprintf('%s/%s', $this->language, $template);

        $render = $this->render($template, $parameters);

        $this->files[$target] = $render;

        if (!$this->outputDisabled) {
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            file_put_contents($target, $render);
        }
    }
}
