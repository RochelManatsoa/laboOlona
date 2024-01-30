<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Notification;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @param User $user
     * @param array $orderBy
     * @param string|null $statusNot
     * @return Notification[]
     */
    public function findByDestinataireAndStatusNot(User $user, array $orderBy, string $statusNot, ?int $isRead)
    {
        $qb = $this->createQueryBuilder('n')
                ->where('n.destinataire = :destinataire')
                ->setParameter('destinataire', $user);

        if ($statusNot !== null) {
            $qb->andWhere('n.status != :statusNot')
            ->setParameter('statusNot', $statusNot);
        }

        if ($isRead !== null) {
            $qb->andWhere('n.isRead = :isRead')
            ->setParameter('isRead', $isRead);
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('n.' . $field, $direction);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Notification[] Returns an array of Notification objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Notification
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
