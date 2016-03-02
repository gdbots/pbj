<?php

namespace Gdbots\Pbjc\Command;

use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\Util\OutputFile;
use Gdbots\Pbjc\Util\ParameterBag;
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
        $options = new ParameterBag();

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
                $options->add($config['languages'][$language]);
            }
        }

        if (!is_array($namespaces)) {
            $namespaces = [$namespaces];
        }

        $options->set('namespaces', $namespaces);

        try {
            $io->title(sprintf('Generated files for "%s":',  implode('", "', $namespaces)));

            $compile = new Compiler();

            $compile->setDispatcher(function (OutputFile $file) use ($io) {
                $io->text($file->getFile());
            });

            $compile->run($language, $options);

            $io->success("\xf0\x9f\x91\x8d"); //thumbs-up-sign
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
