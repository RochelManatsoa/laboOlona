<?php

namespace App\Repository\Moderateur;

use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\Assignation;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Assignation>
 *
 * @method Assignation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignation[]    findAll()
 * @method Assignation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignation::class);
    }


    /**
     * @param EntrepriseProfile $entreprise
     * @return Assignation[]
     */
    public function findAssignByEntreprise(EntrepriseProfile $entreprise): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.jobListing', 'jl')
            ->innerJoin('jl.entreprise', 'e')
            ->where('e.id = :entrepriseId')
            ->setParameter('entrepriseId', $entreprise->getId())
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Assignation[] Returns an array of Assignation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Assignation
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
