<?php

namespace App\Repository\Organisation;

use App\Entity\Organisation\IndividualMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method IndividualMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method IndividualMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method IndividualMember[]    findAll()
 * @method IndividualMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndividualMemberRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, IndividualMember::class);
    }

    /**
     * @return IndividualMember[] Returns an array of IndividualMember objects
     */
    public function findByOrganisationAndRoleName($orgUuid, $role)
    {
        return $this->createQueryBuilder('i')
            ->join('i.roles', 'role')
            ->join('i.organisation','organisation')
            ->andWhere('role.name LIKE :val')
            ->andWhere('organisation.uuid LIKE :orgUuid')
            ->setParameter('val', $role)
            ->setParameter('orgUuid', $orgUuid)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
    }


    /*
    public function findOneBySomeField($value): ?IndividualMember
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
