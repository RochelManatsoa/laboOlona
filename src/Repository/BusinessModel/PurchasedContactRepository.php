<?php

namespace App\Repository\BusinessModel;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Boolean;
use App\Entity\BusinessModel\PurchasedContact;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
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

    public function paginateContactsByBuyer(User $currentUser, int $page = 1): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('pc')
            ->where('pc.buyer = :buyer')
            ->setParameter('buyer', $currentUser)
            ->orderBy('pc.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }
    
    public function countContacts(User $user): int
    {
        return (int) $this->createQueryBuilder('pc')
            ->select('COUNT(pc.id)')
            ->where('pc.buyer = :buyer')
            ->setParameter('buyer', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
