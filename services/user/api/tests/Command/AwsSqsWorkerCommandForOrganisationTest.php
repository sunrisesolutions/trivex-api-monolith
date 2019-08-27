<?php

namespace App\Tests\Command;

use App\Entity\Organisation;
use App\Security\JWTUser;
use App\Util\AwsSqsUtil;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use App\Message\Message;
use App\Util\AppUtil;

class AwsSqsWorkerCommandForOrganisationTest extends WebTestCase
{
    use RefreshDatabaseTrait;

    protected $client;

    protected $queueName;

    protected $queueUrl;

    /** @var AwsSqsUtil */
    protected $sqsUtil;

    function setUp()
    {
        parent::setUp();
        self::bootKernel();
        $this->client = static::createClient();
        $this->sqsUtil = static::$container->get('app_util_aws_sqs_util');
        $this->queueName = 'ORG';
        $this->queueUrl = $this->sqsUtil->getQueueUrl($this->queueName);
        $this->purgeQueue();
    }

    public function testPostOrg() {
        $msg = [
            'Type' => 'Notification',
            'MessageId' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Subject' => 'My First Message',
            'Message' => [],
            'Timestamp' => '2012-05-02T00:54:06.655Z',
            'SignatureVersion' => '1',
            'Signature' => 'EXAMPLEw6JRNwm1LFQL4ICB0bnXrdB8ClRMTQFGBqwLpGbM78tJ4etTwC5zU7O3tS6tGpey3ejedNdOJ+1fkIp9F2/LmNVKb5aFlYq+9rk9ZiPph5YlLmWsDcyC5T+Sy9/umic5S0UQc2PEtgdpVBahwNOdMW4JPwk0kAJJztnc=',
            'SigningCertURL' => 'https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem',
            'UnsubscribeURL' => 'https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a96'
        ];

        $random = rand(10, 9999) . time();
        $orgAr = [
            'logoWriteForm' => [
                'filePath' => 'S3_DIRECTORY/organisation/logo/UID-4444',
                'attributes' => [
                    'action' => 'https://s3_bucket.s3.s3_region.amazonaws.com',
                    'method' => 'POST',
                    'enctype' => 'multipart/form-data'
                ],
                'inputs' => [
                    'acl' => 'private',
                    'key' => '${filename}',
                    'X-Amz-Credential' => 'S3_ACCESS/20190706/S3_REGION/s3/aws4_request',
                    'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
                    'X-Amz-Date' => '20190706T200747Z',
                    'Policy' => 'eyJleHBpcmF0aW9uIjoiMjAxOS0wNy0wNlQyMjowNzo0N1oiLCJjb25kaXRpb25zIjpbeyJhY2wiOiJwcml2YXRlIn0seyJidWNrZXQiOiJTM19CVUNLRVQifSxbInN0YXJ0cy13aXRoIiwiJGtleSIsIlMzX0RJUkVDVE9SWVwvb3JnYW5pc2F0aW9uXC9sb2dvIl0seyJYLUFtei1EYXRlIjoiMjAxOTA3MDZUMjAwNzQ3WiJ9LHsiWC1BbXotQ3JlZGVudGlhbCI6IlMzX0FDQ0VTU1wvMjAxOTA3MDZcL1MzX1JFR0lPTlwvczNcL2F3czRfcmVxdWVzdCJ9LHsiWC1BbXotQWxnb3JpdGhtIjoiQVdTNC1ITUFDLVNIQTI1NiJ9XX0=',
                    'X-Amz-Signature' => '1af431b86999ea4acb59f162ff1713ba16a045ef916d42b84c35f4430645c85c',
                ]
            ],
            'logoReadUrl' => '',
            'uuid' => 'UID-' . $random,
            'foundedOn' => '2019-06-28T06:14:18+00:00',
            'type' => 'type-' . $random,
            'address' => 'white house',
            'name' => 'Donal Trump',
            'registrationNumber' => 'rn-' . $random,
            'parent' => '',
            'children' => [],
            'logoName' => '',
            'individualMembers' => [],
            'code' => 'code-' . $random,
            'subdomain' => 'test.domain.com',
            'role' => [],
            '_SYSTEM_OPERATION' => Message::OPERATION_POST
        ];

        $data = [];
        $data['data']['organisation'] = $orgAr;
        $data['version'] = AppUtil::MESSAGE_VERSION;
        $msg['Message'] = json_encode($data);

        $this->sqsUtil->sendMessage($this->queueUrl, json_encode($msg));

        $kernel = static::createKernel();
        $app = new Application($kernel);
        $command = $app->find('app:aws-sqs-worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--queue' => $this->queueName,
            '--limit' => 1,
            '--env' => 'test'
        ]);

        $orgRepo = static::$container->get('doctrine')->getRepository(Organisation::class);
        $org = $orgRepo->findOneBy(['uuid' => $orgAr['uuid']]);
        $this->assertNotEmpty($org);
    }

    protected function purgeQueue()
    {
        while (!empty($message = $this->sqsUtil->receiveMessage($this->queueUrl, $this->queueName))) {
            $this->sqsUtil->deleteMessage($message);
        }
    }

    public function testPutPerson()
    {
        $personRepo = static::$container->get('doctrine')->getRepository(Person::class);
        /** @var Person $person */
        $person = $personRepo->findOneBy([], ['id' => 'DESC']);
        $this->assertNotEmpty($person);

        $msg = [
            'Type' => 'Notification',
            'MessageId' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Subject' => 'My First Message',
            'Message' => [],
            'Timestamp' => '2012-05-02T00:54:06.655Z',
            'SignatureVersion' => '1',
            'Signature' => 'EXAMPLEw6JRNwm1LFQL4ICB0bnXrdB8ClRMTQFGBqwLpGbM78tJ4etTwC5zU7O3tS6tGpey3ejedNdOJ+1fkIp9F2/LmNVKb5aFlYq+9rk9ZiPph5YlLmWsDcyC5T+Sy9/umic5S0UQc2PEtgdpVBahwNOdMW4JPwk0kAJJztnc=',
            'SigningCertURL' => 'https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem',
            'UnsubscribeURL' => 'https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a96'
        ];

        $serializer = static::$container->get('serializer');
        $randVal = 'name-'.rand(1, 9999).time();
        $person->setName($randVal);

        $personAr = json_decode($serializer->serialize($person, 'json'), true);
        $personAr['_SYSTEM_OPERATION'] = Message::OPERATION_PUT;

        $data = [];
        $data['data']['person'] = $personAr;
        $data['version'] = AppUtil::MESSAGE_VERSION;
        $msg['Message'] = json_encode($data);

        $this->sqsUtil->sendMessage($this->queueUrl, json_encode($msg));

        $kernel = static::createKernel();
        $app = new Application($kernel);
        $command = $app->find('app:aws-sqs-worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--queue' => $this->queueName,
            '--limit' => 1,
            '--env' => 'test'
        ]);

        $person = $personRepo->findOneBy(['uuid' => $person->getUuid()]);
        $this->assertEquals($person->getName(), $randVal);
    }

    public function testDeletePerson() {
        $personRepo = static::$container->get('doctrine')->getRepository(Person::class);
        $person = $personRepo->findOneBy([], ['id' => 'DESC']);
        $this->assertNotEmpty($person);

        $msg = [
            'Type' => 'Notification',
            'MessageId' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Subject' => 'My First Message',
            'Message' => [],
            'Timestamp' => '2012-05-02T00:54:06.655Z',
            'SignatureVersion' => '1',
            'Signature' => 'EXAMPLEw6JRNwm1LFQL4ICB0bnXrdB8ClRMTQFGBqwLpGbM78tJ4etTwC5zU7O3tS6tGpey3ejedNdOJ+1fkIp9F2/LmNVKb5aFlYq+9rk9ZiPph5YlLmWsDcyC5T+Sy9/umic5S0UQc2PEtgdpVBahwNOdMW4JPwk0kAJJztnc=',
            'SigningCertURL' => 'https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem',
            'UnsubscribeURL' => 'https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a96'
        ];

        $serializer = static::$container->get('serializer');

        $personAr = json_decode($serializer->serialize($person, 'json'), true);
        $personAr['_SYSTEM_OPERATION'] = Message::OPERATION_DELETE;

        $data = [];
        $data['data']['person'] = $personAr;
        $data['version'] = AppUtil::MESSAGE_VERSION;
        $msg['Message'] = json_encode($data);

        $this->sqsUtil->sendMessage($this->queueUrl, json_encode($msg));

        $kernel = static::createKernel();
        $app = new Application($kernel);
        $command = $app->find('app:aws-sqs-worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--queue' => $this->queueName,
            '--limit' => 1,
            '--env' => 'test'
        ]);

        $del = $personRepo->findOneBy(['uuid' => $person->getUuid()]);
        $this->assertEmpty($del);
    }

    protected function jwtToken(): string
    {
        $requestStack = static::$container->get('request_stack');
        $requestStack->push(new Request([], [], [], [], [], ['REMOTE_ADDR' => '10.10.10.10']));
        $jwtManager = static::$container->get('lexik_jwt_authentication.jwt_manager');
        $user = new JWTUser('admin', ['ROLE_ADMIN'], '123', '456', 'U1-024290123');
        return $jwtManager->create($user);
    }

    protected function request(string $method, string $uri, $content = null, array $headers = []): Response
    {
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                $server['CONTENT_TYPE'] = $value;

                continue;
            }

            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        if (is_array($content) && false !== preg_match('#^application/(?:.+\+)?json$#', $server['CONTENT_TYPE'])) {
            $content = json_encode($content);
        }

        $this->client->request($method, $uri, [], [], $server, $content);

        return $this->client->getResponse();
    }
}