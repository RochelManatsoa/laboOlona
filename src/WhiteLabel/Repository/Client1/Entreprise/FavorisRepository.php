<?php

namespace App\WhiteLabel\Repository\Client1\Entreprise;

use App\WhiteLabel\Entity\Client1\Entreprise\Favoris;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Favoris>
 *
 * @method Favoris|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favoris|null findOneBy(array $criteria, array $orderBy = null)
 * @method Favoris[]    findAll()
 * @method Favoris[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FavorisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Favoris::class);
    }

    public function paginateFavoris(EntrepriseProfile $entreprise, int $page = 1): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('f')->select('f, c.titre as titre, u.lastLogin as lastLogin')
            ->leftJoin('f.candidat', 'c')
            ->leftJoin('c.candidat', 'u')
            ->andWhere('f.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->addOrderBy('f.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }

    public function countByAnnonce(JobListing $annonce): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.annonce = :annonce')
            ->setParameter('annonce', $annonce)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $annonceIds
     * @return array<int,int> id => count
     */
    public function countByAnnonces(array $annonceIds): array
    {
        if (empty($annonceIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.annonce) AS annonceId, COUNT(f.id) AS favorisCount')
            ->andWhere('f.annonce IN (:annonces)')
            ->setParameter('annonces', $annonceIds)
            ->groupBy('f.annonce')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['annonceId']] = (int) $row['favorisCount'];
        }

        return $counts;
    }

    /**
     * @return Favoris[]
     */
    public function findByAnnonce(JobListing $annonce): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.candidat', 'c')
            ->addSelect('c')
            ->andWhere('f.annonce = :annonce')
            ->setParameter('annonce', $annonce)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
