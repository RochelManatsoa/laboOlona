<?php

namespace App\Controller\TableauDeBord;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tableau-de-bord/entreprise')]

class EntrepriseController extends AbstractController
{
    #[Route('/', name: 'app_tableau_de_bord_entreprise')]
    public function index(): Response
    {
        return $this->render('tableau_de_bord/entreprise/index.html.twig', [
            
        ]);
    }
    #[Route('/cvtheque', name: 'app_tableau_de_bord_entreprise_cvtheque')]
    public function cvtheque(): Response
    {
        return $this->render('tableau_de_bord/entreprise/cvtheque.html.twig', [
            
        ]);
    }
    #[Route('/offre-d-emploi', name: 'app_tableau_de_bord_entreprise_offre_emploi')]
    public function offre(): Response
    {
        return $this->render('tableau_de_bord/entreprise/offre_emploi.html.twig', [
            
        ]);
    }
    #[Route('/publier-une-annonce', name: 'app_tableau_de_bord_entreprise_publier_une_annonce')]
    public function annonce(): Response
    {
        return $this->render('tableau_de_bord/entreprise/publier_une_annonce.html.twig', [
            
        ]);
    }
    #[Route('/listes-candidatures', name: 'app_tableau_de_bord_entreprise_listes_candidatures')]
    public function candidatureslist(): Response
    {
        return $this->render('tableau_de_bord/entreprise/listes_candidatures.html.twig', [
            
        ]);
    }
    #[Route('/favoris', name: 'app_tableau_de_bord_entreprise_favoris')]
    public function favoris(): Response
    {
        return $this->render('tableau_de_bord/entreprise/favoris.html.twig', [
            
        ]);
    }
    #[Route('/tarifs', name: 'app_tableau_de_bord_entreprise_tarifs')]
    public function tarifs(): Response
    {
        return $this->render('tableau_de_bord/entreprise/tarifs.html.twig', [
            
        ]);
    }
    #[Route('/notification', name: 'app_tableau_de_bord_entreprise_notification')]
    public function notification(): Response
    {
        return $this->render('tableau_de_bord/entreprise/notification.html.twig', [
            
        ]);
    }
    #[Route('/mon-compte', name: 'app_tableau_de_bord_entreprise_mon_compte')]
    public function mycompte(): Response
    {
        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', [
            
        ]);
    }
    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_entreprise_mise_a_jour_mot_de_passe')]
    public function updatepassword(): Response
    {
        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', [
            
        ]);
    }
    #[Route('/assistance', name: 'app_tableau_de_bord_entreprise_assistance')]
    public function assistance(): Response
    {
        return $this->render('tableau_de_bord/candidat/assistance.html.twig', [
            
        ]);
    }
}
