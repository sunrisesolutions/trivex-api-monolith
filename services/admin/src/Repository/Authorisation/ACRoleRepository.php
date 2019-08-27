<?php

namespace App\Repository\Authorisation;

use App\Entity\Authorisation\ACRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ACRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method ACRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method ACRole[]    findAll()
 * @method ACRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ACRoleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ACRole::class);
    }

    // /**
    //  * @return ACRole[] Returns an array of ACRole objects
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
    public function findOneBySomeField($value): ?ACRole
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
