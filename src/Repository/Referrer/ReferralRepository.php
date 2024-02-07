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
        // Créer un QueryBuilder
        $qb = $this->createQueryBuilder('r');

        // Sélectionner le compte et grouper par date de création
        $qb->select('COUNT(r.id) AS referralCount', 'r.createdAt AS date')
            ->where('r.referredBy = :referrer') // Correction ici: utiliser '=' au lieu de '=='
            ->setParameter('referrer', $referrerProfile) 
           ->groupBy('date')
           ->orderBy('date', 'ASC'); // Ordonner par date de manière ascendante

        // Exécuter la requête et obtenir le résultat
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    // public function getReferralsByReferrer($annonceId = null)
    // {
    //     $qb = $this->createQueryBuilder('r')
    //         ->innerJoin('r.referredBy', 'rp')
    //         ->innerJoin('r.annonce', 'jl')
    //         ->orderBy('rp.id', 'ASC')
    //         ->addOrderBy('r.createdAt', 'DESC');

    //     // Filtrer par annonce si un ID est fourni
    //     if ($annonceId !== null) {
    //         $qb->andWhere('jl.id = :annonceId')
    //         ->setParameter('annonceId', $annonceId);
    //     }

    //     $result = $qb->getQuery()->getResult();

    //     $referralsByReferrer = [];
    //     foreach ($result as $row) {
    //         $referrerId = $row->getReferredBy()->getId();
    //         if (!isset($referralsByReferrer[$referrerId])) {
    //             $referralsByReferrer[$referrerId] = [];
    //         }
    //         $referralsByReferrer[$referrerId][] = $row;
    //     }

    //     return $referralsByReferrer;
    // }

    public function getReferralsByReferrer($annonceId = null, $referrerId = null)
{
    $qb = $this->createQueryBuilder('r')
        ->select('r', 'rp', 'jl') // Assurez-vous de sélectionner les entités nécessaires
        ->innerJoin('r.referredBy', 'rp')
        ->innerJoin('r.annonce', 'jl')
        ->orderBy('rp.id', 'ASC')
        ->addOrderBy('r.createdAt', 'DESC');

    // Filtrer par annonce si un ID est fourni
    if ($annonceId !== null) {
        $qb->andWhere('jl.id = :annonceId')
           ->setParameter('annonceId', $annonceId);
    }

    // Filtrer par referrer si un ID est fourni
    if ($referrerId !== null) {
        $qb->andWhere('rp.id = :referrerId')
           ->setParameter('referrerId', $referrerId);
    }

    $result = $qb->getQuery()->getResult();

    $referralsByReferrer = [];
    foreach ($result as $row) {
        $currentReferrerId = $row->getReferredBy()->getId(); // Récupère l'ID du referrer pour le row courant
        if (!isset($referralsByReferrer[$currentReferrerId])) {
            $referralsByReferrer[$currentReferrerId] = [];
        }
        $referralsByReferrer[$currentReferrerId][] = $row;
    }

    return $referralsByReferrer;
}




//    /**
//     * @return Referral[] Returns an array of Referral objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Referral
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
