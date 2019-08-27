<?php

namespace App\Repository;

use App\Entity\MessageOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MessageOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageOption[]    findAll()
 * @method MessageOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageOptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MessageOption::class);
    }

    // /**
    //  * @return MessageOption[] Returns an array of MessageOption objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MessageOption
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
