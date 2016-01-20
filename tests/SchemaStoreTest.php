<?php

namespace Gdbots\Tests\Pbjc;

use Gdbots\Pbjc\SchemaStore;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class SchemaStoreTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        SchemaStore::addDir($commandDir = __DIR__.'/Fixtures/schemas/command');
        SchemaStore::addDir($entityDir = __DIR__.'/Fixtures/schemas/entity');

        $this->assertEquals(SchemaStore::getDirs(), [$commandDir, $entityDir]);
    }

    public function testAddSchema()
    {
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.yml');

            foreach ($files as $file) {
                $yamlParser = new YamlParser();

                try {
                    $schema = $yamlParser->parse(file_get_contents($file));
                } catch (ParseException $e) {
                    $e->setParsedFile($file);

                    throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
                }

                SchemaStore::addSchema($schema['id'], $schema, true);
            }
        }

        $this->assertEquals(SchemaStore::getSchemaById('pbj:gdbots:pbj:mixin:command:1-0-1'), [
            'id' => 'pbj:gdbots:pbj:mixin:command:1-0-1',
            'mixin' => true,
            'fields' => [
                'command_id' => [
                    'type' => 'time-uuid',
                    'required' => true
                ],
                'microtime' => [
                    'type' => 'microtime',
                    'required' => true
                ],
                'correlator' => [
                    'type' => 'message-ref'
                ],
                'retries' => [
                    'type' => 'tiny-int'
                ]
            ],
            'php_options' => [
                'namespace' => 'Gdbots\Schemas\Pbj\Command'
            ]
        ]);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAddDuplicateSchema()
    {
        $yamlParser = new YamlParser();

        try {
            $schema = $yamlParser->parse(file_get_contents(__DIR__.'/Fixtures/schemas/command/latest.yml'));
        } catch (ParseException $e) {
            $e->setParsedFile($file);

            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        SchemaStore::addSchema($schema['id'], $schema);
    }
}
