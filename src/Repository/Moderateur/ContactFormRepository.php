<?php

namespace App\Repository\Moderateur;

use App\Entity\Moderateur\ContactForm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactForm>
 *
 * @method ContactForm|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContactForm|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContactForm[]    findAll()
 * @method ContactForm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactFormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactForm::class);
    }

    public function findLatestReservationByUniqueEmail()
    {
        $subQuery = $this->createQueryBuilder('sub')
            ->select('MAX(sub.id)')
            ->groupBy('sub.email');

        return $this->createQueryBuilder('c')
            ->select('c') 
            ->where('c.id IN (' . $subQuery->getDQL() . ')')
            ->getQuery()
            ->getResult();
    }
}
