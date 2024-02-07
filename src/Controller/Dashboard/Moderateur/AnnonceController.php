<?php

namespace App\Controller\Dashboard\Moderateur;

use DateTime;
use App\Entity\Notification;
use App\Entity\Vues\AnnonceVues;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Form\Entreprise\JobListingType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Moderateur\NotificationType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\Search\Annonce\ModerateurAnnonceSearchType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class AnnonceController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}
    
    #[Route('/annonces', name: 'app_dashboard_moderateur_annonces')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->query->get('status');

        /** Formulaire de recherche annonces */
        $form = $this->createForm(ModerateurAnnonceSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->searchAnnonce(null, null, $status);
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $entreprise = $form->get('entreprise')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchAnnonce($titre, $entreprise, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/annonce/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/annonce/index.html.twig', [
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}', name: 'app_dashboard_moderateur_annonce_view', methods: ['GET', 'POST'])]
    public function viewAnnonce(Request $request, JobListing $annonce): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(JobListingType::class, $annonce);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Annonce mise à jour avec succès');

            return $this->redirectToRoute('app_dashboard_moderateur_annonce_view', [ 'id' => $form->getData()->getId()]);
        }

        return $this->render('dashboard/moderateur/annonce/view.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}/candidature', name: 'app_dashboard_moderateur_annonce_candidature_view', methods: ['GET'])]
    public function viewCandidatureAnnonce(JobListing $annonce): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        return $this->render('dashboard/moderateur/annonce/candidature.html.twig', [
            'annonce' => $annonce,
            'candidatures' => $annonce->getApplications(),
        ]);
    }

    #[Route('/annonce/{id}/assignation', name: 'app_dashboard_moderateur_annonce_assignation_view', methods: ['GET'])]
    public function viewassignationAnnonce(JobListing $annonce): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        return $this->render('dashboard/moderateur/annonce/assignation.html.twig', [
            'annonce' => $annonce,
            'candidatures' => $annonce->getAssignations(),
        ]);
    }

    #[Route('/notifier/{annonce}/entreprise/{entreprise}', name: 'app_dashboard_moderateur_annonce_notifier')]
    public function notifierAnnonce(Request $request, JobListing $annonce, EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($entreprise->getEntreprise());
        $notification->setType(Notification::TYPE_ANNONCE);
        $notification->setIsRead(false);

        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'entreprise */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                "Statut de votre annonce sur Olona Talents",
                "notification_annonce.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'details_annonce' => $notification->getContenu(),
                    'objet' => "est toujours en cours de moderation",
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_view_annonce', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé à l\'entreprise');

            return $this->redirectToRoute('app_dashboard_moderateur_annonce_view', ['id' => $annonce->getId()]);
        }

        return $this->render('dashboard/moderateur/annonce/notify.html.twig', [
            'annonce' => $annonce,
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/status/annonce/{id}', name: 'change_status_annonce')]
    public function changeAnnonceStatus(Request $request, EntityManagerInterface $entityManager, JobListing $annonce): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->request->get('status');
        if ($status && in_array($status, JobListing::getArrayStatuses())) {
            $annonce->setStatus($status);
            $entityManager->flush();
            /** Envoi email à l'entreprise si validée*/
            if($annonce->getStatus() === JobListing::STATUS_PUBLISHED || $annonce->getStatus() === JobListing::STATUS_FEATURED ){
                $this->mailerService->send(
                    $annonce->getEntreprise()->getEntreprise()->getEmail(),
                    "Statut de votre annonce sur Olona Talents",
                    "entreprise/notification_annonce.html.twig",
                    [
                        'user' => $annonce->getEntreprise()->getEntreprise(),
                        'details_annonce' => $annonce,
                        'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_view_annonce', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_annonces');
    }

    #[Route('/delete/annonce/{id}', name: 'delete_annonce')]
    public function deleteAnnonce(JobListing $annonce, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $applicationRepository = $entityManager->getRepository(Applications::class);
        $applications = $applicationRepository->findBy(['annonce' => $annonce]);
        
        foreach ($applications as $application) {
            $entityManager->remove($application);
        }
        
        $annonceVues = $entityManager->getRepository(AnnonceVues::class)->findBy(['annonce' => $annonce]);
        foreach ($annonceVues as $vue) {
            $entityManager->remove($vue);
        }
        $entityManager->remove($annonce);
        $entityManager->flush();
        $this->addFlash('success', 'Annonce supprimée avec succès.');

        return $this->redirectToRoute('app_dashboard_moderateur_annonces');
    }

    #[Route('/details/annonce/{id}', name: 'details_annonce', methods: ['GET'])]
    public function detailsAnnonce(JobListing $annonce): JsonResponse
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $annonceDetails = [
            'titre' => $annonce->getTitre(),
            'description' => $annonce->getDescription(),
            'dateCreation' => $annonce->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateExpiration' => $annonce->getDateExpiration()?->format('Y-m-d H:i:s'),
            'status' => $annonce->getStatus(),
            'salaire' => $annonce->getSalaire(),
            'lieu' => $annonce->getLieu(),
            'typeContrat' => $annonce->getTypeContrat(),
        ];

        return $this->json($annonceDetails);
    }
}
