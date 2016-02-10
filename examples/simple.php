<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;

SchemaStore::addDir(__DIR__.'/schemas');

$compile = new Compiler('php', __DIR__.'/src');
$generator = $compile->generate(true);

foreach ($generator->getFiles() as $file => $output) {
    echo highlight_string($output, true).'<hr />';
}

$compile = new Compiler('json', __DIR__.'/schemas');
$generator = $compile->generate(true);

foreach ($generator->getFiles() as $file => $output) {
    $output = sprintf("<?php\n\n\$json = %s;\n", var_export(json_decode($output, true), true));

    echo highlight_string($output, true).'<hr />';
}
