<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Twig\Extension\ClassExtension;
use Gdbots\Pbjc\Twig\Extension\StringExtension;
use Gdbots\Pbjc\Descriptor\SchemaDescriptor;

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

    /** @var bool */
    protected $outputDisabled = false;

    /** @var array */
    protected $files = [];

    /**
     * @param SchemaDescriptor $schema
     */
    public function setSchema(SchemaDescriptor $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Disables rendering output.
     *
     * @return this
     */
    public function disableOutput()
    {
        $this->outputDisabled = true;

        return $this;
    }

    /**
     * Enables rendering output.
     *
     * @return this
     */
    public function enableOutput()
    {
        $this->outputDisabled = false;

        return $this;
    }

    /**
     * Returns true in case the output is disabled, false otherwise.
     *
     * @return bool
     */
    public function isOutputDisabled()
    {
        return $this->outputDisabled;
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
     * @return array
     */
    abstract protected function getTemplates();

    /**
     * @return string
     */
    abstract protected function getEnumTemplate();

    /**
     * Generates and writes files.
     *
     * @param string $output
     *
     * @return void
     */
    public function generate($output)
    {
        foreach ($this->getTemplates() as $template => $filename) {
            $this->renderFile(
                $template,
                $this->getTarget($output, $filename),
                $this->getParameters()
            );
        }

        if ($this->schema->isLatestVersion()) {
            foreach ($this->getTemplates() as $template => $filename) {
                if ($this->getTarget($output, $filename, null) != $this->getTarget($output, $filename, null, true)) {
                    $this->renderFile(
                        $template,
                        $this->getTarget($output, $filename, null, true),
                        $this->getParameters()
                    );
                }
            }
        }
    }

    /**
     * @param string $output
     *
     * @return void
     */
    public function generateEnums($output)
    {
        // do nothing
    }

    /**
     * @param string $output
     * @param string $filename
     * @param string $directory
     * @param bool   $isLatest
     *
     * @return string
     */
    protected function getTarget($output, $filename, $directory = null, $isLatest = false)
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
            $this->schema->getId()->getVersion()->__toString(),
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
            $output,
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
            'schema' => $this->schema
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
     *
     * @return void
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
