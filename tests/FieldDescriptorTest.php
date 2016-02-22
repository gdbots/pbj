<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\Enum\FieldRule;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldDescriptorTest extends \PHPUnit_Framework_TestCase
{
    /** FieldDescriptor */
    protected $field;

    public function setUp()
    {
        $this->field = new FieldDescriptor('name', [
            'type' => 'string',
            'required' => true,
            'min' => 10,
            'max' => 100,
            'pattern' => '^[A-Za-z0-9_\-]+$',
            'default' => 'jonny',
            'any_of' => [
                new SchemaDescriptor('pbj:vendor:package:category:message:1-0-0'),
            ],
            'enum' => new EnumDescriptor('number', 'int', [1, 2, 3, 4]),
            'php_options' => [
                'namespace' => 'Acme\Blog\Entity',
            ],
        ]);
    }

    public function tearDown()
    {
        $this->field = null;
    }

    public function testGetName()
    {
        $this->assertEquals('name', $this->field->getName());
    }

    public function testGetType()
    {
        $this->assertInstanceOf('Gdbots\Pbjc\Type\StringType', $this->field->getType());
        $this->assertEquals('string', $this->field->getType()->getTypeName()->__toString());
    }

    public function testGetRule()
    {
        $this->assertInstanceOf('Gdbots\Pbjc\Enum\FieldRule', $this->field->getRule());
        $this->assertEquals(FieldRule::A_SINGLE_VALUE(), $this->field->getRule());
    }

    public function testIsASingleValue()
    {
        $this->assertFalse($this->field->isASingleValue());
    }

    public function testIsASet()
    {
        $this->assertFalse($this->field->isASet());
    }

    public function testIsAList()
    {
        $this->assertFalse($this->field->isAList());
    }

    public function testIsAMap()
    {
        $this->assertFalse($this->field->isAMap());
    }

    public function testIsRequired()
    {
        $this->assertTrue($this->field->isRequired());
    }

    public function testGetPattern()
    {
        $this->assertEquals('^[A-Za-z0-9_\-]+$', $this->field->getPattern());
    }

    public function testGetFormat()
    {
        $this->assertNull($this->field->getFormat());
    }

    public function testGetMinLength()
    {
        $this->assertEquals(10, $this->field->getMinLength());
    }

    public function testGetMaxLength()
    {
        $this->assertEquals(100, $this->field->getMaxLength());
    }

    public function testGetMin()
    {
        $this->assertNull($this->field->getMin());
    }

    public function testGetMax()
    {
        $this->assertNull($this->field->getMax());
    }

    public function testGetPrecision()
    {
        $this->assertEquals(0, $this->field->getPrecision());
    }

    public function testGetScale()
    {
        $this->assertEquals(0, $this->field->getScale());
    }

    public function testGetDefault()
    {
        $this->assertEquals('jonny', $this->field->getDefault());
    }

    public function testIsUseTypeDefault()
    {
        $this->assertFalse($this->field->isUseTypeDefault());
    }

    public function tetGetAnyOf()
    {
        $this->assertCount(1, $this->field->getAnyOf());
        $this->assertInstanceOf('Gdbots\Pbjc\SchemaDescriptor', $this->field->getAnyOf()[0]);
        $this->assertEquals('pbj:vendor:package:category:message:1-0-0', $this->field->getAnyOf()[0]);
    }

    public function testIsOverridable()
    {
        $this->assertFalse($this->field->isOverridable());
    }

    public function testGetEnum()
    {
        $this->assertInstanceOf('Gdbots\Pbjc\EnumDescriptor', $this->field->getEnum());
        $this->assertEquals('number', $this->field->getEnum()->getName());
    }

    public function testSetLanguage()
    {
        $value = ['convert' => false];

        $this->field->setLanguage('json', $value);

        $this->assertEquals($value, $this->field->getLanguage('json'));
    }

    public function testGetLanguage()
    {
        $this->assertEquals([], $this->field->getLanguage('json'));
        $this->assertEquals(['namespace' => 'Acme\Blog\Entity'], $this->field->getLanguage('php'));
    }

    public function tetSetLanguageKey()
    {
        $this->field->setLanguageKey('php', 'class_name', 'Article');

        $this->assertEquals('Article', $this->field->getLanguageKey('php', 'class_name'));
    }

    public function testGetLanguageKey()
    {
        $this->assertEquals('Acme\Blog\Entity', $this->field->getLanguageKey('php', 'namespace'));
    }

    public function testGetLanguages()
    {
        $this->assertCount(1, $this->field->getLanguages());
    }
}
