<?php

namespace App\Repository\Organisation;

use App\Entity\Organisation\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Connection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Connection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Connection[]    findAll()
 * @method Connection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Connection::class);
    }

    /**
     * @return Connection[] Returns an array of Connection objects
     */
    public function findByFriendMemberUuid($myMemberUuid, $friendMemberUuid)
    {
        $qb = $this->createQueryBuilder('c');
        $expr = $qb->expr();
        $qb
            ->join('c.fromMember', 'fromMember')
            ->join('c.toMember', 'toMember');
        $qb->andWhere(
            $expr->orX(
                $expr->andX(
                    $expr->like('fromMember.uuid', $expr->literal($friendMemberUuid)),
                    $expr->like('toMember.uuid', $expr->literal($myMemberUuid))
                ),
                $expr->andX(
                    $expr->like('fromMember.uuid', $expr->literal($myMemberUuid)),
                    $expr->like('toMember.uuid', $expr->literal($friendMemberUuid))
                ),
            )
        );
        return $qb->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }


    // /**
    //  * @return Connection[] Returns an array of Connection objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Connection
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
