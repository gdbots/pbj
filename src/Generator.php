<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Twig\Extension\ClassExtension;

class Generator
{
    /**
     * The directory to look for templates.
     */
    const SKELETON_DIR = __DIR__.'/Resources/skeleton/';

    /** @var string */
    protected $language = 'php';

    /** @var string */
    protected $extension = '.php';

    /** @var string */
    protected $prefix = '';

    /** @var Schema */
    protected $schema;

    /**
     * @param Schema $schema
     * @param string $language
     */
    public function __construct(Schema $schema, $language = 'php')
    {
        $this->schema = $schema;
        $this->language = $language;

        switch ($this->language) {
            case 'json':
                $this->extension = '.json';
                break;
        }
    }

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
     * Generates and writes files for the given yaml file.
     *
     * @param string $output
     * @param bool   $print
     *
     * @return void
     */
    public function generate($output, $print = true)
    {
        $this->renderFile(
            $this->getTemplate(),
            $this->getTarget($output),
            $this->getParameters(),
            $print
        );

        if ($this->schema->isLatestVersion() &&
            $this->getTarget($output) != $this->getTarget($output, true)
        ) {
            $this->renderFile(
                $this->getTemplate(),
                $this->getTarget($output, true),
                $this->getParameters(),
                $print
            );
        }
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        if ($this->schema->isMixin()) {
            return sprintf('%s/Mixin%s.twig', $this->language, $this->extension);
        }

        throw new \Exception('Missing schema template');
    }

    /**
     * @param string $output
     * @param bool   $isLatest
     *
     * @return string
     */
    protected function getTarget($output, $isLatest = false)
    {
        switch ($this->language) {
            case 'php':
                $filename = $this->schema->getClassName();

                break;

            case 'json':
                $filename = $isLatest ? 'latest' : $this->schema->getId()->getVersion();

                break;
        }

        return sprintf('%s/%s/%s%s%s',
            $output,
            str_replace(':', '/', $this->schema->getId()->getCurie()),
            $filename,
            $this->prefix,
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
