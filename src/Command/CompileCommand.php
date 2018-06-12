<?php

namespace Gdbots\Pbjc\Command;

use Gdbots\Pbjc\CompileOptions;
use Gdbots\Pbjc\Compiler;
use Gdbots\Pbjc\Util\OutputFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
                'The generated language'
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getOption('config') ?: sprintf('%s/pbjc.yml', getcwd());

        if (!file_exists($file)) {
            $io->error(sprintf('File "%s" does not exists.', $file));
            return;
        }

        $parser = new Parser();
        $data = $parser->parse(file_get_contents($file));

        if (!is_array($data['namespaces'])) {
            $data['namespaces'] = [$data['namespaces']];
        }

        $io->title('Generated files for namespaces:');
        $io->listing($data['namespaces']);

        // output callback
        $callback = function (OutputFile $file) use ($io) {
            $io->text($file->getFile());

            if (!is_dir(dirname($file->getFile()))) {
                mkdir(dirname($file->getFile()), 0777, true);
            }

            file_put_contents($file->getFile(), $file->getContents());
        };

        $languages = [];
        if (isset($data['languages'])) {
            $languages = $data['languages'];

            unset($data['languages']);
        }

        $compile = new Compiler();

        foreach ($languages as $language => $options) {
            if ($input->getOption('language') && $input->getOption('language') != $language) {
                continue;
            }

            $options = array_merge($data, $options, [
                'callback' => $callback,
            ]);

            try {
                $io->section($language);

                $compile->run($language, new CompileOptions($options));

                $io->success("\xf0\x9f\x91\x8d"); //thumbs-up-sign
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }
    }
}
