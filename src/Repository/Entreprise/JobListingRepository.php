<?php

namespace App\Repository\Entreprise;

use App\Entity\EntrepriseProfile;
use App\Entity\Entreprise\JobListing;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<JobListing>
 *
 * @method JobListing|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobListing|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobListing[]    findAll()
 * @method JobListing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobListing::class);
    }
    
    public function findAllOrderedByIdDesc()
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function findAllJobListingPublished()
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :published') 
            ->setParameter('published', JobListing::STATUS_PUBLISHED)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param EntrepriseProfile $entreprise
     * @param string $status
     * @return JobListing[]
     */
    public function findByEntrepriseAndStatus(EntrepriseProfile $entreprise, string $status): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.entreprise = :entreprise')
            ->andWhere('j.status = :status')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return JobListing[] Returns an array of JobListing objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JobListing
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
