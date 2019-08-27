<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MessageApprovalController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function __invoke(Message $data)
    {
        if ($data->getStatus() === Message::STATUS_PENDING_APPROVAL) {
            $data->setStatus(Message::STATUS_NEW);
        }
    }
}
