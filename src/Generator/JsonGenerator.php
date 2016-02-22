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
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        if ($isLatest) {
            $filename = str_replace('{version}', 'latest', $filename);
        }

        $directory = sprintf('%s/%s/%s/%s',
            $schema->getId()->getVendor(),
            $schema->getId()->getPackage(),
            $schema->getId()->getCategory(),
            $schema->getId()->getMessage()
        );

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        return [
            'Message.json.twig' => '{version}',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, $parameters)
    {
        return str_replace(
            [
                '    ',
                '\/'
            ], [
                '  ',
                '/'
            ], json_encode(
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
            )
        );
    }
}
