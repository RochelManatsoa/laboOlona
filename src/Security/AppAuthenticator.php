<?php

namespace App\Security;

use App\Service\ActivityLogger;
use App\Entity\Logs\ActivityLog;
use App\Service\SsoTokenService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Service\UserPostAuthenticationService;
use Symfony\Component\HttpFoundation\Response;
use App\WhiteLabel\Entity\Client1\User as Client1User;
use App\WhiteLabel\Entity\Client1\CandidateProfile as Client1CandidateProfile;
use App\WhiteLabel\Entity\Client1\Candidate\Competences as Client1Competences;
use App\WhiteLabel\Entity\Client1\Candidate\Experiences as Client1Experiences;
use App\WhiteLabel\Entity\Client1\Candidate\CV as Client1CV;
use App\WhiteLabel\Entity\Client1\Candidate\Langages as Client1Langages;
use App\WhiteLabel\Entity\Client1\Candidate\Social as Client1Social;
use App\WhiteLabel\Entity\Client1\Candidate\TarifCandidat as Client1TarifCandidat;
use Symfony\Component\Uid\Uuid;
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
        private array $client1Hosts,
        private SsoTokenService $ssoTokenService,
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
            $clientUser = $emClient1->getRepository(Client1User::class)->findOneBy(['email' => $mainUser->getEmail()]);
            if (!$clientUser) {
                $clientUser = new Client1User();
                $clientUser->setEmail($mainUser->getEmail());
                $clientUser->setRoles($mainUser->getRoles());
                $clientUser->setPassword($mainUser->getPassword());
                $clientUser->setNom($mainUser->getNom());
                $clientUser->setPrenom($mainUser->getPrenom());
                $clientUser->setDateInscription(new \DateTime());
                $emClient1->persist($clientUser);
                $emClient1->flush();
            }
            $this->syncClient1UserData($mainUser, $clientUser, $emClient1);

            $tokenEntity = $this->ssoTokenService->createToken($mainUser);
            $url = $this->urlGenerator->generate('app_client1_consume_token', [
                'token' => $tokenEntity->getToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $host = in_array($request->getHost(), $this->client1Hosts, true) ? $request->getHost() : $this->client1Hosts[1];
            $url = preg_replace('#://[^/]+#', '://'.$host, $url);
            return new RedirectResponse($url);
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

    private function syncClient1UserData(\App\Entity\User $mainUser, Client1User $clientUser, \Doctrine\ORM\EntityManagerInterface $em): void
    {
        $profile = $mainUser->getCandidateProfile();
        if (!$profile) {
            return;
        }

        $clientProfile = $clientUser->getCandidateProfile();
        if (!$clientProfile) {
            $clientProfile = new Client1CandidateProfile();
            $clientProfile->setCandidat($clientUser);
        }

        $clientProfile
            ->setResume($profile->getResume())
            ->setTitre($profile->getTitre())
            ->setProvince($profile->getProvince())
            ->setRegion($profile->getRegion())
            ->setGender($profile->getGender())
            ->setBirthday($profile->getBirthday())
            ->setLocalisation($profile->getLocalisation())
            ->setUid($profile->getUid());

        foreach ($profile->getCompetences() as $comp) {
            $c = $em->getRepository(Client1Competences::class)->findOneBy(['slug' => $comp->getSlug()]);
            if (!$c) {
                $c = new Client1Competences();
                $c->setNom($comp->getNom());
                $c->setSlug($comp->getSlug());
                $c->setDescription($comp->getDescription());
                $em->persist($c);
            }
            $clientProfile->addCompetence($c);
        }

        foreach ($profile->getExperiences() as $exp) {
            $e = new Client1Experiences();
            $e->setNom($exp->getNom())
              ->setDescription($exp->getDescription())
              ->setEntreprise($exp->getEntreprise())
              ->setEnPoste($exp->isEnPoste())
              ->setDateDebut($exp->getDateDebut())
              ->setDateFin($exp->getDateFin())
              ->setProfil($clientProfile);
            $em->persist($e);
        }

        foreach ($profile->getCvs() as $cv) {
            $c = new Client1CV();
            $c->setCvLink($cv->getCvLink())
              ->setUploadedAt($cv->getUploadedAt())
              ->setSafeFileName($cv->getSafeFileName())
              ->setCandidat($clientProfile);
            $em->persist($c);
        }

        foreach ($profile->getLangages() as $lang) {
            $l = new Client1Langages();
            $l->setTitre($lang->getTitre())
              ->setNiveau($lang->getNiveau())
              ->setCode($lang->getCode())
              ->setProfile($clientProfile);
            $em->persist($l);
        }

        if ($profile->getSocial()) {
            $src = $profile->getSocial();
            $social = $clientProfile->getSocial();
            if (!$social) {
                $social = new Client1Social();
                $social->setCandidat($clientProfile);
            }
            $social
                ->setLinkedin($src->getLinkedin())
                ->setSkype($src->getSkype())
                ->setSlack($src->getSlack())
                ->setFacebook($src->getFacebook())
                ->setInstagram($src->getInstagram())
                ->setGithub($src->getGithub());
            $clientProfile->setSocial($social);
            $em->persist($social);
        }

        if ($profile->getTarifCandidat()) {
            $srcTarif = $profile->getTarifCandidat();
            $tarif = $clientProfile->getTarifCandidat();
            if (!$tarif) {
                $tarif = new Client1TarifCandidat();
                $tarif->setCandidat($clientProfile);
                $tarif->setCreatedAt(new \DateTime());
            }
            $tarif
                ->setMontant($srcTarif->getMontant())
                ->setDevise($srcTarif->getDevise())
                ->setTypeTarif($srcTarif->getTypeTarif());
            $clientProfile->setTarifCandidat($tarif);
            $em->persist($tarif);
        }

        $em->persist($clientProfile);
        $em->flush();
    }
}
