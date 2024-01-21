<?php

namespace App\Controller\Ajax;

use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\Assignation;
use App\Entity\Secteur;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\EntrepriseProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

class ModerateurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobListingRepository $jobListingRepository,
    )
    {}
    
    #[Route('/ajax/remove/{id}/sector', name: 'ajax_remove_sector')]
    public function remove(Secteur $secteur): Response
    {
        return $this->json([], 200, []);
    }

    #[Route('/ajax/edit/{id}/sector', name: 'ajax_edit_sector')]
    public function edit(Secteur $secteur): Response
    {
        return $this->json([], 200, []);
    }

    #[Route('/ajax/assignation/annonce/{id}', name: 'ajax_change_assignation_annonce')]
    public function annonce(Request $request, Assignation $assignation): Response
    {
        $jsonData = json_decode($request->getContent(), true);
        $forfait = $jsonData['forfait'] ?? null;
        $commentaire = $jsonData['commentaire'] ?? null;

        $assignation->setForfait($forfait);
        $assignation->setCommentaire($commentaire);
        $this->em->persist($assignation);
        $this->em->flush();
        
        return $this->json([
            'message' => 'Assignation mise à jour'
        ], 200, []);
    }

    #[Route('/ajax/assignation/delete/{id}', name: 'ajax_change_assignation_delete')]
    public function delete(Request $request, Assignation $assignation): Response
    {
        $this->em->remove($assignation);
        $this->em->flush();


        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_assignation');
    }   

    #[Route('/ajax/entreprise/select/{id}', name: 'ajax_select_entreprise')]
    public function selectEntreprise(Request $request, EntrepriseProfile $entreprise): Response
    {
        
        $jobListings = $this->jobListingRepository->findBy(['entreprise' => $entreprise]);
        
        $annonces = array_map(function ($jobListing) {
            return [
                'id' => $jobListing->getId(),
                'titre' => $jobListing->getTitre(),
                // Autres champs nécessaires
            ];
        }, $jobListings);

        return $this->json($annonces);
    }  

}
