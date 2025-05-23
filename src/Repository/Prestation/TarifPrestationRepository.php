<?php

namespace App\Repository\Prestation;

use App\Entity\Prestation\TarifPrestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifPrestation>
 *
 * @method TarifPrestation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifPrestation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifPrestation[]    findAll()
 * @method TarifPrestation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifPrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifPrestation::class);
    }

//    /**
//     * @return TarifPrestation[] Returns an array of TarifPrestation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TarifPrestation
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
