<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Security\Client1\Client1Authenticator;

class SecurityController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    #[Route(path: '/connexion', name: 'app_client1_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_white_label_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('white_label/client1/security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/deconnection', name: 'app_client1_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/consume-token', name: 'app_client1_consume_token')]
    public function consumeToken(
        Request $request,
        HttpClientInterface $httpClient,
        UserAuthenticatorInterface $userAuthenticator,
        Client1Authenticator $authenticator,
    ): Response {
        $token = $request->query->get('token');
        $url = "https://dev.olona-talents.com/api/sso/verify-token";
        if (!$token) {
            return $this->redirectToRoute('app_client1_login');
        }

        $response = $httpClient->request('GET', $url, [
            'query' => ['token' => $token],
            'timeout' => 5.0,
            'verify_peer' => false,
        ]);

        if (200 !== $response->getStatusCode()) {
            return $this->redirectToRoute('app_client1_login');
        }

        $data = $response->toArray();
        $user = $this->entityManager->getRepository(\App\WhiteLabel\Entity\Client1\User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $data['email']]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_client1_login');
        }

        return $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
    }
}
