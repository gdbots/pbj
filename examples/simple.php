<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler\PhpCompiler;
use Gdbots\Pbjc\Compiler\JsonCompiler;

SchemaStore::addDir(__DIR__.'/schemas');

$compile = new PhpCompiler(__DIR__.'/src');
$compile->generate(true);

$compile = new JsonCompiler(__DIR__.'/schemas');
$compile->generate(true);
