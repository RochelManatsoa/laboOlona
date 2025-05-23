<?php

namespace App\Manager;

use App\Entity\Candidate\Applications;
use DateTime;
use Symfony\Component\Form\Form;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\Assignation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\AssignationRepository;
use Doctrine\ORM\Query\Expr\Func;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApplicationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private JobListingRepository $jobListingRepository,
        private AssignationRepository $assignationRepository,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private UserService $userService
    ){}

    public function init(): Applications
    {
        $application = new Applications();
        $application->setDateCandidature(new \DateTime());
        $application->setStatus(Applications::STATUS_PENDING);
        
        return $application;
    }

    public function save(Applications $application)
    {
        $this->em->persist($application);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $application = $form->getData();
        $this->save($application);

        return $application;
    }

    public function sendEmails(Applications $application)
    {    
        /** Envoi email moderateur */
        $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            "Une nouvelle candidature sur Olona Talents",
            "moderateur/notification_candidature.html.twig",
            [
                'user' => $application->getCandidat()->getCandidat(),
                'candidature' => $application,
                'objet' => "mise à jour",
                'details_annonce' => $application->getAnnonce(),
                'dashboard_url' => $this->urlGeneratorInterface->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );

        /** Envoi email entreprise */
        $this->mailerService->send(
            $application->getAnnonce()->getEntreprise()->getEntreprise()->getEmail(),
            "Nouvelle candidature reçue sur votre annonce Olona-talents.com",
            "entreprise/notification_candidature.html.twig",
            [
                'user' => $application->getAnnonce()->getEntreprise(),
                'candidature' => $application,
                'candidat' => $application->getCandidat(),
                'objet' => "mise à jour",
                'details_annonce' => $application->getAnnonce(),
                'dashboard_url' => $this->urlGeneratorInterface->generate('app_tableau_de_bord_entreprise_listes_candidatures', ['id' => $application->getCandidat()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );

        /** Envoi email candidat */
        $this->mailerService->send(
            $application->getCandidat()->getCandidat()->getEmail(),
            "Votre candidature a été prise en compte sur Olona Talents",
            "candidat/notification_candidature.html.twig",
            [
                'user' => $application->getCandidat()->getCandidat(),
                'candidature' => $application,
                'objet' => "mise à jour",
                'details_annonce' => $application->getAnnonce(),
                'dashboard_url' => $this->urlGeneratorInterface->generate('app_tableau_de_bord_candidat_mes_candidatures', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }
}