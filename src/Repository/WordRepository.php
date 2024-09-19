<?php

namespace App\Repository;

use App\Entity\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Word>
 *
 * @method Word|null find($id, $lockMode = null, $lockVersion = null)
 * @method Word|null findOneBy(array $criteria, array $orderBy = null)
 * @method Word[]    findAll()
 * @method Word[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Word::class);
    }


    /**
     * Récupère tous les mots du dictionnaire.
     */
    public function findAllWords(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.content')
            ->getQuery()
            ->getResult();
    }
}
