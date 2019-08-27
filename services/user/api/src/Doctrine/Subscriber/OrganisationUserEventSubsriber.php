<?php
/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine\Subscriber;

use App\Entity\OrganisationUser;
use App\Entity\Organisation;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use GuzzleHttp\Client;
use mysql_xdevapi\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Symfony\Component\Dotenv\Dotenv;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrganisationUserEventSubsriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $em = $args->getObjectManager();
        if (!$object instanceof OrganisationUser) return;

        if (!empty($object->getOrganisationUuid())) {
            $org = $em->getRepository(Organisation::class)->findOneBy(['uuid' => $object->getOrganisationUuid()]);
            if (!empty($org)) {
                $org->addOrganisationUser($object);
                $object->setOrganisation($org);
                $em->persist($org);
                $em->flush();
            }
        }

        if (!empty($object->getPersonUuid())) {
            $url = 'https://' . $_ENV['PERSON_SERVICE_HOST'] . '/user/' . $object->getPersonUuid();
            $client = new Client([
                'http_errors' => false,
                'verify' => false,
                'curl' => [
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ]);
            try {
                $res = $client->request('GET', $url, []);
                if ($res->getStatusCode() != 200) {
                    throw new HttpException(500, 'Request: (' . $url . ') error code: (' . $res->getStatusCode() . ')');
                }

                $data = json_decode($res->getBody()->getContents(), true);
                if (!isset($data['userUuid'])) {
                    throw new NotFoundHttpException('userUuid null');
                }

                $user = $em->getRepository(User::class)->findOneBy(['uuid' => $data['userUuid']]);
                if (empty($user)) {
                    throw new HttpException(500, 'person with userUuid: (' . $data['userUuid'] . ') not found');
                }

                $user->addOrganisationUser($object);
                $object->setUser($user);

                $em->persist($user);
                $em->flush();
            } catch (\Exception $exception) {}
        }
    }
}
