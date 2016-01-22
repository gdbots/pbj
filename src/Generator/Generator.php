<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\Schema;
use Gdbots\Pbjc\Twig\Extension\ClassExtension;

/**
 * Generator is the base class for all generators.
 */
abstract class Generator
{
    /**
     * The directory to look for templates.
     */
    const SKELETON_DIR = __DIR__.'/../Resources/skeleton/';

    /**
     * The extension to use for written files.
     *
     * @var string
     */
    protected $extension = '.php';

    /** @var string */
    protected $prefix = '';

    /** @var string */
    protected $template = '';

    /** @var Schema */
    protected $schema;

    /** @var bool */
    protected $isLatest = false;

    /**
     * Sets the extension to use when writing files to disk.
     *
     * @param string $extension
     *
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Sets a file prefix.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Sets a template.
     *
     * @param string $template
     *
     * @return void
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Generates and writes files for the given yaml file.
     *
     * @param Schema $schema
     * @param string $output
     * @param bool   $isLatest
     *
     * @return void
     */
    public function generate(Schema $schema, $output, $isLatest = false)
    {
        $this->schema = $schema;
        $this->isLatest = $isLatest;

        $this->renderFile(
            $this->getTemplate(),
            $this->getTarget($output),
            $this->getParameters(),
            empty($output)
        );
    }

    /**
     * @return bool
     */
    public function isOnlyLatest()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $output
     *
     * @return string
     */
    protected function getTarget($output)
    {
        return sprintf('%s/%s/%s%s%s',
            $output,
            str_replace(':', '/', $this->schema->getId()->getCurie()),
            $this->getTargetFilename(),
            $this->prefix,
            $this->extension
        );
    }

    /**
     * @return string
     */
    protected function getTargetFilename()
    {
        return $this->schema->getClassName();
    }

    /**
     * @return array
     */
    protected function getParameters()
    {
        return [
            'schema' => $this->schema,
            'prefix' => $this->prefix
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
        if ($print) {
            var_dump('<pre>', $target, str_replace('<?php', '-?php', $this->render($template, $parameters)), '</pre>');
            return;
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }
}
