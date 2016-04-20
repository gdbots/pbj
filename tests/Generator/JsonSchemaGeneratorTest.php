<?php

namespace Gdbots\Tests\Pbjc\Generator;

use Gdbots\Pbjc\Generator\JsonSchemaGenerator;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class JsonSchemaGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Gdbots\Pbjc\Generator\Generator */
    private $generator;

    public function setUp()
    {
        $this->generator = new JsonSchemaGenerator(new CompileOptions([
            'namespaces' => ['acme:blog']
        ]));
    }

    /**
     * @dataProvider getSchemas
     */
    public function testGenerateSchema(SchemaDescriptor $schema, array $files)
    {
        $response = $this->generator->generateSchema($schema);

        $this->assertInstanceOf('Gdbots\Pbjc\Generator\GeneratorResponse', $response);
        $this->assertCount(count($files), $response->getFiles());

        foreach ($response->getFiles() as $path => $outputFile) {
            $this->assertEquals($files[$path], $outputFile->getContents());
        }
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return [
            [
                'schema' => new SchemaDescriptor(
                    'pbj:acme:blog:entity:article:1-0-0',
                    [
                        'fields' => [
                            new FieldDescriptor('string', [
                                'type' => 'string',
                            ]),
                            new FieldDescriptor('int', [
                                'type' => 'int',
                            ]),
                        ],
                    ]
                ),
                'files' => [
                    '/acme/blog/entity/article/1-0-0.json' => '{
  "id": "/json-schema/acme/blog/entity/article/1-0-0.json#",
  "$schema": "http://json-schema.org/draft-04/schema#",
  "type": "object",
  "properties": {
    "_schema": {
      "type": "string",
      "pattern": "^pbj:([a-z0-9-]+):([a-z0-9\\\\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):([0-9]+-[0-9]+-[0-9]+)$",
      "default": "pbj:acme:blog:entity:article:1-0-0"
    },
    "string": {
      "type": "string",
      "minLength": 0,
      "maxLength": 255,
      "pbj": {
        "type": "string",
        "rule": "single"
      }
    },
    "int": {
      "type": "integer",
      "default": 0,
      "minimum": 0,
      "maximum": 4294967295,
      "pbj": {
        "type": "int",
        "rule": "single"
      }
    }
  },
  "required": [
    "_schema"
  ],
  "additionalProperties": false
}'
                ]
            ]
        ];
    }
}
