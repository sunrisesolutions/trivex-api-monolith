<?php

namespace App\Twig;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Entity\Person\Person;
use App\Service\Organisation\OrganisationService;
use App\Service\ServiceContext;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{

    const TYPE_PDF_DOWNLOAD_SERVICE_SHEET = 'PDF_DOWNLOAD_SERVICE_SHEET';

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }

    public function getFilters()
    {
        return array(//            new TwigFilter('privateMediumUrl', array($this, 'privateMediumUrl')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig\TwigFunction('currentOrganisation', array($this, 'getCurrentOrganisation')),
            new \Twig\TwigFunction('adminUser', array($this, 'getAdminUser')),
        );
    }

    public function getCurrentOrganisation(AdminInterface $admin = null)
    {
        $context = new ServiceContext();
        $context->setType(ServiceContext::TYPE_ADMIN_CLASS);
        $context->setAttribute('parent', $admin->getParent());
        $org = $this->container->get(OrganisationService::class)->getCurrentOrganisation($context);
        return $org;
    }

    public function getAdminUser(Organisation $org)
    {
        $manager = $this->container->get('doctrine.orm.default_entity_manager');
        $admins = $manager->getRepository(IndividualMember::class)->findByOrganisationAndRoleName($org->getUuid(), 'ROLE_ORG_ADMIN');
        $adminUser = null;
        if (count($admins) > 0) {
            /** @var IndividualMember $admin */
            $admin = $admins[0];
            $oPerson = $admin->getPerson();
            $oPersonUuid = $oPerson->getUuid();
            /** @var \App\Entity\User\Person $person */
            $person = $manager->getRepository(\App\Entity\User\Person::class)->findOneBy(['uuid' => $oPersonUuid]);
            if (!empty($person)) {
                $adminUser = $person->getUser();
            }
        }
        return $adminUser;
    }

    public function privateMediumUrl($mediumId, $format = 'admin')
    {
        $c = $this->container;

        return $c->get('sonata.media.manager.media')->generatePrivateUrl($mediumId, $format);
    }

}
