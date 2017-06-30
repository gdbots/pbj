<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\SchemaDescriptor;

class JsonSchemaGenerator extends Generator
{
    const LANGUAGE = 'json-schema';
    const EXTENSION = '.json';

    /**
     * {@inheritdoc}
     */
    public function generateManifest(array $schemas)
    {
        return new GeneratorResponse();
    }

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
            'message.twig' => '{version}',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, array $parameters)
    {
        return str_replace(
            [
                '    ',
                '\/',
            ], [
            '  ',
            '/',
        ], json_encode(
                json_decode(
                    str_replace(
                        [
                            "\n",
                            '  ',
                            ', }',
                            ', ]',
                            ',}',
                            ',]',
                            ': INF',
                            ': NAN',
                        ],
                        [
                            '',
                            '',
                            '}',
                            '}',
                            '}',
                            ']',
                            ': "INF"',
                            ': "NAN"',
                        ],
                        parent::render($template, $parameters)
                    )
                ),
                JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT
            )
        );
    }
}
