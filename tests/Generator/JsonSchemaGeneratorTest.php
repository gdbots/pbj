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
                            new FieldDescriptor('geo_point', [
                                'type' => 'geo-point',
                            ]),
                            new FieldDescriptor('string_with_properties', [
                                'type' => 'string',
                                'default' => 'test',
                                'description' => 'this is a short description',
                                'min' => 10,
                                'max' => 100,
                            ]),
                            new FieldDescriptor('url', [
                                'type' => 'string',
                                'format' => 'url',
                                'rule' => 'map',
                            ]),
                            new FieldDescriptor('node_refs', [
                                'type' => 'message-ref',
                                'rule' => 'set',
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
    },
    "geo_point": {
      "type": "object",
      "properties": {
        "type": {
          "type": "string",
          "pattern": "^Point$"
        },
        "coordinates": {
          "type": "array",
          "items": [
            {
              "required": true,
              "type": "number",
              "minimum": -180,
              "maximum": 180
            },
            {
              "required": true,
              "type": "number",
              "minimum": -90,
              "maximum": 90
            }
          ]
        }
      },
      "required": [
        "type",
        "coordinates"
      ],
      "additionalProperties": false,
      "pbj": {
        "type": "geo-point",
        "rule": "single"
      }
    },
    "string_with_properties": {
      "type": "string",
      "default": "test",
      "minLength": 10,
      "maxLength": 100,
      "description": "this is a short description",
      "pbj": {
        "type": "string",
        "rule": "single"
      }
    },
    "url": {
      "type": "object",
      "patternProperties": {
        "^[a-zA-Z_]{1}[a-zA-Z0-9_]{1,99}$": {
          "type": "string",
          "pattern": "^(https?:\\\\/\\\\/)?([\\\\da-z\\\\.-]+)\\\\.([a-z\\\\.]{2,6})([\\\\/\\\\w \\\\.-]*)*\\\\/?$"
        }
      },
      "additionalProperties": false,
      "pbj": {
        "type": "string",
        "rule": "map",
        "format": "url"
      }
    },
    "node_refs": {
      "type": "array",
      "items": [
        {
          "type": "object",
          "properties": {
            "curie": {
              "type": "string",
              "pattern": "^([a-z0-9-]+):([a-z0-9\\\\.-]+):([a-z0-9-]+)?:([a-z0-9-]+)$",
              "minLength": 0,
              "maxLength": 146
            },
            "id": {
              "type": "string",
              "pattern": "^[A-Za-z0-9:_\\\\-]+$",
              "minLength": 0,
              "maxLength": 255
            },
            "tag": {
              "type": "string",
              "pattern": "^([\\\\w\\\\/-]|[\\\\w-][\\\\w\\\\/-]*[\\\\w-])$",
              "minLength": 0,
              "maxLength": 255
            }
          },
          "required": [
            "curie",
            "id"
          ],
          "additionalProperties": false
        }
      ],
      "uniqueItems": true,
      "additionalProperties": false,
      "pbj": {
        "type": "message-ref",
        "rule": "set"
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
