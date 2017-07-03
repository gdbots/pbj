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
    public function generateSchema(SchemaDescriptor $schema)
    {
        $response = new GeneratorResponse();

        $id = $schema->getId();
        $directory = str_replace(['::', ':'], [':', '/'], $id->getCurie());

        $response->addFile(
            $this->generateOutputFile(
                'message.twig',
                "{$directory}/{$id->getVersion()}",
                ['schema' => $schema]
            )
        );

        if ($schema->isLatestVersion()) {
            $response->addFile(
                $this->generateOutputFile(
                    'message.twig',
                    "{$directory}/latest",
                    ['schema' => $schema]
                )
            );
        }

        return $response;
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
