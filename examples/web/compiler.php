<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../pbj-schema-stores.php';

use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\Util\OutputFile;
use Symfony\Component\Yaml\Parser;

$parser = new Parser();
$settings = $parser->parse(file_get_contents(__DIR__.'/../pbjc.yml'));

$languages = $settings['languages'];
unset($settings['languages']);

$compiler = new Compiler();

$rootDir = __DIR__.'/.'; // folder location hack

foreach ($languages as $language => $values) {

    $options = array_merge($settings, [
        'output' => $rootDir.$values['output'],
        'callback' => function (OutputFile $file) {
            echo highlight_string($file->getContents(), true).'<hr />';

            if (!is_dir(dirname($file->getFile()))) {
                mkdir(dirname($file->getFile()), 0777, true);
            }

            file_put_contents($file->getFile(), $file->getContents());
        }
    ]);

    if (isset($values['manifest']) && $values['manifest']) {
        $options['manifest'] = $rootDir.$values['manifest'];
    }

    $compiler->run($language, new CompileOptions($options));
}
