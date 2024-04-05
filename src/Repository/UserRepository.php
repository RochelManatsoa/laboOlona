<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    
    public function findUsersRegisteredToday(): array
    {
        $qb = $this->createQueryBuilder('u');

        // Détermine la date de début (aujourd'hui à 00h00)
        // $startDate = new \DateTime('today midnight');
        
        // Détermine la date de fin (maintenant)
        $endDate = new \DateTime('now');
        $startDate = clone $endDate;
        $startDate->modify('-28 days');

        $qb->where('u.dateInscription >= :startDate')
           ->andWhere('u.dateInscription <= :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    public function countUsersByType(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.type AS userType', 'COUNT(u.id) AS userCount')
            ->groupBy('u.type');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countUsersRegisteredTodayByType(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.type AS userType', 'COUNT(u.id) AS userCount')
            ->where('u.dateInscription >= :startOfDay')
            ->setParameter('startOfDay', new \DateTime('today'))
            ->groupBy('u.type');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countUsersRegisteredToday(): int
    {
        // Définir la date de début à aujourd'hui à 00h00
        $startOfDay = new \DateTime('today');
        
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id) AS totalUsers')
            ->where('u.dateInscription >= :startOfDay')
            ->setParameter('startOfDay', $startOfDay);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    public function countAllUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
