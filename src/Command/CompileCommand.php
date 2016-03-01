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
 * Provides the console command to compile files.
 */
class CompileCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pbjc')
            ->addOption(
                'language',
                'l',
                 InputOption::VALUE_OPTIONAL,
                'The generated language (php, or json)',
                'php'
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

  <info>pbjc --language=php</info>

By default no option is required when running from the same folder contains the
<comment>pbjc.yml</comment> configuration file.

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
        $file = $input->getOption('config') ?: sprintf('%s/pbjc.yml', getcwd());

        $namespaces = null;
        $options = null;

        if (!empty($namespaces)) {
            $namespaces = explode(',', $namespaces);
        }

        if (file_exists($file)) {
            $parser = new Parser();
            $config = $parser->parse(file_get_contents($file));

            if (isset($config['namespaces'])) {
                $namespaces = $config['namespaces'];
            }

            if (isset($config['languages'][$language])) {
                $options = $config['languages'][$language];
            }
        }

        if (!is_array($namespaces)) {
            $namespaces = [$namespaces];
        }

        $options['namespaces'] = $namespaces;

        try {
            $compile = new Compiler();

            $generator = $compile->run($language, $options);

            if (count($generator->getFiles()) === 0) {
                throw new \Exception('No files were generated.');
            }

            $io->title(sprintf('Generated files for "%s":', $ns));
            $io->listing(array_keys($generator->getFiles()));

            $io->success("\xf0\x9f\x91\x8d"); //thumbs-up-sign
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
