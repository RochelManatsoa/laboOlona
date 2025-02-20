<?php

namespace App\Controller\Marketing;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Marketing\Lead;
use App\Form\Marketing\LeadType;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Marketing\LeadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/moderateur/marketing/lead')]
class LeadController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
    ){}
    
    #[Route('/', name: 'app_marketing_lead_index', methods: ['GET'])]
    public function index(
        Request $request, 
        LeadRepository $leadRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', null);

        return $this->render('marketing/lead/index.html.twig', [
            'leads' => $leadRepository->paginateLeads($page, $status),
            'count' => $leadRepository->countAll(),
            'countStatus' => $leadRepository->countStatus($status),
            'statuses' => array_merge(['Tous' => 'ALL' ],Lead::getStatuses()),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_marketing_lead_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lead = new Lead();
        $form = $this->createForm(LeadType::class, $lead);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lead);
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_lead_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/lead/new.html.twig', [
            'lead' => $lead,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_lead_show', methods: ['GET'])]
    public function show(Lead $lead): Response
    {
        return $this->render('marketing/lead/show.html.twig', [
            'lead' => $lead,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_marketing_lead_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lead $lead, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LeadType::class, $lead);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lead->setLastContacted(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_lead_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/lead/edit.html.twig', [
            'lead' => $lead,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_lead_delete', methods: ['POST'])]
    public function delete(Request $request, Lead $lead, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lead->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lead);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_marketing_lead_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/email', name: 'app_marketing_lead_show_email', methods: ['GET', 'POST'])]
    public function email(Request $request, Lead $lead, EntityManagerInterface $entityManager): Response
    {
        $fullName = $lead->getFullName();
        if($lead->getUser() instanceof User) {
            $fullName = $lead->getUser()->getPrenom();
        }
        $notification = new Notification();
        $notification->setDateMessage(new \DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($lead->getUser());
        $notification->setType(Notification::TYPE_PROFIL);
        $notification->setIsRead(false);
        $notification->setTitre($lead->getSource()->getName()." - Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour ".$fullName.",</p>
            <p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p>
            <p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p>
            <p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p>
            <p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>
            <p>Ravaka, de l'équipe Olona Talents</p>
            "
        );

        $form = $this->createForm(NotificationProfileType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $lead->setEmailSent($lead->getEmailSent() + 1);
            $entityManager->persist($lead);
            $entityManager->persist($notification);
            $entityManager->flush();
            /** Envoi email à l'utilisateur */
            $this->mailerService->send(
                $lead->getUser()->getEmail(),
                $notification->getTitre(),
                "moderateur/notification_profile.html.twig",
                [
                    'user' => $lead->getUser(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_compte', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }
        
        return $this->render('marketing/lead/email.html.twig', [
            'lead' => $lead,
            'form' => $form->createView(),
            'notifications' => $entityManager->getRepository(Notification::class)->findBy([
                'destinataire' => $lead->getUser()
            ], ['id' => 'DESC']),
        ]);
    }
}
