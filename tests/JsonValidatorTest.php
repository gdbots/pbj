<?php

namespace Gdbots\Tests\Pbjc\Compiler;

use Gdbots\Pbj\Serializer\JsonSerializer;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Validator;

class JsonValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSchema()
    {
        // get the schema and data as objects
        $retriever = new UriRetriever();
        $schema = $retriever->retrieve('http://json-schema.org/draft-04/schema#');
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/json-schema/article.json'));

        // resolve $ref's
        $refResolver = new RefResolver($retriever);
        $refResolver->resolve($schema, __DIR__.'/Fixtures');

        // validate
        $validator = new Validator();
        $validator->check($data, $schema);

        $this->assertTrue($validator->isValid());
    }
}
