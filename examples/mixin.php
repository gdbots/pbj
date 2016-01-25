<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;

SchemaStore::addDir(__DIR__.'/schemas');

$jsonCompile = new Compiler('json');
$jsonCompile->generate();

$phpCompile = new Compiler('php');
$phpCompile->generate();
