<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler\PhpCompiler;
use Gdbots\Pbjc\Compiler\JsonCompiler;

SchemaStore::addDir(__DIR__.'/schemas');

echo '<pre>';

$compile = new PhpCompiler(__DIR__.'/src');
$files = $compile->generate(true);
foreach ($files as $file => $output) {
    highlight_file($file);

    echo '<hr />';
}

$compile = new JsonCompiler(__DIR__.'/schemas');
$files = $compile->generate(true);
foreach ($files as $file => $output) {
    echo $output.'<hr />';
}
