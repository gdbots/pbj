<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;

SchemaStore::addDir(__DIR__.'/schemas');
SchemaStore::addDir(__DIR__.'/../vendor/gdbots/schemas/schemas/gdbots/pbj', true);
SchemaStore::addDir(__DIR__.'/../vendor/gdbots/schemas/schemas/gdbots/pbjx', true);

$compile = new Compiler('php', __DIR__.'/src');
$compile->generate(true);
