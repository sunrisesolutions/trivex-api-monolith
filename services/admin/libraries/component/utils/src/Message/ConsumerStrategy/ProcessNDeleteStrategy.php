<?php

declare(strict_types=1);

namespace App\Message\ConsumerStrategy;

use App\Message\Message;
use App\Util\AwsSqsUtilInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProcessNDeleteStrategy implements StrategyInterface
{
    public const QUEUE_NAME = 'ORG';

    private $awsSqsUtil;
    private $logger;
    private $manager;

    public function __construct(
        AwsSqsUtilInterface $awsSqsUtil,
        LoggerInterface $logger,
        EntityManagerInterface $manager
    )
    {
        $this->awsSqsUtil = $awsSqsUtil;
        $this->logger = $logger;
        $this->manager = $manager;
    }

    public function canProcess(Message $message = null, string $queue = null): bool
    {
        return true;
//        return self::QUEUE_NAME === strtoupper($queue);
    }

    public function process(Message $message): void
    {
        $body = json_decode($message->body, true);
        $message->updateEntity($this->manager);

//        if (array_key_exists('is_good_message', $body) && $body['is_good_message']) {
//            $this->logger->alert(sprintf('The message "%s" has been consumed.', $message->id));
//        } else {
//            $this->logger->alert(sprintf('The message "%s" has been deleted.', $message->id));
//        }

        $this->awsSqsUtil->deleteMessage($message);
    }
}
