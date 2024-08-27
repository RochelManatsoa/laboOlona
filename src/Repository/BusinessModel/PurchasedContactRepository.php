<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\PurchasedContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchasedContact>
 *
 * @method PurchasedContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchasedContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchasedContact[]    findAll()
 * @method PurchasedContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchasedContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchasedContact::class);
    }

//    /**
//     * @return PurchasedContact[] Returns an array of PurchasedContact objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PurchasedContact
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
