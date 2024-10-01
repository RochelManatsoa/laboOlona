<?php

namespace App\Repository\BusinessModel;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\BusinessModel\PurchasedContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * @extends ServiceEntityRepository<PurchasedContact>
 *
 * @method PurchasedContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchasedContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchasedContact[]    findAll()
 * @method PurchasedContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchasedContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchasedContact::class);
    }

   /**
    * @return PurchasedContact[] Returns an array of PurchasedContact objects
    */
    public function findContactsByBuyerAndStatus(User $currentUser, bool $isAccepted)
    {
        $queryBuilder = $this->createQueryBuilder('pc')
            ->where('pc.buyer = :buyer')
            ->setParameter('buyer', $currentUser)
            ->orderBy('pc.id', 'DESC');

        if (!$isAccepted) {
            $queryBuilder->andWhere('pc.isAccepted IS NULL OR pc.isAccepted = :accepted')
                         ->setParameter('accepted', false);
        } else {
            $queryBuilder->andWhere('pc.isAccepted = :accepted')
                         ->setParameter('accepted', $isAccepted);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
