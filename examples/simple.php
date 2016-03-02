<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Util\OutputFile;
use Gdbots\Pbjc\Util\ParameterBag;

SchemaStore::addDir(__DIR__.'/schemas');

$compile = new Compiler();

// dispatcher to pretty print file content
$compile->setDispatcher(function (OutputFile $file) {
    $extension = pathinfo($file->getFile(), PATHINFO_EXTENSION);

    $content = $file->getContents();

    if ($extension == 'json') {
        $content = sprintf("<?php\n\n\$json = %s;\n", var_export(json_decode($content, true), true));
    }

    echo highlight_string($content, true).'<hr />';
});

$namespaces = ['acme:blog', 'acme:core', 'gdbots:pbj'];

// generate PHP files
$compile->run('php', new ParameterBag([
    'namespaces' => $namespaces,
    'output' => __DIR__.'/src',
    'manifest' => __DIR__.'/pbj-schemas.php'
]));

// generate JSON Schema files
$compile->run('json-schema', new ParameterBag([
    'namespaces' => $namespaces,
    'output' => __DIR__.'/json-schema'
]));
