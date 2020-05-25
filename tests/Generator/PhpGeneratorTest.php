<?php

namespace Gdbots\Tests\Pbjc\Generator;

use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\Generator\Generator;
use Gdbots\Pbjc\Generator\GeneratorResponse;
use Gdbots\Pbjc\Generator\PhpGenerator;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Util\LanguageBag;

class PhpGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Generator */
    private $generator;

    public function setUp()
    {
        $this->generator = new PhpGenerator(new CompileOptions([
            'namespaces' => ['acme:blog'],
        ]));
    }

    /**
     * @dataProvider getSchemas
     *
     * @param SchemaDescriptor $schema
     * @param array            $files
     */
    public function testGenerateSchema(SchemaDescriptor $schema, array $files)
    {
        $this->markTestSkipped('refactoring');

        $response = $this->generator->generateSchema($schema);

        $this->assertInstanceOf(GeneratorResponse::class, $response);
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
                        'fields'    => [
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
                                'type'        => 'string',
                                'default'     => 'test',
                                'description' => 'this is a short description',
                                'min'         => 10,
                                'max'         => 100,
                            ]),
                            new FieldDescriptor('url', [
                                'type'   => 'string',
                                'format' => 'url',
                                'rule'   => 'map',
                            ]),
                            new FieldDescriptor('message_refs', [
                                'type' => 'message-ref',
                                'rule' => 'list',
                            ]),
                            new FieldDescriptor('node_refs', [
                                'type' => 'node-ref',
                                'rule' => 'set',
                            ]),
                            new FieldDescriptor('set_with_pattern', [
                                'type'    => 'string',
                                'pattern' => '^[\w\/\.:-]+$',
                                'rule'    => 'set',
                            ]),
                        ],
                        'languages' => new LanguageBag([
                            'php' => new LanguageBag([
                                'namespace' => 'Acme\Blog\Entity',
                            ]),
                        ]),
                    ]
                ),
                'files'  => [
                    '/Acme/Blog/Entity/Article.php' => "<?php

namespace Acme\Blog\Entity;

use Gdbots\Pbj\Message;

interface Article extends Message
{
}
",

                    '/Acme/Blog/Entity/ArticleV1.php' => "<?php

namespace Acme\Blog\Entity;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\Enum\Format;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;

final class ArticleV1 extends AbstractMessage implements
    Article
{
    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:entity:article:1-0-0', __CLASS__,
            [
                Fb::create('string', T\StringType::create())
                    ->build(),
                Fb::create('int', T\IntType::create())
                    ->build(),
                Fb::create('geo_point', T\GeoPointType::create())
                    ->build(),
                /*
                 * this is a short description
                 */
                Fb::create('string_with_properties', T\StringType::create())
                    ->minLength(10)
                    ->maxLength(100)
                    ->withDefault(\"test\")
                    ->build(),
                Fb::create('url', T\StringType::create())
                    ->asAMap()
                    ->format(Format::URL())
                    ->build(),
                Fb::create('node_refs', T\MessageRefType::create())
                    ->asASet()
                    ->build(),
                Fb::create('set_with_pattern', T\StringType::create())
                    ->asASet()
                    ->pattern('^[\w\/\.:-]+$')
                    ->build()
            ]
        );
    }
}
",
                ],
            ],
        ];
    }
}
