<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\AwsSqsWorkerException;
use App\Message\ConsumerInterface;
use App\Message\Message;
use App\Util\AwsSqsUtilInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AwsSqsWorkerCommand extends Command
{
    private const LIMIT_MIN = 1;
    private const LIMIT_MAX = 50;

    private $awsSqsUtil;
    private $consumer;
    private $logger;

    public function __construct(
        AwsSqsUtilInterface $awsSqsUtil,
        ConsumerInterface $consumer,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->awsSqsUtil = $awsSqsUtil;
        $this->consumer = $consumer;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('app:aws-sqs-worker')
            ->setDescription('Watches AWS SQS queues for messages.')
            ->addOption(
                'queue',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the queue to watch.'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum amount of messages a worker should consume.',
                self::LIMIT_MAX
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parameters = $this->extractParameters($input);
        $processed = 0;
        $queueUrl = $this->awsSqsUtil->getQueueUrl($parameters['queue']);

        $this->handleInterruption();

        $output->writeln(
            sprintf('Watching the "%s" queue for "%d" messages ...', $parameters['queue'], $parameters['limit'])
        );

        while (true) {
            if ($processed === $parameters['limit']) {
                exit;
            }

            $this->handleMemory($parameters['queue'], $parameters['limit']);
            $message = $this->awsSqsUtil->receiveMessage($queueUrl, $parameters['queue']);

            if ($message instanceof Message) {
                $output->writeln('Consuming a message ... '. $message->id. ' ...... '.$message->body);
                $this->consumer->consume($message, $parameters['queue']);

                ++$processed;
            } else {
//                $output->writeln('Sleeping for 10 seconds due to no message ... at '.$queueUrl);

                sleep(10);
            }
        }
    }

    private function extractParameters(InputInterface $input): iterable
    {
        $queue = (string) $input->getOption('queue');
        if (null === $queue || !trim($queue)) {
            throw new AwsSqsWorkerException('The "--queue" option requires a value.');
        }

        $limit = (int) $input->getOption('limit');
        if ($limit < self::LIMIT_MIN || $limit > self::LIMIT_MAX) {
            $limit = self::LIMIT_MAX;
        }

        return ['queue' => $queue, 'limit' => $limit];
    }

    private function handleInterruption(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () {
            throw new AwsSqsWorkerException('Process has been terminated with the "ctrl+c" signal.');
        });
        pcntl_signal(SIGTERM, function () {
            throw new AwsSqsWorkerException('Process has been terminated with the "kill" signal.');
        });
    }

    private function handleMemory(string $queue, int $limit): void
    {
        // 104857600 bytes = 100 megabytes
        if (104857600 < memory_get_peak_usage(true)) {
            throw new AwsSqsWorkerException(
                sprintf('Run out of memory while watching the "%s" queue for "%d" messages.', $queue, $limit)
            );
        }
    }
}
