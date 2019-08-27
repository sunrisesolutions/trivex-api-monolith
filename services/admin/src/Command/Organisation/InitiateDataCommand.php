<?php

namespace App\Command\Organisation;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitiateDataCommand extends Command
{
    protected static $defaultName = 'app:initiate-data';

    private $manager;

    public function __construct(string $name = null, EntityManagerInterface $manager)
    {
        parent::__construct($name);
        $this->manager = $manager;
    }

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

        $orgs = $this->manager->getRepository(Organisation::class)->findAll();
        /** @var Organisation $org */
        foreach ($orgs as $org) {
            $ims = $org->getIndividualMembers();
            /** @var IndividualMember $im */
            foreach ($ims as $im) {
                $im->updateFulltextString();
                $this->manager->persist($im);
            }
            $this->manager->flush();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
