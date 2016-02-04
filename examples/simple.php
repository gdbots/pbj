<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\Compiler;

SchemaStore::addDir(__DIR__.'/schemas');
SchemaStore::addDir(__DIR__.'/../vendor/gdbots/schemas', true);

$compile = new Compiler(__DIR__.'/src');
$compile->generate('php', true);

/**
 * Update output directory to point to the `root` where json files will be stored
 * or just create a new `Compiler` object.
 *
 * @see Gdbots\Pbjc\Generator\JsonGenerator::getTarget with output structure
 */
$compile->setOutputDirectory(__DIR__.'/schemas');
$compile->generate('json', true);
