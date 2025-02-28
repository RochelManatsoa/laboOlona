<?php

namespace App\Controller\TableauDeBord;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/tableau-de-bord/candidat')]

class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private CandidatManager $candidatManager,
    ){}


    #[Route('/', name: 'app_tableau_de_bord_candidat')]
    public function index(): Response
    {
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $candidat = $params['candidat'];
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        return $this->render('tableau_de_bord/candidat/index.html.twig', $params);
    }


    #[Route('/mon-profil', name: 'app_tableau_de_bord_candidat_profil')]
    public function profile(): Response
    {
        $params = $this->getData();
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        return $this->render('tableau_de_bord/candidat/mon_profil.html.twig', $params);
    }

    #[Route('/messages', name: 'app_tableau_de_bord_candidat_messages')]
    public function message(): Response
    {
        return $this->render('tableau_de_bord/candidat/messages.html.twig', $this->getData());
    }

    #[Route('/trouver-des-missions', name: 'app_tableau_de_bord_candidat_trouver_des_missions')]
    public function searchmission(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $limit = 10;
        $params = $this->getData();
        $candidat = $params['candidat'];
        $secteurs = $candidat->getSecteurs();
        $qb = $this->em->getRepository(JobListing::class)->createQueryBuilder('j');
        $qb->where('j.status = :status')
            ->setParameter('status', JobListing::STATUS_PUBLISHED)
            ->andWhere('j.secteur IN (:secteurs)')
            ->setParameter('secteurs', $secteurs)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $offres = $qb->getQuery()->getResult();
        $params['offres'] = $offres;

        return $this->render('tableau_de_bord/candidat/trouver_des_missions.html.twig', $params);
    }

    #[Route('/mes-prestations', name: 'app_tableau_de_bord_candidat_mes_prestations')]
    public function prestation(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_prestations.html.twig', $this->getData());
    }

    #[Route('/mes-candidatures', name: 'app_tableau_de_bord_candidat_mes_candidatures')]
    public function candidature(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_candidatures.html.twig', $this->getData());
    }

    #[Route('/missions-obtenues', name: 'app_tableau_de_bord_candidat_missions_obtenues')]
    public function missions(): Response
    {
        return $this->render('tableau_de_bord/candidat/missions_obtenues.html.twig', $this->getData());
    }

    #[Route('/reseaux-professionnelles', name: 'app_tableau_de_bord_candidat_reseaux_professionnelles')]
    public function socialpro(): Response
    {
        return $this->render('tableau_de_bord/candidat/reseaux_professionnelles.html.twig', $this->getData());
    }

    #[Route('/se-faire-recommander', name: 'app_tableau_de_bord_candidat_se_faire_recommander')]
    public function recommandation(): Response
    {
        return $this->render('tableau_de_bord/candidat/se_faire_recommander.html.twig', $this->getData());
    }

    #[Route('/notification', name: 'app_tableau_de_bord_candidat_notification')]
    public function notification(): Response
    {
        return $this->render('tableau_de_bord/candidat/notification.html.twig', $this->getData());
    }

    #[Route('/mes-taches', name: 'app_tableau_de_bord_candidat_mes_taches')]
    public function taches(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_taches.html.twig', $this->getData());
    }

    #[Route('/mon-compte', name: 'app_tableau_de_bord_candidat_mon_compte')]
    public function mycompte(): Response
    {
        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', $this->getData());
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_candidat_mise_a_jour_mot_de_passe')]
    public function updatepassword(): Response
    {
        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', $this->getData());
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_candidat_assistance')]
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
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement.');
        $candidat = $this->userService->checkProfile();
        $data = [];
        $data['currentUser'] = $currentUser;
        $data['candidat'] = $candidat;
        $data['credit'] = $currentUser->getCredit()->getTotal();
        $data['notificationsCount'] = $this->em->getRepository(Notification::class)->countIsRead($currentUser,false);

        return $data;
    }
}
