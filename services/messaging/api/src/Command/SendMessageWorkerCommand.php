<?php

namespace App\Command;

use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Exception\AwsSqsWorkerException;
use App\Service\IndividualMemberService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\MessageSentReport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendMessageWorkerCommand extends Command
{
    private const LIMIT_MIN = 1;
    private const LIMIT_MAX = 500;

    protected static $defaultName = 'app:send-messages';

    private $manager;
    private $imService;

    public function __construct(string $name = null, EntityManagerInterface $manager, IndividualMemberService $imService)
    {
        parent::__construct($name);
        $this->manager = $manager;
        $this->imService = $imService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum amount of messages a worker should consume.',
                self::LIMIT_MAX
            );;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        $parameters = $this->extractParameters($input);
        $processed = 0;
//        $queueUrl = $this->awsSqsUtil->getQueueUrl($parameters['queue']);

        $this->handleInterruption();

        $output->writeln(
            sprintf('Watching the for "%d" messages ...', $parameters['limit'])
        );

        while (true) {
            if ($processed === $parameters['limit']) {
                exit;
            }

            $this->handleMemory($parameters['limit']);

            $mRepo = $this->manager->getRepository(Message::class);
            $messages = $mRepo->findBy(['status' => Message::STATUS_NEW]);

            /** @var Message $message */
            foreach ($messages as $message) {
                $output->writeln('Sending Message ... '.$message->getUuid().' with SUBJ:'.$message->getSubject());
                $res = $this->imService->notifyOneOrganisationIndividualMembers($message);
                $io->note('MSG: '.$message->getUuid().' '.$message->getSubject());
                if(count($res) === 0){
                    $io->note('no push notifs were sent');
                }
                foreach($res as $r){
                    $io->comment($r);
                }
//                var_dump($res);
                /**
                if (is_array($res)) {
                foreach ($res as $_r) {
                if ($_r instanceof MessageSentReport) {
                $io->comment($_r->getRequestPayload().' '.$_r->isSuccess().' '.$_r->getResponseContent());
                } else {
                $io->comment(get_class($_r));
                }
                }
                } else {
                $io->comment('not array '.json_encode($res));
                }
                 */
            }

            /////////////////////////////////////

//            $message = $this->awsSqsUtil->receiveMessage($queueUrl);

//            if ($message instanceof Message) {
//                $output->writeln('Consuming a message ... '. $message->id. ' ...... '.$message->body);
//                $this->consumer->consume($message, $parameters['queue']);
//
            if (count($messages) > 0) {
                ++$processed;
            }

//            } else {
//            $output->writeln('Sleeping for 3 seconds due to no message ... ');

            sleep(3);
//            }
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }

    private function extractParameters(InputInterface $input): iterable
    {

        $limit = (int) $input->getOption('limit');
        if ($limit < self::LIMIT_MIN || $limit > self::LIMIT_MAX) {
            $limit = self::LIMIT_MAX;
        }

        return ['limit' => $limit];
    }

    private function handleInterruption(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () {
            throw new \Exception('Process has been terminated with the "ctrl+c" signal.');
        });
        pcntl_signal(SIGTERM, function () {
            throw new \Exception('Process has been terminated with the "kill" signal.');
        });
    }

    private function handleMemory(int $limit): void
    {
        // 104857600 bytes = 100 megabytes
        if (104857600 < memory_get_peak_usage(true)) {
            throw new \Exception(sprintf('Run out of memory while watching for "%d" messages.', $limit));
        }
    }

}
