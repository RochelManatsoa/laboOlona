<?php

namespace App\Repository;

use App\Entity\TemplateEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TemplateEmail>
 *
 * @method TemplateEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemplateEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemplateEmail[]    findAll()
 * @method TemplateEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemplateEmail::class);
    }
    
    public function findByTypeAndCategorieAndCompte(string $type, string $categorie, string $compte): ?TemplateEmail
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.type = :type')
            ->andWhere('e.categorie = :categorie')
            ->andWhere('e.compte = :compte')
            ->setParameter('type', $type)
            ->setParameter('categorie', $categorie)
            ->setParameter('compte', $compte)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
