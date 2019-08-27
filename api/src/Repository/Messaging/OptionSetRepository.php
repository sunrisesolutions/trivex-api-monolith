<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\OptionSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method OptionSet|null find($id, $lockMode = null, $lockVersion = null)
 * @method OptionSet|null findOneBy(array $criteria, array $orderBy = null)
 * @method OptionSet[]    findAll()
 * @method OptionSet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptionSetRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OptionSet::class);
    }

    // /**
    //  * @return OptionSet[] Returns an array of OptionSet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OptionSet
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
