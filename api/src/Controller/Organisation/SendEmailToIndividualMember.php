<?php

namespace App\Controller\Organisation;

use App\Entity\Organisation\IndividualMember;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SendEmailToIndividualMember
{
    private $mailer;
    private $registry;
    private $container;

    public function __construct(RegistryInterface $registry, \Swift_Mailer $mailer, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->registry = $registry;
        $this->container = $container;
    }

    public function __invoke(IndividualMember $data): IndividualMember
    {
//        /** @var IndividualMember $member */
//        $member = $this->registry->getRepository(IndividualMember::class)->find($data->emailTo);
        if (!empty($data)) {
            if (!empty($toEmail = $data->getPerson()->getEmail())) {
                $message = (new \Swift_Message($data->getEmailSubject()))
                    ->setFrom('no-reply.member@magentapulse.com')
                    ->setTo($toEmail)
                    ->setBody(
                        $data->getEmailBody(),
                        'text/html'
                    )/*
                 * If you also want to include a plaintext version of the message
                ->addPart(
                    $this->renderView(
                        'emails/registration.txt.twig',
                        array('name' => $name)
                    ),
                    'text/plain'
                )
                */
                ;

                $this->mailer->send($message);

//                $spool = $this->mailer->getTransport()->getSpool();
//                $transport = $this->container->get('swiftmailer.mailer.smtp_mailer.transport.real');
//                if ($spool and $transport) $spool->flushQueue($transport);


            }
        }
        return $data;
    }
}
