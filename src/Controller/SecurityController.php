<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ActivityLogger;
use App\Service\YouTubeService;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private YouTubeService $youTubeService,
        private ActivityLogger $activityLogger,
        private UserService $userService,
        private RequestStack $requestStack,
    ){}

    #[Route(path: '/login', name: 'app_login', options: ['sitemap' => true])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $this->requestStack->getSession()->set('fromPath', 'app_home');
        if ($this->getUser()) {
            return $this->redirectToRoute('app_connect');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/coworking/login', name: 'app_coworking_login', options: ['sitemap' => true])]
    public function loginCoworking(AuthenticationUtils $authenticationUtils): Response
    {
        $this->requestStack->getSession()->set('fromPath', 'app_coworking_main');
        if ($this->getUser()) {
            return $this->redirectToRoute('app_coworking_main');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login_coworking.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/coworking/authenticate', name: 'app_coworking_authennticate', methods: ['POST'])]
    public function authenticateCoworking(
        Request $request,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
        UserService $userService,
    ): Response
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $response = [
                'status' => 'error',
                'id' => 'error',
                'message' => 'Aucun utilisateur trouvé avec cet e-mail.',
            ];
    
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('turbo_stream/error_message.html.twig', $response);
            }
    
            return $this->json($response, Response::HTTP_NOT_FOUND);
        }

        if(!$userService->checkUserPassword($user, $password)){
            $response = [
                'status' => 'error',
                'id' => 'error',
                'message' => 'Mot de passe incorrect.',
            ];
    
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('turbo_stream/error_message.html.twig', $response);
            }
    
            return $this->json($response, Response::HTTP_NOT_FOUND);
        }
    
        $response = $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
        
        return $this->redirectToRoute('app_coworking_main');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        $this->youTubeService->logout();
        
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/connect', name: 'app_connect')]
    public function connect(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $from = $request->get('fromPath', null);
        if($from){
            return $this->redirectToRoute($from);
        }
        if( $this->requestStack->getSession()->get('fromPath') === 'app_coworking_main'){
            return $this->redirectToRoute('app_coworking_main');
        }
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_profile');
        }
        if ($user->getType() === User::ACCOUNT_MODERATEUR) {
            return $this->redirectToRoute('app_dashboard_moderateur');
        }
    
        if ($user->getType() === User::ACCOUNT_ENTREPRISE) {
            if ($user->getEntrepriseProfile() instanceof EntrepriseProfile && $user->getEntrepriseProfile()->getStatus() === EntrepriseProfile::STATUS_BANNED) {
                return $this->redirectToRoute('app_logout');
            }
            if ($user->getEntrepriseProfile() instanceof EntrepriseProfile && $user->getEntrepriseProfile()->getStatus() === EntrepriseProfile::STATUS_VALID) {
                return $this->redirectToRoute('app_v2_profiles');
            }
            return $this->redirectToRoute('app_v2_recruiter_dashboard');
        }
    
        if ($user->getType() === User::ACCOUNT_CANDIDAT) {
            if ($user->getCandidateProfile() instanceof CandidateProfile && $user->getCandidateProfile()->getStatus() === CandidateProfile::STATUS_BANNISHED) {
                return $this->redirectToRoute('app_logout');
            }
            if ($user->getCandidateProfile() instanceof CandidateProfile && $user->getCandidateProfile()->getStatus() === CandidateProfile::STATUS_VALID ) {
                return $this->redirectToRoute('app_v2_job_offer');
            }
            return $this->redirectToRoute('app_v2_candidate_dashboard');
        }

        if ($user->getType() === User::ACCOUNT_REFERRER) {
            return $this->redirectToRoute('app_v2_dashboard');
        }

        if ($user->getType() === User::ACCOUNT_EMPLOYE) {
            return $this->redirectToRoute('app_v2_dashboard');
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
            return $this->json([
                'status' => false,
                'message' => "User not found"
            ], Response::HTTP_FORBIDDEN);
        }else{
            return $this->redirectToRoute('app_connect');
        }
    }
    
    #[Route(path: '/connect/facebook', name: 'connect_facebook_start')]
    public function connectFacebookAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('facebook_main') 
            ->redirect([
                'public_profile', 'email' 
            ]);
    }

    #[Route(path: '/auth/facebook/callback', name: 'connect_facebook_check')]
    public function connectFacebookCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        if(!$this->getUser()){
            return $this->json([
                'status' => false,
                'message' => "User not found"
            ], Response::HTTP_FORBIDDEN);
        }else{
            return $this->redirectToRoute('app_connect');
        }
    }

    #[Route(path: '/store/target/path', name: 'store_target_path')]
    public function storeTargetPath(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $href = $data['href'] ?? null;
    
        if ($href) {
            $session = $this->requestStack->getSession();
            $providerKey = 'main'; 
            $session->set('_security.'.$providerKey.'.target_path', $href);
            return $this->json(['status' => 'success', 'message' => 'Href stored in session'], Response::HTTP_ACCEPTED);
        }
    
        return $this->json(['status' => 'error', 'message' => 'No href provided'], Response::HTTP_BAD_REQUEST);
    }
}
