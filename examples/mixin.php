<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;

SchemaStore::addDir($commandDir = __DIR__.'/../tests/Fixtures/schemas/pbj/mixin/command');
SchemaStore::addDir($entityDir = __DIR__.'/../tests/Fixtures/schemas/pbj/mixin/entity');

$compile = new Compiler('php');
$compile->generate();
