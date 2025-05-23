<?php

namespace App\Repository\Entreprise;

use App\Entity\Entreprise\Favoris;
use App\Entity\EntrepriseProfile;
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
}
