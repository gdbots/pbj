<?php

namespace Gdbots\Tests\Pbjc\Compiler;

use Gdbots\Pbj\Serializer\JsonSerializer;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Validator;

class JsonValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSchemas
     */
    public function testValidateSchema($data)
    {
        if (is_string($data)) {
            $data = json_decode(file_get_contents($data));
        }

        // get the schema and data as objects
        $retriever = new UriRetriever();
        $schema = $retriever->retrieve('http://json-schema.org/draft-04/schema#');

        // resolve $ref's
        $refResolver = new RefResolver($retriever);
        $refResolver->resolve($schema, __DIR__.'/Fixtures');

        // validate
        $validator = new Validator();
        $validator->check($data, $schema);

        $this->assertTrue($validator->isValid());
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return [
            [
                'data' => __DIR__.'/Fixtures/json-schema/article.json'
            ],
            [
                'data' => __DIR__.'/Fixtures/json-schema/comment.json'
            ],
            [
                'data' => (object)[
                    'id' => 'file://schame.json#',
                    '$schema' => 'http://json-schema.org/draft-04/schema#',
                    'type' => 'object',
                    'properties' => (object)[
                        '_schema' => (object)[
                            'type' => 'string',
                            'pattern' => '^pbj:([a-z0-9-]+):([a-z0-9\\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):([0-9]+-[0-9]+-[0-9]+)$',
                            'default' => 'pbj:acme:blog:entity:manual-schema:1-0-0'
                        ]
                    ],
                    'additionalProperties' => false
                ]
            ],
            [
                'data' => (object)[
                    'id' => 'file://schame.json#',
                    '$schema' => 'http://json-schema.org/draft-04/schema#',
                    'type' => 'object',
                    'properties' => (object)[
                        'tags' => (object)[
                            'type' => 'array',
                            'items' => (object)[
                                (object)[
                                    'type' => 'string',
                                ]
                            ],
                            'minItems' => 1,
                            'uniqueItems' => true
                        ]
                    ],
                    'additionalProperties' => false
                ]
            ]
        ];
    }
}
