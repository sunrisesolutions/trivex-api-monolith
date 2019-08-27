<?php


namespace App\Util\Organisation;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Sdk;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\JWTUser;
use GuzzleHttp\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ApiResourceUtil
{
    /** @var SnsClient */
    private $client;
    private $sdk;
    private $applicationName;
    private $env;

    private $topics = [];

    private $normalizer;

    private $manager;
    /** @var JWTManager */
    private $jwtManager;

    private static $instance;

    public function __construct(JWTTokenManagerInterface $jwtManager, Sdk $sdk, iterable $config, iterable $credentials, string $env, iterable $snsConfig, ObjectNormalizer $normalizer, EntityManagerInterface $manager)
    {
        $this->client = $sdk->createSns($config + $credentials);
        $this->jwtManager = $jwtManager;

        $this->sdk = $sdk;
        $this->applicationName = BaseUtil::PROJECT_NAME.'_'.AppUtil::APP_NAME;
        $this->env = $env;
        $this->queuePrefix = $this->applicationName.'_'.$env.'_';
        $this->snsConfig = $snsConfig;
        $this->normalizer = $normalizer;
        $this->manager = $manager;

        self::$instance = $this;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function generateRootAdminToken()
    {
        $sadmin = new JWTUser('rootadmin', ['ROLE_SUPER_ADMIN',
        ], null, null, 'ROOT_ADMIN_UUID');

        return $token = $this->jwtManager->create($sadmin);
    }

    public function fetchResource($resource, $queryParams = [])
    {
        $plurals = ['person' => 'people',
        ];
        $token = $this->generateRootAdminToken();

        $queryString = '';
        if (!empty($queryParams)) {
            $queryString = '?';
            $index = 0;
            foreach ($queryParams as $key => $val) {
                if ($index > 0) {
                    $queryString .= '&';
                    $index++;
                }
                $queryString .= $key.'='.$val;
            }
        }

        $url = 'https://'.$_ENV[sprintf('%s_SERVICE_HOST', strtoupper($resource))].'/'.$plurals[$resource].$queryString;
        $client = new Client([
            'http_errors' => false,
            'verify' => false,
            'curl' => [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]);

        try {
            $res = $client->request('GET', $url, ['headers' => ['Authorization' => 'Bearer '.$token]]);
            if ($res->getStatusCode() === 200) {
                $data = json_decode($res->getBody()->getContents(), true);
                return $data;
//                if (isset($data['hydra:totalItems']) && $data['hydra:totalItems'] > 0) {
//
//                }
            } else {
                return $res->getBody()->getContents();
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}