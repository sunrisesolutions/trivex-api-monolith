<?php

namespace App\Tests\Api;

use App\Entity\Organisation;
use App\Entity\OrganisationUser;
use App\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

class UserTests extends WebTestCase
{
    use RefreshDatabaseTrait;

    protected $client;

    function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testUserList()
    {
        print_r($this->jwtToken());
        exit();
        $token = $this->jwtToken();
        $response = $this->request('GET', 'users?page=1', null, ['Authorization' => 'Bearer ' . $token]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseArray = json_decode($response->getContent(),true);
        $this->assertArrayHasKey('email', $responseArray['hydra:member'][0]);
        $this->assertArrayHasKey('username', $responseArray['hydra:member'][0]);
        $this->assertArrayHasKey('idNumber', $responseArray['hydra:member'][0]);
        $this->assertArrayHasKey('phone', $responseArray['hydra:member'][0]);
        $this->assertArrayHasKey('birthDate', $responseArray['hydra:member'][0]);

        $this->assertTrue(is_string($responseArray['hydra:member'][0]['email']));
        $this->assertTrue(is_string($responseArray['hydra:member'][0]['username']));
        $this->assertTrue(is_string($responseArray['hydra:member'][0]['idNumber']));
        $this->assertTrue(is_string($responseArray['hydra:member'][0]['phone']));
        $this->assertTrue(is_string($responseArray['hydra:member'][0]['birthDate']));
    }

    public function PostUser() {
        $token = $this->jwtToken();
        $response = $this->request('POST', 'users', [
            'email' => 'user7@gmail.com',
            'plainPassword' => '123456',
            'username' => 'user7',
            'idNumber' => 'U1-024290126',
            'phone' => '123456789',
            'birthDate' => '2019-07-01T15:54:18.354Z'
        ], ['Authorization' => 'Bearer ' . $token]);
        $this->assertEquals(201, $response->getStatusCode());

        $user = static::$container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => 'user7']);
        $this->assertNotEmpty($user);
    }

    public function GetUser() {
        $user = static::$container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => 'user1']);
        $token = $this->jwtToken();
        $response = $this->request('GET', 'users/' . $user->getId(), null, ['Authorization' => 'Bearer ' . $token]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseArray = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('email', $responseArray);
        $this->assertArrayHasKey('username', $responseArray);
        $this->assertArrayHasKey('idNumber', $responseArray);
        $this->assertArrayHasKey('phone', $responseArray);
        $this->assertArrayHasKey('birthDate', $responseArray);

        $this->assertTrue(is_string($responseArray['email']));
        $this->assertTrue(is_string($responseArray['username']));
        $this->assertTrue(is_string($responseArray['idNumber']));
        $this->assertTrue(is_string($responseArray['phone']));
        $this->assertTrue(is_string($responseArray['birthDate']));
    }

    public function DeleteUser() {
        $repository = static::$container->get('doctrine')->getRepository(User::class);
        $token = $this->jwtToken();
        $user = $repository->findOneBy([], ['id' => 'DESC']);
        $userId = $user->getId();
        $response = $this->request('DELETE', 'users/' . $userId, null, ['Authorization' => 'Bearer ' . $token]);
        $this->assertEquals(204, $response->getStatusCode());

        $u = $repository->find($userId);
        $this->assertNull($u);
    }

    public function EditUser() {
        $repository = static::$container->get('doctrine')->getRepository(User::class);
        $token = $this->jwtToken();
        $user = $repository->findOneBy(['username' => 'user1']);
        $content = [
            'email' => 'testemail@gmail.com',
            'plainPassword' => '123654',
            'idNumber' => '250880690',
            'username' => 'user50',
            'phone' => '012345678',
            'birthDate' => '2019-07-02T11:50:12.000Z'
        ];
        $response = $this->request('PUT', '/users/' . $user->getId(), json_encode($content), ['Authorization' => 'Bearer ' . $token]);
        $this->assertEquals(200, $response->getStatusCode());
        $user = $repository->findOneBy(['username' => 'user50']);
        $this->assertEquals($content['email'], $user->getEmail());
        $this->assertEquals($content['plainPassword'], $user->getPlainPassword());
        $this->assertEquals($content['idNumber'], $user->getIdNumber());
        $this->assertEquals($content['phone'], $user->getPhone());
        $this->assertEquals($content['birthDate'], $user->getBirthDate());
    }

    protected function jwtToken(): string {
        /** @var User $user */
        $user = static::$container->get('doctrine')->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);

        /** @var OrganisationUser $im */
        $im = static::$container->get('doctrine')->getRepository(OrganisationUser::class)->findOneBy(['user'=>$user]);
        $this->assertNotEmpty($im);
        $user->addOrganisationUser($im);
        $user->setRoles(['ROLE_ADMIN']);

        $requestStack = static::$container->get('request_stack');
        $requestStack->push(new Request([], [], [], [], [], ['REMOTE_ADDR' => '10.10.10.10']));
        $jwtManager = static::$container->get('lexik_jwt_authentication.jwt_manager');
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

            $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
        }

        if (is_array($content) && false !== preg_match('#^application/(?:.+\+)?json$#', $server['CONTENT_TYPE'])) {
            $content = json_encode($content);
        }

        $this->client->request($method, $uri, [], [], $server, $content);

        return $this->client->getResponse();
    }
}
