<?php
namespace App\Repository;

use App\Entity\SsoToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SsoToken>
 */
class SsoTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SsoToken::class);
    }

    public function save(SsoToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SsoToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function consumeValidToken(string $token): ?SsoToken
    {
        /** @var SsoToken|null $ssoToken */
        $ssoToken = $this->find($token);
        if (!$ssoToken || $ssoToken->isExpired() || $ssoToken->isConsumed()) {
            return null;
        }
        $ssoToken->consume();
        $this->getEntityManager()->flush();
        return $ssoToken;
    }
}
