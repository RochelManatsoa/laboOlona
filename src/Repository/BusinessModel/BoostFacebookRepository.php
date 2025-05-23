<?php

namespace App\Repository\BusinessModel;

use App\Entity\CandidateProfile;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\BusinessModel\BoostFacebook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<BoostFacebook>
 *
 * @method BoostFacebook|null find($id, $lockMode = null, $lockVersion = null)
 * @method BoostFacebook|null findOneBy(array $criteria, array $orderBy = null)
 * @method BoostFacebook[]    findAll()
 * @method BoostFacebook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoostFacebookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoostFacebook::class);
    }

    public function findBoostFacebookByCandidate(CandidateProfile $candidateProfile): ?BoostFacebook
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.candidateProfiles', 'cp')
            ->where('cp = :candidateProfile')
            ->setParameter('candidateProfile', $candidateProfile)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
