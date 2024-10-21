<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Prestation;
use Twig\Environment as Twig;
use App\Entity\Finance\Contrat;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Manager\Finance\EmployeManager;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailManager
{
    public function __construct(
        private Twig $twig,
        private RequestStack $requestStack,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private EmployeManager $employeManager,
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
                'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_profile_candidat_view', ['id' => $user->getCandidateProfile()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function cooptation(Referral $referral)
    {
        return $this->mailerService->send(
            $referral->getReferredEmail(),
            'Opportunité de carrière chez Olona Talents - Recommandé par '.$referral->getReferredBy()->getReferrer()->getNom().' '.$referral->getReferredBy()->getReferrer()->getPrenom(),
            'referrer/cooptation.html.twig',
            [
                'user' => $referral->getReferredBy()->getReferrer(),
                'annonce' => $referral->getAnnonce(),
                'url' => $this->urlGenerator->generate('app_invitation_referral', ['referralCode' => $referral->getReferralCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function newPortage(User $user, Contrat $contrat)
    {
        return $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            'Portage Salariale : '.$user->getNom().' '.$user->getPrenom().' souhaite en savoir plus',
            "moderateur/notification_portage.html.twig",
            [
                'user' => $user,
                'simulateur' => $contrat->getSimulateur(),
                'details' => $this->employeManager->simulate($contrat->getSimulateur()),
                'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_view_portage', ['id' => $contrat->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function facebookBoostProfile(User $user, BoostVisibility $boost)
    {
        $url = '';
        if($user->getCandidateProfile() instanceof CandidateProfile){
            $url = $this->urlGenerator->generate('app_dashboard_moderateur_profile_candidat_view', [
                'id' => $user->getCandidateProfile()->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        if($user->getEntrepriseProfile() instanceof EntrepriseProfile){
            $url = $this->urlGenerator->generate('app_dashboard_moderateur_profile_entreprise_view', [
                'id' => $user->getEntrepriseProfile()->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        return $this->mailerService->send(
            'jrandriamalala.olona@gmail.com',
            'Notification de Boost Facebook Profil '.$user->getNom().' '.$user->getPrenom(),
            'facebook/boost_profile.mail.twig',
            [
                'user' => $user,
                'boost' => $boost,
                'url' => $url,
            ]
        );
    }

    public function facebookBoostPrestation(User $user, Prestation $prestation, BoostVisibility $boost)
    {
        $url = '';
        $url = $this->urlGenerator->generate('app_v2_staff_view_prestation', [
            'prestation' => $prestation->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->mailerService->send(
            'jrandriamalala.olona@gmail.com',
            'Notification de Boost Facebook Prestation '.$user->getNom().' '.$user->getPrenom(),
            'facebook/boost_prestation.mail.twig',
            [
                'user' => $user,
                'prestation' => $prestation,
                'boost' => $boost,
                'url' => $url,
            ]
        );
    }

    public function facebookBoostJobListing(User $user, JobListing $jobListing, BoostVisibility $boost)
    {
        $url = '';
        $url = $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', [
            'id' => $jobListing->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->mailerService->send(
            'jrandriamalala.olona@gmail.com',
            'Notification de Boost Facebook Annonce '.$user->getNom().' '.$user->getPrenom(),
            'facebook/boost_job_listing.mail.twig',
            [
                'user' => $user,
                'jobListing' => $jobListing,
                'boost' => $boost,
                'url' => $url,
            ]
        );
    }
}