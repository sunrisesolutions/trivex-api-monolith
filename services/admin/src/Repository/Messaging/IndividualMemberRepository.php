<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\IndividualMember;
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

    // /**
    //  * @return IndividualMember[] Returns an array of IndividualMember objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

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
