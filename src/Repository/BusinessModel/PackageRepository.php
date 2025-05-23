<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 *
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function findContractPackages()
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->setParameter('type', 'CONTRAT');
    }

    public function findAbonnementPackages()
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->setParameter('type', 'ABONNEMENT');
    }

//    public function findOneBySomeField($value): ?Package
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
