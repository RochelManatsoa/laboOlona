<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Devise;
use App\Manager\NotificationManager;
use App\Entity\Moderateur\Assignation;
use App\Manager\ModerateurManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\AssignationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ModerateurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobListingRepository $jobListingRepository,
        private AssignationRepository $assignationRepository,
        private NotificationManager $notificationManager,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private AppExtension $appExtension,
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

    #[Route('/ajax/devise/select/{id}', name: 'ajax_select_devise')]
    public function selectDevise(Request $request, int $id): Response
    {        
        $devise = $this->em->getRepository(Devise::class)->find((int) $id);
        
        return $this->json($devise, 200, [], ['groups' => 'devise']);
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
        $assignation->setStatus(Assignation::STATUS_MODERATED);
        $assignation->setForfait($request->request->get('forfait'));
        $assignation->setCommentaire($request->request->get('commentaire'));
        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($assignation);
        $this->em->flush();
        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_connect');

        /** Send Notification */
        $titre = 'Réponse à votre demande de devis';
        $titreMod = 'Réponse à la demande de devis de '.$assignation->getJobListing()->getEntreprise()->getNom().' pour '.$this->appExtension->generatePseudo($assignation->getProfil());
        $contenu = '             
            <p>Nous vous remercions pour votre demande de devis concernant le candidat '.$this->appExtension->generatePseudo($assignation->getProfil()).'. Nous sommes heureux de vous informer que nous avons préparé une proposition adaptée à vos besoins.</p>
            <p>Voici les détails de notre offre :</p>
            <ul>
                <li>Prix estimatif : '.$assignation->getForfait().' €</li>
                <li>Conditions spécifiques : '.$assignation->getCommentaire().'</li>
            </ul>
            <p>Nous espérons que notre proposition vous conviendra et restons à votre disposition pour toute modification ou précision supplémentaire.</p>
            <p>Nous sommes impatients de travailler avec vous et de contribuer au succès de votre projet.</p>
            <p>Cordialement,</p>
        ';
        $contenuMod = ' 
        <p>Voici les détails de l\'offre :</p>
            <ul>
                <li>Prix estimatif : '.$assignation->getForfait().' €</li>
                <li>Conditions spécifiques : '.$assignation->getCommentaire().'</li>
            </ul>
        ';
        $this->notificationManager->notifyModerateurs($assignation->getProfil()->getCandidat(), Notification::TYPE_CONTACT, $titreMod, $contenuMod );
        $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $assignation->getJobListing()->getEntreprise()->getEntreprise(), Notification::TYPE_CONTACT, $titre, $contenu );
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
