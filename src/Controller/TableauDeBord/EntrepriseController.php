<?php

namespace App\Controller\TableauDeBord;

use App\Entity\Candidate\Applications;
use App\Entity\Entreprise\Favoris;
use App\Entity\Entreprise\JobListing;
use App\Entity\Notification;
use App\Entity\User;
use App\Form\Entreprise\JobListingType;
use App\Manager\ProfileManager;
use App\Service\ActivityLogger;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/tableau-de-bord/entreprise')]

class EntrepriseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private UserService $userService,
        private CreditManager $creditManager,
        private ActivityLogger $activityLogger,
    ){}

    #[Route('/', name: 'app_tableau_de_bord_entreprise')]
    public function index(): Response
    {
        return $this->render('tableau_de_bord/entreprise/index.html.twig', $this->getData());
    }

    #[Route('/cvtheque', name: 'app_tableau_de_bord_entreprise_cvtheque')]
    public function cvtheque(): Response
    {
        return $this->render('tableau_de_bord/entreprise/cvtheque.html.twig', $this->getData());
    }
    
    #[Route('/offre-d-emploi', name: 'app_tableau_de_bord_entreprise_offre_emploi')]
    public function offre(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $offres = $this->em->getRepository(JobListing::class)->paginateJobListingsEntrepriseProfiles($page, $entreprise);
        $params['offres'] = $offres;

        return $this->render('tableau_de_bord/entreprise/offre_emploi.html.twig', $params);
    }

    #[Route('/publier-une-annonce', name: 'app_tableau_de_bord_entreprise_publier_une_annonce')]
    public function annonce(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        $params = $this->getData();
        $form = $this->createForm(JobListingType::class, new JobListing());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing = $form->getData();
            $jobListing->setEntreprise($params['entreprise']);
            $em->persist($jobListing);
            $em->flush();
            $this->addFlash('success', 'Annonce créée avec succès');

            return $this->redirectToRoute('app_tableau_de_bord_entreprise_listes_candidatures');
        }
        $params['form'] = $form->createView();
        
        return $this->render('tableau_de_bord/entreprise/publier_une_annonce.html.twig', $params);
    }

    #[Route('/listes-candidatures', name: 'app_tableau_de_bord_entreprise_listes_candidatures')]
    public function candidatureslist(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $candidatures = $this->em->getRepository(Applications::class)->findByEntrepriseProfile($page, $entreprise->getId());
        $params['candidatures'] = $candidatures;

        return $this->render('tableau_de_bord/entreprise/listes_candidatures.html.twig', $params);
    }

    #[Route('/favoris', name: 'app_tableau_de_bord_entreprise_favoris')]
    public function favoris(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];  
        $favoris = $this->em->getRepository(Favoris::class)->paginateFavoris($page, $entreprise);
        $params['favoris'] = $favoris;

        return $this->render('tableau_de_bord/entreprise/favoris.html.twig', $params);
    }

    #[Route('/tarifs', name: 'app_tableau_de_bord_entreprise_tarifs')]
    public function tarifs(): Response
    {
        return $this->render('tableau_de_bord/entreprise/tarifs.html.twig', $this->getData());
    }
    #[Route('/profil-candidat', name: 'app_tableau_de_bord_entreprise_profil_candidat')]
    public function profilcandidat(): Response
    {
        return $this->render('tableau_de_bord/entreprise/profil_candidat.html.twig', $this->getData());
    }
    #[Route('/annuaire-de-services', name: 'app_tableau_de_bord_entreprise_annuaire_de_services')]
    public function annuaire(): Response
    {
        return $this->render('tableau_de_bord/entreprise/annuaire_de_services.html.twig', $this->getData());
    }
    #[Route('/profil-public', name: 'app_tableau_de_bord_entreprise_profil_public')]
    public function profilpublic(): Response
    {
        return $this->render('tableau_de_bord/entreprise/profil_public.html.twig', $this->getData());
    }

    #[Route('/notification', name: 'app_tableau_de_bord_entreprise_notification')]
    public function notification(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $notifications = $this->em->getRepository(Notification::class)->findByDestinataire($currentUser, null, [], $page);
        $params['notifications'] = $notifications;

        return $this->render('tableau_de_bord/entreprise/notification.html.twig', $params);
    }

    #[Route('/mon-compte', name: 'app_tableau_de_bord_entreprise_mon_compte')]
    public function mycompte(): Response
    {
        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', $this->getData());
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_entreprise_mise_a_jour_mot_de_passe')]
    public function updatepassword(): Response
    {
        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', $this->getData());
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_entreprise_assistance')]
    public function assistance(): Response
    {
        return $this->render('tableau_de_bord/candidat/assistance.html.twig', $this->getData());
    }

    private function getData()
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $entreprise = $this->userService->checkProfile();
        $data = [];
        $data['currentUser'] = $currentUser;
        $data['entreprise'] = $entreprise;
        $data['credit'] = $currentUser->getCredit()->getTotal();
        $data['notificationsCount'] = $this->em->getRepository(Notification::class)->countIsRead($currentUser,false);
        $data['favorisCount'] = count($entreprise->getFavoris());

        return $data;
    }
}
