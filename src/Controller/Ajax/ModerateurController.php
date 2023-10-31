<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ModerateurManager $moderateurManager,
        private ProfileManager $profileManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ){
    }
    
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
    
    #[Route('/ajax/status/annonce/{id}', name: 'ajax_change_status_annonce', methods: ['POST'])]
    public function annonce(Request $request, JobListing $jobListing): Response
    {
        $status = $request->request->get('status');
        
        if ($status) {
            // Mettre à jour le statut
            $jobListing->setStatus($status);
            
            // Enregistrer les modifications dans la base de données
            $entityManager = $this->em;
            $entityManager->persist($jobListing);
            $entityManager->flush();

            return $this->json(['message' => 'Statut mis à jour avec succès'], 200);
        }
        
        return $this->json(['message' => 'Statut mis à jour avec succès'], 200);
    }
}
