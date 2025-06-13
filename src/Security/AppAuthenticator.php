<?php

namespace App\Security;

use App\Service\ActivityLogger;
use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\WhiteLabel\Entity\Client1\User as Client1User;
use App\Service\UserPostAuthenticationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator, 
        private UserPostAuthenticationService $userPostAuthenticationService,
        private ActivityLogger $activityLogger,
        private UserService $userService,
        private ManagerRegistry $registry,
    ){}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $routeName = $request->attributes->get('_route');
        $this->userPostAuthenticationService->updateLastLoginDate($token->getUser());
        $this->activityLogger->logActivity($this->userService->getCurrentUser(), ActivityLog::ACTIVITY_LOGIN, 'Connexion à Olona Talents', ActivityLog::LEVEL_INFO);
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $wl = $request->request->get('wl_redirect') ?: $request->query->get('wl_redirect');
        if ($wl === 'client1') {
            $mainUser = $token->getUser();
            $emClient1 = $this->registry->getManager('client1');
            $existing = $emClient1->getRepository(Client1User::class)->findOneBy(['email' => $mainUser->getEmail()]);
            if (!$existing) {
                $new = new Client1User();
                $new->setEmail($mainUser->getEmail());
                $new->setRoles($mainUser->getRoles());
                $new->setPassword($mainUser->getPassword());
                $new->setNom($mainUser->getNom());
                $new->setPrenom($mainUser->getPrenom());
                $new->setDateInscription(new \DateTime());
                $emClient1->persist($new);
                $emClient1->flush();
            }

            return new RedirectResponse($this->urlGenerator->generate('app_white_label_home'));
        }
        if ($routeName === 'coworking_login') {
            return new RedirectResponse($this->urlGenerator->generate('app_coworking_main'));
        }
        return new RedirectResponse($this->urlGenerator->generate('app_v2_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
