<?php

namespace App\Repository;

use App\Entity\FreeOnMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FreeOnMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FreeOnMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FreeOnMessage[]    findAll()
 * @method FreeOnMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FreeOnMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FreeOnMessage::class);
    }

    // /**
    //  * @return FreeOnMessage[] Returns an array of FreeOnMessage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FreeOnMessage
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
