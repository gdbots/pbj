<?php

namespace Gdbots\Tests\Pbjc\Generator;

use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\Generator\Generator;
use Gdbots\Pbjc\Generator\JsGenerator;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\SchemaDescriptor;

class JsGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Generator */
    private $generator;

    public function setUp()
    {
        $this->generator = new JsGenerator(new CompileOptions([
            'namespaces' => ['acme:blog'],
        ]));
    }

    public function testGenerateEnum()
    {
        $enum = new EnumDescriptor('gdbots:tests:some-enum', 'int', ['K1' => 1, 'K2' => 2, 'K3' => 3]);
        $expected = <<<OUTPUT
import Enum from '@gdbots/common/Enum';

export default class SomeEnum extends Enum {
}

SomeEnum.configure({
  K1: 1,
  K2: 2,
  K3: 3,
}, 'gdbots:tests:some-enum');

OUTPUT;

        $response = $this->generator->generateEnum($enum);
        $file = current($response->getFiles());
        $this->assertSame($expected, $file->getContents());

        $enum = new EnumDescriptor('gdbots:tests:some-enum', 'string', ['K1' => 'v1', 'K2' => 'v2', 'K3' => 'v3']);
        $expected = <<<OUTPUT
import Enum from '@gdbots/common/Enum';

export default class SomeEnum extends Enum {
}

SomeEnum.configure({
  K1: 'v1',
  K2: 'v2',
  K3: 'v3',
}, 'gdbots:tests:some-enum');

OUTPUT;

        $response = $this->generator->generateEnum($enum);
        $file = current($response->getFiles());
        $this->assertSame($expected, $file->getContents());
    }

    public function testSchemaToClassName()
    {
        $schema = new SchemaDescriptor('pbj:acme:blog:entity:article:1-0-0');
        $this->assertSame('Article', $this->generator->schemaToClassName($schema));
        $this->assertSame('ArticleV1', $this->generator->schemaToClassName($schema, true));
    }

    public function testSchemaToFqClassName()
    {
        $schema = new SchemaDescriptor('pbj:acme:blog:entity:article:1-0-0');
        $this->assertSame('AcmeBlogArticle', $this->generator->schemaToFqClassName($schema));
        $this->assertSame('AcmeBlogArticleV1', $this->generator->schemaToFqClassName($schema, true));

        $schema = new SchemaDescriptor('pbj:acme:blog.city:entity:cool-article:1-0-0');
        $this->assertSame('AcmeBlogCityCoolArticle', $this->generator->schemaToFqClassName($schema));
        $this->assertSame(
            'AcmeBlogCityCoolArticleV1',
            $this->generator->schemaToFqClassName($schema, true)
        );
    }

    public function testEnumToClassName()
    {
        $enum = new EnumDescriptor('gdbots:tests:some-enum', 'int', [1, 2, 3, 4]);
        $this->assertSame('SomeEnum', $this->generator->enumToClassName($enum));
    }

    public function testSchemaToNativePackage()
    {
        $schema = new SchemaDescriptor('pbj:acme:blog:entity:article:1-0-0');
        $this->assertSame('@acme/schemas', $this->generator->schemaToNativePackage($schema));
    }

    public function testEnumToNativePackage()
    {
        $enum = new EnumDescriptor('gdbots:tests:some-enum', 'int', [1, 2, 3, 4]);
        $this->assertSame('@gdbots/schemas', $this->generator->enumToNativePackage($enum));
    }

    public function testSchemaToNativeNamespace()
    {
        $schema = new SchemaDescriptor('pbj:acme:blog:entity:article:1-0-0');
        $this->assertSame(
            '@acme/schemas/acme/blog/entity',
            $this->generator->schemaToNativeNamespace($schema)
        );

        $schema = new SchemaDescriptor('pbj:acme:blog.city:entity:cool-article:1-0-0');
        $this->assertSame(
            '@acme/schemas/acme/blog.city/entity',
            $this->generator->schemaToNativeNamespace($schema)
        );

        $schema = new SchemaDescriptor('pbj:acme:blog.city:mixin:super-article:1-0-0', ['isMixin' => true]);
        $this->assertSame(
            '@acme/schemas/acme/blog.city/mixin/super-article',
            $this->generator->schemaToNativeNamespace($schema)
        );
    }

    public function testEnumToNativeNamespace()
    {
        $enum = new EnumDescriptor('gdbots:tests:some-enum', 'int', [1, 2, 3, 4]);
        $this->assertSame(
            '@gdbots/schemas/gdbots/tests/enums',
            $this->generator->enumToNativeNamespace($enum)
        );

        $enum = new EnumDescriptor('gdbots:tests.abc:some-enum', 'int', [1, 2, 3, 4]);
        $this->assertSame(
            '@gdbots/schemas/gdbots/tests.abc/enums',
            $this->generator->enumToNativeNamespace($enum)
        );
    }
}
