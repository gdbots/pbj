<?php

namespace Gdbots\Pbjc\Generator;

class JsonGenerator extends Generator
{
    /** @var string */
    protected $language = 'json';

    /** @var string */
    protected $extension = '.json';

    /**
     * {@inheritdoc}
     */
    protected function getTemplates()
    {
        return [
                'Message.json.twig'  => '{vendor}.{package}.{category}.{className}.{version}'
            ]
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumTemplate()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($output, $filename, $directory = null, $isLatest = false)
    {
        if ($isLatest) {
            $filename = str_replace('{version}', 'latest', $filename);
        }

        return parent::getTarget($output, $filename, false, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFile($template, $target, $parameters)
    {
        echo sprintf("<pre>Filename = %s\n\n%s</pre><hr />", $target, $this->render($template, $parameters));
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, $parameters)
    {
        return json_encode(
            json_decode(
                str_replace(
                    [
                        "\n",
                        '  ',
                        ',}'
                    ],
                    [
                        '',
                        '',
                        '}'
                    ],
                    parent::render($template, $parameters)
                )
            ),
            JSON_PRETTY_PRINT
        );
    }
}
