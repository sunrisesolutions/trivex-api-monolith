<?php

namespace App\Command\Messaging;

use App\Entity\Messaging\Delivery;
use App\Entity\Messaging\IndividualMember;
use App\Entity\Messaging\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixDataCommand extends Command
{
    protected static $defaultName = 'app:fix-data';

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

        $deliveries = $this->manager->getRepository(Delivery::class)->findAll();
        /** @var Delivery $delivery */
        foreach ($deliveries as $delivery) {
            $delivery->fixData();
            $this->manager->persist($delivery);
        }
        $messages = $this->manager->getRepository(Message::class)->findBy(['status' => Message::STATUS_DELIVERY_SUCCESSFUL]);
        /** @var Message $message */
        foreach ($messages as $message) {
            $message->fixData();
            $this->manager->persist($message);

            $deliveries = $message->getDeliveries();
            $found = false;
            /** @var Delivery $delivery */
            foreach ($deliveries as $delivery) {
                if ($delivery->getRecipient()->getUuid() === $message->getSenderUuid()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $delivery = $delivery = Delivery::createInstance($message, $message->getSender());
                $this->manager->persist($delivery);
            }
        }
        $members = $this->manager->getRepository(IndividualMember::class)->findBy(['messageAdminGranted' => null,
        ]);
        /** @var IndividualMember $member */
        foreach ($members as $member) {
            $member->fixData();
            $this->manager->persist($member);
        }
        $this->manager->flush();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
