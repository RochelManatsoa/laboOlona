<?php
namespace App\Service;

use App\Entity\SsoToken;
use App\Entity\User;
use App\Repository\SsoTokenRepository;

class SsoTokenService
{
    public function __construct(private SsoTokenRepository $repository)
    {
    }

    public function createToken(User $user, int $ttlSeconds = 300): SsoToken
    {
        $token = new SsoToken($user, $ttlSeconds);
        $this->repository->save($token, true);
        return $token;
    }

    public function consumeToken(string $token): ?User
    {
        $ssoToken = $this->repository->consumeValidToken($token);
        return $ssoToken?->getUser();
    }
}
