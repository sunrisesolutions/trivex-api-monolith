<?php

declare(strict_types=1);

namespace App\Util\Authorisation;

use App\Message\Message;
use App\Message\MessageFactory;
use Aws\Result;
use Aws\Sdk;
use Aws\Sqs\SqsClient;
use App\Message\Entity as MessageEntity;

class AwsSqsUtil implements AwsSqsUtilInterface
{

    private $mf;

    /** @var SqsClient */
    private $client;
    private $sdk;
    private $applicationName;
    private $env;
    private $queuePrefix;

    private $queues;

    public function __construct(MessageFactory $mf, Sdk $sdk, iterable $config, iterable $credentials, string $env)
    {
        $this->mf = $mf;
        $this->client = $sdk->createSqs($config + $credentials);
        $this->sdk = $sdk;
        $this->applicationName = BaseUtil::PROJECT_NAME.'_'.AppUtil::APP_NAME;
        $this->env = $env;
        $this->queuePrefix = $this->applicationName.'_'.$env.'_';
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#createqueue
     */
    public function createQueue(string $name, $prefix = null): ?string
    {
        if (empty($prefix)) {
            $prefix = $this->queuePrefix;
        }
        /** @var Result $result */
        $result = $this->client->createQueue(['QueueName' => $prefix.$name]);

        return $result->get('QueueUrl');
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#listqueues
     */
    public function listQueues(): iterable
    {
//        return   $result = $this->client->listQueues();
        if (!empty($this->queues)) {
            return $this->queues;
        }
        $queues = [];

        /** @var Result $result */
        $result = $this->client->listQueues();
        foreach ($result->get('QueueUrls') as $queueUrl) {
            $queues[] = $queueUrl;
        }

        $this->queues = $queues;
        return $queues;
    }

    /**
     * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#getqueueurl
     */
    public function getQueueUrl(string $name, $prefix = null): ?string
    {
        /** @var Result $result */
        $result = $this->client->getQueueUrl([
            'QueueName' => $this->createQueueName($name, $prefix),
        ]);

        return $result->get('QueueUrl');
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#sendmessage
     */
    public function sendMessage(string $url, string $message): ?string
    {
        /** @var Result $result */
        $result = $this->client->sendMessage([
            'QueueUrl' => $url,
            'MessageBody' => $message,
        ]);

        return $result->get('MessageId');
    }

    public function getQueueArn(string $url): string
    {
        /** @var Result $result */
        $result = $this->client->getQueueAttributes([
            'QueueUrl' => $url,
            'AttributeNames' => ['QueueArn'],
        ]);

        return $result->get('Attributes')['QueueArn'];
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#getqueueattributes
     */
    public function getTotalMessages(string $url): string
    {
        /** @var Result $result */
        $result = $this->client->getQueueAttributes([
            'QueueUrl' => $url,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        return $result->get('Attributes')['ApproximateNumberOfMessages'];
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#purgequeue
     */
    public function purgeQueue(string $url): void
    {
        $this->client->purgeQueue(['QueueUrl' => $url]);
    }

    /**
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletequeue
     */
    public function deleteQueue(string $url): void
    {
        $this->client->deleteQueue(['QueueUrl' => $url]);
    }


    public function createClient(iterable $config, iterable $credentials): void
    {
        $this->client = $this->sdk->createSqs($config + $credentials);
    }

    public function addPermission($args = [])
    {
        $this->client->addPermission($args);
    }

    /**
     * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#receivemessage
     */
    public function receiveMessage(string $url, string $name): ?Message
    {
        /** @var Result $result */
        $result = $this->client->receiveMessage([
            'QueueUrl' => $url,
            'MaxNumberOfMessages' => 1,
        ]);

        $message = null;
        if (null !== $result->get('Messages')) {

            $body = $result->get('Messages')[0]['Body'];
            $id = $result->get('Messages')[0]['MessageId'];
            $receiptHandle = $result->get('Messages')[0]['ReceiptHandle'];
            $message = $this->mf->newMessage(BaseUtil::getFullAppName($name), $url, $id, $body, $receiptHandle);

        }

        return $message;
    }

    /**
     * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletemessage
     */
    public function deleteMessage(Message $message): void
    {
        $this->client->deleteMessage([
            'QueueUrl' => $message->url,
            'ReceiptHandle' => $message->receiptHandle,
        ]);
    }

    /**
     * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#changemessagevisibility
     */
    public function requeueMessage(Message $message): void
    {
        $this->client->changeMessageVisibility([
            'QueueUrl' => $message->url,
            'ReceiptHandle' => $message->receiptHandle,
            'VisibilityTimeout' => 30,
        ]);
    }

    public function createQueueName(string $name, $prefix = null, bool $isDeadLetter = null): string
    {
        if (empty($prefix)) {
            return sprintf(
                '%s_%s_%s%s', // TRIVEX_USER_DEV_ORG
                strtoupper($this->applicationName),
                strtoupper($this->env),
                $name,
                $isDeadLetter ? '_DL' : null
            );
        } else {
            return sprintf(
                '%s%s%s', // TRIVEX_USER_DEV_ORG
                strtoupper($this->queuePrefix),
                $name,
                $isDeadLetter ? '_DL' : null
            );
        }
    }
}
