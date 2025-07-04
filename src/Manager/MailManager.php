<?php

namespace App\Manager;

use Exception;
use Throwable;
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
use App\Entity\Coworking\Contract;
use App\Entity\Coworking\Reservation;
use App\Entity\Moderateur\ContactForm;
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

    public function reservationEnLigne(Reservation $reservation)
    {
        $url = '';
        $url = $this->urlGenerator->generate('app_coworking_reservation_edit', [
            'id' => $reservation->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->mailerService->sendMultiple(
            ['ambassadrices@olona-talents.com', 'admin@olona-talents.com','partenaires@olona-talents.com', 'support@olona-talents.com', 'contact@olona-talents.com'],
            'Réservation au nom de '.$reservation->getFullName(),
            'reservation/coworking.mail.twig',
            [
                'reservation' => $reservation,
                'url' => $url,
            ]
        );
    }

    public function contractVIP(Contract $contract)
    {
        $url = '';
        $url = $this->urlGenerator->generate('app_coworking_contract_show', [
            'id' => $contract->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->mailerService->send(
            $contract->getEmail(), 
            'Confirmation de votre souscription au contrat VIP Coworking Olona Talents',
            'reservation/confirmation_contrat_vip.mail.twig',
            [
                'contract' => $contract,
            ]
        );
        
        return $this->mailerService->sendMultiple(
            ['ambassadrices@olona-talents.com', 'admin@olona-talents.com','partenaires@olona-talents.com', 'support@olona-talents.com', 'contact@olona-talents.com'],
            'Réservation au nom de '.$contract->getFirstName().' '.$contract->getLastName(),
            'reservation/contrat_vip.mail.twig',
            [
                'contract' => $contract,
                'url' => $url,
            ]
        );
    }

    public function contractFLEXI(Contract $contract)
    {
        $url = '';
        $url = $this->urlGenerator->generate('app_coworking_contract_show', [
            'id' => $contract->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->mailerService->send(
            $contract->getEmail(), 
            'Confirmation de votre souscription au contrat VIP Coworking Olona Talents',
            'reservation/confirmation_contrat_flexi.mail.twig',
            [
                'contract' => $contract,
            ]
        );
        
        return $this->mailerService->sendMultiple(
            ['ambassadrices@olona-talents.com', 'admin@olona-talents.com','partenaires@olona-talents.com', 'support@olona-talents.com', 'contact@olona-talents.com'],
            'Réservation au nom de '.$contract->getFirstName().' '.$contract->getLastName(),
            'reservation/contrat_vip.mail.twig',
            [
                'contract' => $contract,
                'url' => $url,
            ]
        );
    }

    public function contactForm(ContactForm $contactForm)
    {        
        return $this->mailerService->sendMultiple(
            ["contact@olona-talents.com", "support@olona-talents.com", "miandrisoa.olona@gmail.com"],
            "Nouvelle entrée sur le formulaire de contact Coworking",
            "contact.html.twig",
            [
                'user' => $contactForm,
            ]
        );
    }

    public function errorAlertUser(User $user, string $url, Throwable $exception)
    {        
        $dashboardUrl = $this->urlGenerator->generate('app_v2_staff_history_user', [
            'user' => $user->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->mailerService->send(
            'miandrisoa.olona@gmail.com',
            'Erreur experience utilisateur : '.$user->getNom().' '.$user->getPrenom(),
            'error/user_log.mail.twig',
            [
                'user' => $user,
                'exception' => $exception,
                'url' => $url,
                'dashboard_url' => $dashboardUrl,
            ]
        );
    }
}