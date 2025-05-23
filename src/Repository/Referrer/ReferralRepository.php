<?php

namespace App\Repository\Referrer;

use App\Entity\Referrer\Referral;
use App\Entity\ReferrerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Referral>
 *
 * @method Referral|null find($id, $lockMode = null, $lockVersion = null)
 * @method Referral|null findOneBy(array $criteria, array $orderBy = null)
 * @method Referral[]    findAll()
 * @method Referral[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferralRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Referral::class);
    }

    public function countReferralsByDate(ReferrerProfile $referrerProfile)
    {
        $qb = $this->createQueryBuilder('r');

        // Sélectionne le compte et grouper par date de création
        $qb->select('COUNT(r.id) AS referralCount', 'r.createdAt AS date')
            ->where('r.referredBy = :referrer') 
            ->setParameter('referrer', $referrerProfile) 
           ->groupBy('date')
           ->orderBy('date', 'ASC'); 

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function countReferralsByDays(ReferrerProfile $referrerProfile)
    {
        $qb = $this->createQueryBuilder('r');

        // Calcule la date de début (il y a 28 jours)
        $startDate = new \DateTime('-28 days');
        $startDate->setTime(0, 0); 

        // Date de fin (aujourd'hui)
        $endDate = new \DateTime('now');
        $endDate->setTime(23, 59, 59); // Optionnel: définir l'heure à 23:59:59 pour inclure toute la journée

        // Sélectionne le compte et groupe par date de création
        $qb->select('COUNT(r.id) AS referralCount', 'r.createdAt AS date')
        ->where('r.referredBy = :referrer')
        ->andWhere('r.createdAt >= :startDate') 
        ->andWhere('r.createdAt <= :endDate')   
        ->setParameter('referrer', $referrerProfile)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->groupBy('r.createdAt') 
        ->orderBy('r.createdAt', 'ASC'); 

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function getReferralsByReferrer($annonceId = null, $referrerId = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r', 'rp', 'jl') 
            ->innerJoin('r.referredBy', 'rp')
            ->innerJoin('r.annonce', 'jl')
            ->orderBy('rp.id', 'ASC')
            ->addOrderBy('r.createdAt', 'DESC');

        // Filtre par annonce si un ID est fourni
        if ($annonceId !== null) {
            $qb->andWhere('jl.id = :annonceId')
            ->setParameter('annonceId', $annonceId);
        }

        // Filtre par referrer si un ID est fourni
        if ($referrerId !== null) {
            $qb->andWhere('rp.id = :referrerId')
            ->setParameter('referrerId', $referrerId);
        }

        $result = $qb->getQuery()->getResult();

        $referralsByReferrer = [];
        foreach ($result as $row) {
            $currentReferrerId = $row->getReferredBy()->getId(); 
            if (!isset($referralsByReferrer[$currentReferrerId])) {
                $referralsByReferrer[$currentReferrerId] = [];
            }
            $referralsByReferrer[$currentReferrerId][] = $row;
        }

        return $referralsByReferrer;
    }


}
