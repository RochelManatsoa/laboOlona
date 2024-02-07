<?php

namespace App\Manager;

use App\Entity\Referrer\Referral;
use App\Entity\User;
use Twig\Environment as Twig;
use App\Service\Mailer\MailerService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailManager
{
    public function __construct(
        private Twig $twig,
        private RequestStack $requestStack,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private ModerateurManager $moderateurManager
    ) {}

    public function welcome(User $user)
    {
        return $this->mailerService->send(
            $user->getEmail(),
            "Bienvenue sur Olona Talents",
            "welcome.html.twig",
            [
                'user' => $user,
                'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function newUser(User $user)
    {
        return $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            "Nouvel inscrit sur Olona Talents",
            "moderateur/notification_welcome.html.twig",
            [
                'user' => $user,
                'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidat_view', ['id' => $user->getCandidateProfile()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function cooptation(Referral $referral)
    {
        return $this->mailerService->send(
            $referral->getReferredEmail(),
            'Opportunité de carrière chez Olona Talants - Recommandé par '.$referral->getReferredBy()->getReferrer()->getNom().' '.$referral->getReferredBy()->getReferrer()->getNom(),
            'referrer/cooptation.html.twig',
            [
                'user' => $referral->getReferredBy()->getReferrer(),
                'annonce' => $referral->getAnnonce(),
                'url' => $this->urlGenerator->generate('app_home', ['referrer' => $referral->getReferredBy()->getCustomId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }
}