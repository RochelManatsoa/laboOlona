<?php
namespace App\Entity;

use App\Repository\SsoTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SsoTokenRepository::class)]
class SsoToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    public function __construct(User $user, int $ttlSeconds = 300)
    {
        $this->token = Uuid::v4()->toRfc4122();
        $this->user = $user;
        $this->expiresAt = new \DateTimeImmutable('+' . $ttlSeconds . ' seconds');
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt !== null;
    }

    public function consume(): void
    {
        $this->consumedAt = new \DateTimeImmutable();
    }
}
