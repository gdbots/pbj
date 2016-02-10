<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler\PhpCompiler;
use Gdbots\Pbjc\Compiler\JsonCompiler;

SchemaStore::addDir(__DIR__.'/schemas');

$compile = new PhpCompiler();
$generator = $compile->generate(true);
foreach ($generator->getFiles() as $file => $output) {
    echo highlight_string($output, true).'<hr />';
}

$compile = new JsonCompiler();
$generator = $compile->generate(true);
foreach ($generator->getFiles() as $file => $output) {
    $output = sprintf("<?php\n\n\$json = %s;\n", var_export(json_decode($output, true), true));

    echo highlight_string($output, true).'<hr />';
}
