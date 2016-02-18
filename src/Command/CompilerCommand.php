<?php

namespace Gdbots\Pbjc\Command;

use Gdbots\Pbjc\Compiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Parser;

/**
 * Provides the console command to generate compiled files.
 */
class CompilerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pbjc:compiler')
            ->addOption(
                'language',
                'l',
                 InputOption::VALUE_OPTIONAL,
                'The generated language (php, or json)',
                'php'
            )
            ->addOption(
                'output',
                'o',
                 InputOption::VALUE_OPTIONAL,
                'The output directory files will be generate in'
            )
            ->addOption(
                'namespace',
                's',
                 InputOption::VALUE_OPTIONAL,
                'The schema namespace (vendor:package)'
            )
            ->addOption(
                'config',
                'c',
                 InputOption::VALUE_OPTIONAL,
                'The pbjc config yaml file'
            )
            ->setDescription('Generate compiled files')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command compiles and generates files for a select language.

To generate files you would need to specify the language, namespace and output directory:

  <info>pbjc --language=php --output=src</info>

Note that currently we only support <comment>php</comment> or <comment>json</comment> languages.

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $language = $input->getOption('language') ?: 'php';
        $namespace = $input->getOption('namespace');
        $output = $input->getOption('output');
        $file = $input->getOption('config') ?: sprintf('%s/pbjc.yml', getcwd());

        if (file_exists($file)) {
            $parser = new Parser();
            $config = $parser->parse(file_get_contents($file));

            if (!$namespace && isset($config['pbjc']['namespace'])) {
                $namespace = $config['pbjc']['namespace'];
            }

            if (!$output) {
                if (isset($config['pbjc']['output'])) {
                    $output = $config['pbjc']['output'];
                }

                if (isset($config['pbjc']['language'][$language])) {
                    $output = $config['pbjc']['language'][$language];
                }
            }
        }

        try {
            $compile = new Compiler();

            $generator = $compile->run($language, $namespace, $output);

            if (count($generator->getFiles()) === 0) {
                throw new \Exception('No files were generated.');
            }

            $io->title('Generated files:');
            $io->listing(array_keys($generator->getFiles()));
            $io->success("\xf0\x9f\x91\x8d"); //thumbs-up-sign
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
