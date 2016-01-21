<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\SchemaStore;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

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

    /**
     * @var string
     */
    protected $filePrefix = '';

    /**
     * @var string
     */
    protected $template = '';

    /**
     * Holds schema id elements.
     *
     * @var array
     */
    protected $schemaIdElements = [
        'vendor' => null,
        'package' => null,
        'category' => null,
        'message' => null,
        'version' => null
    ];

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
    public function setFilePrefix($prefix)
    {
        $this->filePrefix = $prefix;
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
     * Parse and sets the schema id elements.
     *
     * @param string $id
     *
     * @return void
     */
    public function setSchemaIdElements($id)
    {
        if (preg_match(SchemaStore::VALID_PATTERN, $id, $matches) === false) {
            throw new \InvalidArgumentException(sprintf('The "%s" does not follow the schemaId format.', $id));
        }

        $this->schemaIdElements = [
            'vendor' => $matches[0][1],
            'package' => $matches[0][2],
            'category' => $matches[0][3],
            'message' => $matches[0][4],
            'version' => $matches[0][5],
        ];
    }

    /**
     * @return string|null
     */
    public function getSchemaIdElement($element)
    {
        if (isset($this->schemaIdElements[$element])) {
            return $this->schemaIdElements[$element];
        }

        return null;
    }

    /**
     * Generates and writes files for the given yaml file.
     *
     * @param array  $schema
     * @param string $output
     *
     * @return void
     */
    public function generate(array $schema, $output)
    {
        $this->setSchemaIdElements($schema['id']);

        if ($this->isValid($schema)) {
            $this->renderFile(
                $this->getTemplate(),
                $this->getTarget($output),
                $this->getParameters($schema)
            );
        }
    }

    /**
     * @param array $schema
     *
     * @return bool
     */
    protected function isValid(array $schema)
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
        return sprintf('%s/%s.%s', $output, $this->getFileName(), $this->extension);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return sprintf('%s%s%s',
            $this->classify($this->getSchemaIdElement('message')),
            $this->classify($this->getSchemaIdElement('version')),
            $this->filePrefix
        );
    }

    /**
    * @param array $schema
     *
     * @return array
     */
    abstract protected function getParameters(array $schema);

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
        return new \Twig_Environment(new \Twig_Loader_Filesystem(self::SKELETON_DIR), array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     *
     * @return int
     */
    protected function renderFile($template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }

    /**
     * Transforms the given string to a new string valid as a PHP class name
     * ('app:my-project' -> 'AppMyProject', 'app:namespace:name' -> 'AppNamespaceName').
     *
     * @param string $string
     *
     * @return The string transformed to be a valid PHP class name
     */
    public function classify($string)
    {
        return str_replace(' ', '', ucwords(strtr($string, '_-:', '   ')));
    }
}
