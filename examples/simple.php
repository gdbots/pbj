<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\Generator\PhpGenerator;
use Gdbots\Pbjc\Generator\JsonGenerator;

SchemaStore::addDir(__DIR__.'/schemas');

$generator = new PhpGenerator();
$generator->setOutput(__DIR__.'/src');
//$generator->disableOutput();

$compile = new Compiler();
$compile->setNamespace('acme:blog');
$compile->run($generator);

foreach ($generator->getFiles() as $file => $output) {
    echo highlight_string($output, true).'<hr />';
}

$generator = new JsonGenerator();
$generator->setOutput(__DIR__.'/json-schema');
//$generator->disableOutput();

$compile = new Compiler();
$compile->setNamespace('acme:blog');
$compile->run($generator);

foreach ($generator->getFiles() as $file => $output) {
    $output = sprintf("<?php\n\n\$json = %s;\n", var_export(json_decode($output, true), true));

    echo highlight_string($output, true).'<hr />';
}
