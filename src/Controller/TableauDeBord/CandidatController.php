<?php

namespace App\Controller\TableauDeBord;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tableau-de-bord/candidat')]

class CandidatController extends AbstractController
{
    #[Route('/', name: 'app_tableau_de_bord_candidat')]
    public function index(): Response
    {
        return $this->render('tableau_de_bord/candidat/index.html.twig', [
            
        ]);
    }

    #[Route('/mon-profil', name: 'app_tableau_de_bord_candidat_profil')]
    public function profile(): Response
    {
        return $this->render('tableau_de_bord/candidat/mon_profil.html.twig', [
            
        ]);
    }
    #[Route('/messages', name: 'app_tableau_de_bord_candidat_messages')]
    public function message(): Response
    {
        return $this->render('tableau_de_bord/candidat/messages.html.twig', [
            
        ]);
    }
    #[Route('/trouver-des-missions', name: 'app_tableau_de_bord_candidat_trouver_des_missions')]
    public function searchmission(): Response
    {
        return $this->render('tableau_de_bord/candidat/trouver_des_missions.html.twig', [
            
        ]);
    }
    #[Route('/mes-prestations', name: 'app_tableau_de_bord_candidat_mes_prestations')]
    public function prestation(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_prestations.html.twig', [
            
        ]);
    }
    #[Route('/mes-candidatures', name: 'app_tableau_de_bord_candidat_mes_candidatures')]
    public function candidature(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_candidatures.html.twig', [
            
        ]);
    }
    #[Route('/missions-obtenues', name: 'app_tableau_de_bord_candidat_missions_obtenues')]
    public function missions(): Response
    {
        return $this->render('tableau_de_bord/candidat/missions_obtenues.html.twig', [
            
        ]);
    }
    #[Route('/reseaux-professionnelles', name: 'app_tableau_de_bord_candidat_reseaux_professionnelles')]
    public function socialpro(): Response
    {
        return $this->render('tableau_de_bord/candidat/reseaux_professionnelles.html.twig', [
            
        ]);
    }
    #[Route('/se-faire-recommander', name: 'app_tableau_de_bord_candidat_se_faire_recommander')]
    public function recommandation(): Response
    {
        return $this->render('tableau_de_bord/candidat/se_faire_recommander.html.twig', [
            
        ]);
    }
    #[Route('/notification', name: 'app_tableau_de_bord_candidat_notification')]
    public function notification(): Response
    {
        return $this->render('tableau_de_bord/candidat/notification.html.twig', [
            
        ]);
    }
    #[Route('/mes-taches', name: 'app_tableau_de_bord_candidat_mes_taches')]
    public function taches(): Response
    {
        return $this->render('tableau_de_bord/candidat/mes_taches.html.twig', [
            
        ]);
    }
    #[Route('/mon-compte', name: 'app_tableau_de_bord_candidat_mon_compte')]
    public function mycompte(): Response
    {
        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', [
            
        ]);
    }
    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_candidat_mise_a_jour_mot_de_passe')]
    public function updatepassword(): Response
    {
        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', [
            
        ]);
    }
    #[Route('/assistance', name: 'app_tableau_de_bord_candidat_assistance')]
    public function assistance(): Response
    {
        return $this->render('tableau_de_bord/candidat/assistance.html.twig', [
            
        ]);
    }
}
