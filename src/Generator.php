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
    }

    /**
     * Gets the extension to use when writing files to disk.
     *
     * @return string
     */
    public function getExtension()
    {
        switch ($this->language) {
            case 'php': return '.php';
        }

        throw new \InvalidArgumentException(sprintf('No extension for language "%s"', $this->language));
    }

    /**
     * @return array
     */
    protected function getTemplates()
    {
        switch ($this->language) {
            case 'php':
                return $this->schema->isMixin()
                    ? [
                        'MessageInterface.php.twig' => '{className}',
                        'Interface.php.twig'        => '{className}V{major}',
                        'Mixin.php.twig'            => '{className}V{major}Mixin',
                        'Trait.php.twig'            => '{className}V{major}Trait'
                    ]
                    : [
                        'MessageInterface.php.twig' => '{className}',
                        'AbstractMessage.php.twig'  => '{className}V{major}'
                    ]
                ;
        }

        throw new \InvalidArgumentException(sprintf('No extension for language "%s"', $this->language));
    }

    /**
     * Generates and writes files.
     *
     * @param string $output
     * @param bool   $print
     *
     * @return void
     */
    public function generate($output, $print = true)
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
                        $this->getTarget($output, $name, true),
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
     * @param bool   $isLatest
     *
     * @return string
     */
    protected function getTarget($output, $filename, $isLatest = false)
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

        return sprintf('%s/%s/%s/%s/%s%s',
            $output,
            $this->schema->getId()->getVendor(),
            $this->schema->getId()->getPackage(),
            $this->schema->getId()->getCategory(),
            $filename,
            $this->getExtension()
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
            var_dump('<pre>', $target, str_replace('<?php', '-?php', $this->render($template, $parameters)), '</pre>');
            return;
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }
}
