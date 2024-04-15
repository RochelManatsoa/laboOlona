<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Employe;
use App\Entity\Finance\Simulateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Simulateur>
 *
 * @method Simulateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Simulateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Simulateur[]    findAll()
 * @method Simulateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SimulateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Simulateur::class);
    }

    public function findSimulateursNotDeletedForEmploye(Employe $employe)
    {
        $qb = $this->createQueryBuilder('s'); 
        $qb->where('s.employe = :employe')
        ->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('s.status'),
            $qb->expr()->eq('s.status', ':statusValid')
        ))
        ->setParameter('employe', $employe)
        ->setParameter('statusValid', Simulateur::STATUS_VALID)
        ->orderBy('s.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findSimulationsWithEmploye()
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.employe IS NOT NULL');

        return $qb->getQuery()->getResult();
    }


//    /**
//     * @return Simulateur[] Returns an array of Simulateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Simulateur
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
