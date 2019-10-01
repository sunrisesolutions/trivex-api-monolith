<?php

namespace App\Command;

use App\Entity\Person\Person;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixDataCommand extends Command
{
    protected static $defaultName = 'app:fix-data';
    private $container;

    public function __construct(string $name = null, ContainerInterface $c)
    {
        parent::__construct($name);
        $this->container = $c;
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

        $registry = $this->container->get('doctrine');
        $manager = $this->container->get('doctrine.orm.default_entity_manager');
        $personRepo = $registry->getRepository(Person::class);
        $people = $personRepo->findAll();
        /** @var Person $person */
        foreach ($people as $person) {
            $phone = $person->getPhoneNumber();
            if (!empty($phone)) {
                $res = preg_replace("/[^0-9]/", "", $phone);
                $person->setPhoneNumber($res);
                $person->getUser()->setPhone($res);
                $manager->persist($person);
            }
        }

        $manager->flush();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
