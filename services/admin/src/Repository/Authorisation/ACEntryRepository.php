<?php

namespace App\Repository\Authorisation;

use App\Entity\Authorisation\ACEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ACEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ACEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ACEntry[]    findAll()
 * @method ACEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ACEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ACEntry::class);
    }

    // /**
    //  * @return ACEntry[] Returns an array of ACEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ACEntry
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
