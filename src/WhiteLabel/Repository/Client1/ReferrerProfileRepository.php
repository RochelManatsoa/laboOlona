<?php

namespace App\WhiteLabel\Repository\Client1;

use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\WhiteLabel\Entity\Client1\ReferrerProfile;
use App\WhiteLabel\Entity\Client1\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ReferrerProfile>
 *
 * @method ReferrerProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReferrerProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReferrerProfile[]    findAll()
 * @method ReferrerProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferrerProfileRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry, 
        private PaginatorInterface $paginator,
    )
    {
        parent::__construct($registry, ReferrerProfile::class);
    }

    public function paginateRecipes($page, PaginatorInterface $paginator, ?int $userId, ?int $affiliateId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('r')->select('r, u.nom  AS nom, COUNT(ref.id) AS refCount')
        ->leftJoin('r.referrer', 'u')
        ->leftJoin('r.referrals', 'ref')
        ->groupBy('r.id')
        ->addOrderBy('r.id', 'DESC');
        if ($affiliateId) {
            $queryBuilder->andWhere('r.referrer = :affiliateId')
                ->setParameter('affiliateId', $affiliateId);
        };

        return $paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }
}
