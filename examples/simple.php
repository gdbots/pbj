<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/pbj-schema-stores.php';

use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\Util\OutputFile;

$compiler = new Compiler();

$namespaces = ['acme:blog', 'acme:core'];

// generate PHP files
$compiler->run('php', new CompileOptions([
    'namespaces' => $namespaces,
    'output' => __DIR__.'/src',
    'manifest' => __DIR__.'/pbj-schemas.php',
    'callback' => function (OutputFile $file) {
        echo highlight_string($file->getContents(), true).'<hr />';

        if (!is_dir(dirname($file->getFile()))) {
            mkdir(dirname($file->getFile()), 0777, true);
        }

        file_put_contents($file->getFile(), $file->getContents());
    },
]));

// generate JSON-SCHEMA files
$compiler->run('json-schema', new CompileOptions([
    'domain' => 'http://example.com/schemas',
    'namespaces' => $namespaces,
    'output' => __DIR__.'/json-schema',
    'callback' => function (OutputFile $file) {
        echo highlight_string($file->getContents(), true).'<hr />';

        if (!is_dir(dirname($file->getFile()))) {
            mkdir(dirname($file->getFile()), 0777, true);
        }

        file_put_contents($file->getFile(), $file->getContents());
    },
]));
