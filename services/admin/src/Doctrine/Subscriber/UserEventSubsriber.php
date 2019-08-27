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

use App\Entity\User\User;
use App\Util\User\CanonicalFieldsUpdater;
use App\Util\User\PasswordUpdaterInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Doctrine listener updating the canonical username and password fields.
 *
 * @author Christophe Coevoet <stof@notk.org>
 * @author David Buchmann <mail@davidbu.ch>
 */
class UserEventSubsriber implements EventSubscriber
{
    private $passwordUpdater;
    private $canonicalFieldsUpdater;
    private $container;

    public function __construct(PasswordUpdaterInterface $passwordUpdater, CanonicalFieldsUpdater $canonicalFieldsUpdater, ContainerInterface $c)
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->canonicalFieldsUpdater = $canonicalFieldsUpdater;
        $this->container = $c;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preUpdate',
            'postLoad',
        ];
    }

    /**
     * Pre persist listener based on doctrine common.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof UserInterface) {
            $this->updateUserFields($object);
        }
    }

    /**
     * Pre update listener based on doctrine common.
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof UserInterface) {
            $this->updateUserFields($object);
            $this->recomputeChangeSet($args->getObjectManager(), $object);
        }
    }

    /**
     * Updates the user properties.
     *
     * @param UserInterface $user
     */
    private function updateUserFields(UserInterface $user)
    {
        //////// MODIF 001 ///////
        if ($user instanceof User) {
            if (empty($user->getUsername()) && !empty($user->getEmail())) {
                $user->setUsername($user->getEmail());
            }
            if (empty($user->getPlainPassword()) && empty($user->getPassword())) {
                $user->setPlainPassword(rand(0, 10 * 1000));
            }
        }
        //////// END MODIF 001 ///////

        $this->canonicalFieldsUpdater->updateCanonicalFields($user);
        $this->passwordUpdater->hashPassword($user);
    }

    /**
     * Recomputes change set for Doctrine implementations not doing it automatically after the event.
     *
     * @param ObjectManager $om
     * @param UserInterface $user
     */
    private function recomputeChangeSet(ObjectManager $om, UserInterface $user)
    {
        $meta = $om->getClassMetadata(get_class($user));

        if ($om instanceof EntityManager) {
            $om->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $user);

            return;
        }

        if ($om instanceof DocumentManager) {
            $om->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $user);
        }
    }

    /**
     * Post load listener based on doctrine common.
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof User) {
            if (empty($person = $object->getPerson())) {

            }
        }
    }
}
