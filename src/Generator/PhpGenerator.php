<?php

namespace Gdbots\Pbjc\Generator;

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
}
