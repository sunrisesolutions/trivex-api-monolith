<?php

namespace App\Command\Organisation;

use App\Util\Organisation\AppUtil;
use App\Util\Organisation\BaseUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }


        $filelist = array();
        $io->note(AppUtil::APP_NAME);
        $io->note('hey AWS_SNS_PREFIX');
        $io->note(getenv('AWS_SNS_PREFIX'));
        if ($handle = opendir("/srv/api/libraries/component/utils/src/Message")) {
            while ($entry = readdir($handle)) {
                $io->note($entry);
            }
            closedir($handle);
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}

