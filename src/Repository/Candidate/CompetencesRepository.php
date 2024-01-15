<?php

namespace App\Repository\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Candidate\Competences;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Competences>
 *
 * @method Competences|null find($id, $lockMode = null, $lockVersion = null)
 * @method Competences|null findOneBy(array $criteria, array $orderBy = null)
 * @method Competences[]    findAll()
 * @method Competences[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompetencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competences::class);
    }

   /**
     * Trouve des compétences par secteurs.
     *
     * @param array $secteurs Un tableau de secteurs
     * @return Competences[] Une liste de compétences associées aux candidats de ces secteurs
     */
    public function findCompetencesBySecteurs($secteurs)
    {
        $qb = $this->createQueryBuilder('comp')
            ->select('DISTINCT comp.nom, comp.id')
            ->join('comp.profil', 'profile')
            ->join('profile.secteurs', 'sect')
            ->andWhere('sect.id IN (:secteurs)')
            ->setParameter('secteurs', $secteurs);

        return $qb->getQuery()->getResult();
    }

//    public function findOneBySomeField($value): ?Competences
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
