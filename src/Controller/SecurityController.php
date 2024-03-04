<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\YouTubeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private YouTubeService $youTubeService
    )
    {}

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        $this->youTubeService->logout();
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/connect', name: 'app_connect')]
    public function connect(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_profile');
        }
        if ($user->getType() === User::ACCOUNT_MODERATEUR) {
            return $this->redirectToRoute('app_dashboard_moderateur');
        }
    
        if ($user->getType() === User::ACCOUNT_ENTREPRISE) {
            return $this->redirectToRoute('app_dashboard_entreprise');
        }
    
        if ($user->getType() === User::ACCOUNT_CANDIDAT) {
            return $this->redirectToRoute('app_dashboard_candidat');
        }

        if ($user->getType() === User::ACCOUNT_REFERRER) {
            return $this->redirectToRoute('app_dashboard_referrer');
        }

        if ($user->getType() === User::ACCOUNT_EMPLOYE) {
            return $this->redirectToRoute('app_dashboard_employes_simulations');
        }
    
        return $this->redirectToRoute('app_profile');
    }
    
    #[Route(path: '/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google_main') 
            ->redirect();
    }

    #[Route(path: '/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        if(!$this->getUser()){
            return new JsonResponse([
                'status' => false,
                'message' => "User not found"
            ]);
        }else{
            return $this->redirectToRoute('app_connect');
        }
    }
}
