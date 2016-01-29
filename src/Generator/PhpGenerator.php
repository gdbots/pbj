<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;

class PhpGenerator extends Generator
{
    /** @var string */
    protected $language = 'php';

    /** @var string */
    protected $extension = '.php';

    /**
     * {@inheritdoc}
     */
    protected function getTemplates()
    {
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

    /**
     * {@inheritdoc}
     */
    protected function getEnumTemplate()
    {
        return 'Enum.php.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($output, $filename, $directory = null, $isLatest = false)
    {
        $directory = str_replace('\\', '/', $this->schema->getLanguageOption('php', 'namespace'));

        return parent::getTarget($output, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFile($template, $target, $parameters)
    {
        echo sprintf("<pre>Filename = %s\n\n%s</pre><hr />", $target, str_replace('<?php', '-?php', $this->render($template, $parameters)));
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnums($output, $print = false)
    {
        $enums = $this->schema->getOption('enums', []);

        foreach ($enums as $name => $options) {
            if (!$phpOptions = $this->schema->getLanguageOption('php', 'enums')) {
                $phpOptions = $this->schema->getLanguageOptions('php');
            }

            $namespace = $phpOptions['namespace'];
            if (substr($namespace, 0, 1) == '\\') {
                $namespace = substr($namespace, 1);
            }

            $className =
                sprintf('%s%sV%d',
                    $this->schema->getClassName(),
                    StringUtils::toCamelFromSlug($name),
                    $this->schema->getId()->getVersion()->getMajor()
                )
            ;

            $filename =
                sprintf('%s/%s/%s%s',
                    $output,
                    str_replace('\\', '/', $namespace),
                    str_replace('\\', '/', $className),
                    $this->extension
                )
            ;

            $this->renderFile(
                $this->getEnumTemplate(),
                $filename,
                array_merge($this->getParameters(), [
                    'namespace' => $namespace,
                    'className' => $className,
                    'options' => $options,
                    'is_int' => is_int(current($options))
                ]),
                $print
            );
        }
    }
}
