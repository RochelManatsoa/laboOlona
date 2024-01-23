<?php

namespace App\Controller\Ajax;

use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\Assignation;
use App\Entity\Secteur;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\AssignationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ModerateurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobListingRepository $jobListingRepository,
        private AssignationRepository $assignationRepository,
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
            ];
        }, $jobListings);

        return $this->json($annonces);
    }   

    #[Route('/ajax/edit/assignation', name: 'app_ajax_edit_assignation')]
    public function editAssignation(Request $request): Response
    {
        $success = false;
        $assignationId = $request->request->get('assignation_id');
        $assignation = $this->assignationRepository->find($assignationId);

        if ($assignation) {

            $formHtml = $this->renderView('ajax/form/form_assignation.html.twig', [
                'assignation' => $assignation,
            ]);

            $success = true;

            return $this->json([
                'success' => $success,
                'form' => $formHtml,
            ]);
        } else {
            // Gérer le cas où l'assignation n'est pas trouvée
            return $this->json([
                'assignation_id' => $assignationId,
                'success' => $success,
                'error' => 'Assignation non trouvée',
            ]);
        }
    }  

    #[Route('/ajax/assignation/update/{id}', name: 'app_ajax_update_assignation')]
    public function updateAssignation(Request $request, AssignationRepository $assignationRepository, $id): Response
    {
        $assignation = $assignationRepository->find($id);

        if (!$assignation) {
            return $this->json([
                'success' => false,
                'error' => 'Assignation non trouvée',
            ]);
        }
        // Mettez à jour l'entité Assignation avec les nouvelles données
        $assignation->setForfait($request->request->get('forfait'));
        $assignation->setCommentaire($request->request->get('commentaire'));
        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($assignation);
        $this->em->flush();
        // $this->candidatManager->sendNotificationEmail($experience->getProfil());

        return $this->json([
            'success' => true,
            'assignationId' => $assignation->getId(),
            'forfait' => $assignation->getForfait(),
            'rolePositionVisee' => $assignation->getRolePositionVisee(),
            'commentaire' => $assignation->getCommentaire(),
            'date' => $assignation->getDateAssignation()->format('d-m-Y'),
            'entreprise' => $assignation->getJobListing()->getEntreprise()->getNom(),
            'annonce' => $assignation->getJobListing()->getTitre(),
            'error' => 'Assignation effectuée',
        ]);
    }

}
