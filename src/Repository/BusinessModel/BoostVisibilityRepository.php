<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Boost;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<BoostVisibility>
 *
 * @method BoostVisibility|null find($id, $lockMode = null, $lockVersion = null)
 * @method BoostVisibility|null findOneBy(array $criteria, array $orderBy = null)
 * @method BoostVisibility[]    findAll()
 * @method BoostVisibility[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoostVisibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoostVisibility::class);
    }

    public function findLatestBoostVisibilityByBoost(Boost $boost)
    {
        return $this->createQueryBuilder('bv')
            ->andWhere('bv.boost = :boost')
            ->setParameter('boost', $boost)
            ->orderBy('bv.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByBoostFacebookAndCandidate(BoostFacebook $boostFacebook, User $user): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'u') 
            ->innerJoin('bv.boostFacebook', 'bf')
            ->innerJoin('bv.user', 'u')
            ->where('bf = :boostFacebook')
            ->andWhere('u = :user')
            ->setParameter('boostFacebook', $boostFacebook)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByBoostAndUser(Boost $boost, User $user): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'u') 
            ->innerJoin('bv.boost', 'bf')
            ->innerJoin('bv.user', 'u')
            ->where('bf = :boost')
            ->andWhere('u = :user')
            ->setParameter('boost', $boost)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
