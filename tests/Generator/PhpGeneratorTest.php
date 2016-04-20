<?php

namespace Gdbots\Tests\Pbjc\Generator;

use Gdbots\Pbjc\Generator\PhpGenerator;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Util\LanguageBag;

class PhpGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Gdbots\Pbjc\Generator\Generator */
    private $generator;

    public function setUp()
    {
        $this->generator = new PhpGenerator(new CompileOptions([
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
                        'languages' => new LanguageBag([
                            'php' => new LanguageBag([
                                'namespace' => 'Acme\Blog\Entity'
                            ])
                        ])
                    ]
                ),
                'files' => [
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
                    ->build()
            ]
        );
    }
}
"
                ]
            ]
        ];
    }
}
