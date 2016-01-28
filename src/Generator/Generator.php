<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Twig\Extension\ClassExtension;
use Gdbots\Pbjc\Schema;

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

    /** @var Schema */
    protected $schema;

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return array
     */
    protected function getTemplates()
    {
        throw new \InvalidArgumentException('No yet implemented');
    }

    /**
     * Generates and writes files.
     *
     * @param string $output
     * @param bool   $print
     *
     * @return void
     */
    public function generate($output, $print = false)
    {
        foreach ($this->getTemplates() as $template => $filename) {
            $this->renderFile(
                $template,
                $this->getTarget($output, $filename),
                $this->getParameters(),
                $print
            );
        }

        if ($this->schema->isLatestVersion()) {
            foreach ($this->getTemplates() as $template => $filename) {
                if ($this->getTarget($output, $filename) != $this->getTarget($output, $filename, true)) {
                    $this->renderFile(
                        $template,
                        $this->getTarget($output, $name, null, true),
                        $this->getParameters(),
                        $print
                    );
                }
            }
        }
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
            '{className}',
            '{version}',
            '{major}',
        ], [
            $this->schema->getClassName(),
            $this->schema->getId()->getVersion()->__toString(),
            $this->schema->getId()->getVersion()->getMajor(),
        ], $filename);

        if (!$directory) {
            $directory = sprintf('%s/%s/%s',
                StringUtils::toCamelFromSlug($this->schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($this->schema->getId()->getPackage()),
                StringUtils::toCamelFromSlug($this->schema->getId()->getCategory())
            );
        }

        return sprintf('%s/%s/%s%s',
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

        return $twig;
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     * @param bool   $print
     *
     * @return int
     */
    protected function renderFile($template, $target, $parameters, $print = false)
    {
        $template = sprintf('%s/%s', $this->language, $template);

        if ($print) {
            return $this->printFile($template, $target, $parameters);
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     *
     * @return void
     */
    protected function printFile($template, $target, $parameters)
    {
        echo $this->render($template, $parameters);
    }
}
