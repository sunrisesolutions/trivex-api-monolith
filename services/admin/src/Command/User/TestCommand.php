<?php

namespace App\Command\User;

use App\Entity\User\User;
use App\Util\User\ApiResourceUtil;
use App\Util\User\AppUtil;
use App\Util\User\BaseUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';
    private $apiResourceUtil;

    public function __construct(string $name = null, ApiResourceUtil $apiResourceUtil)
    {
        parent::__construct($name);
        $this->apiResourceUtil = $apiResourceUtil;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
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

        $fetcher = $this->apiResourceUtil;
        $data = $fetcher->fetchResource('person', ['userUuid' => 'USER-5d41ceaa61d4b-012301082019',
        ]);
        var_dump($data);
        var_dump($token = $fetcher->generateRootAdminToken());
        $filelist = array();
        $io->note(AppUtil::APP_NAME);
//        if ($handle = opendir("/srv/api/libraries/component/utils/src/Util")) {
//            while ($entry = readdir($handle)) {
//                $io->note($entry);
//            }
//            closedir($handle);
//        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}

