<?php
namespace App\Controller;

use App\Service\SsoTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SsoController extends AbstractController
{
    #[Route('/sso/verify-token', name: 'api_sso_verify_token', methods: ['GET'])]
    public function verifyToken(Request $request, SsoTokenService $service): JsonResponse
    {
        $token = $request->query->get('token');
        if (!$token) {
            return $this->json(['error' => 'Token missing'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $service->consumeToken($token);
        if (!$user) {
            return $this->json(['error' => 'Invalid token'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
