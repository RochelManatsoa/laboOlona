<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Boost;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\Prestation;
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
    
    public function findBoostVisibilityByBoostFacebookAndUser(BoostFacebook $boostFacebook, User $user, string $type): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'u') 
            ->innerJoin('bv.boostFacebook', 'bf')
            ->innerJoin('bv.user', 'u')
            ->where('bf = :boostFacebook')
            ->andWhere('u = :user')
            ->andWhere('bv.type = :type')
            ->setParameter('boostFacebook', $boostFacebook)
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByBoostAndUser(Boost $boost, User $user, string $type): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'u') 
            ->innerJoin('bv.boost', 'bf')
            ->innerJoin('bv.user', 'u')
            ->where('bf = :boost')
            ->andWhere('u = :user')
            ->andWhere('bv.type = :type')
            ->setParameter('boost', $boost)
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByBoostAndPrestation(Boost $boost, Prestation $prestation): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'p') 
            ->innerJoin('bv.boost', 'bf')
            ->innerJoin('bv.prestation', 'p')
            ->where('bf = :boost')
            ->andWhere('p = :prestation')
            ->setParameter('boost', $boost)
            ->setParameter('prestation', $prestation)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByBoostFacebookAndPrestation(BoostFacebook $boost, Prestation $prestation): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'p') 
            ->innerJoin('bv.boostFacebook', 'bf')
            ->innerJoin('bv.prestation', 'p')
            ->where('bf = :boost')
            ->andWhere('p = :prestation')
            ->setParameter('boost', $boost)
            ->setParameter('prestation', $prestation)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityByPrestationAndUser(Prestation $prestation, User $user, Boost $boost): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('b', 'p') 
            ->innerJoin('bv.prestation', 'p')
            ->innerJoin('bv.user', 'u')
            ->innerJoin('bv.boost', 'b')
            ->where('u = :user')
            ->andWhere('p = :prestation')
            ->andWhere('b = :boost')
            ->setParameter('user', $user)
            ->setParameter('prestation', $prestation)
            ->setParameter('boost', $boost)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findBoostVisibilityFBByPrestationAndUser(Prestation $prestation, User $user, BoostFacebook $boostFacebook): ?BoostVisibility
    {
        return $this->createQueryBuilder('bv')
            ->addSelect('bf', 'p') 
            ->innerJoin('bv.prestation', 'p')
            ->innerJoin('bv.user', 'u')
            ->innerJoin('bv.boostFacebook', 'bf')
            ->where('u = :user')
            ->andWhere('p = :prestation')
            ->andWhere('bf = :boostFacebook')
            ->setParameter('user', $user)
            ->setParameter('prestation', $prestation)
            ->setParameter('boostFacebook', $boostFacebook)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
