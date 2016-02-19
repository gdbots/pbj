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
            'Message.json.twig' => '{version}',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($filename, $directory = null, $isLatest = false)
    {
        if ($isLatest) {
            $filename = str_replace('{version}', 'latest', $filename);
        }

        $directory = sprintf('%s/%s/%s/%s',
            $this->schema->getId()->getVendor(),
            $this->schema->getId()->getPackage(),
            $this->schema->getId()->getCategory(),
            $this->schema->getId()->getMessage()
        );

        return parent::getTarget($filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, $parameters)
    {
        return str_replace('    ', '  ', json_encode(
            json_decode(
                str_replace(
                    [
                        "\n",
                        '  ',
                        ',}',
                    ],
                    [
                        '',
                        '',
                        '}',
                    ],
                    parent::render($template, $parameters)
                )
            ),
            JSON_PRETTY_PRINT
        ));
    }
}
