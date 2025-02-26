<?php

namespace App\Controller\Marketing;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\TemplateEmail;
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
        $notification->setTitre("CVthèque - Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour ".$fullName.",</p>
            <p>Je me permets de vous écrire car je sais à quel point il peut être complexe et chronophage de dénicher les profils  qui répondent parfaitement à vos besoins spécifiques.</p>
            <p>C'est exactement pour résoudre ce défi que nous avons créé Olona-Talent , une plateforme innovante conçue pour simplifier et accélérer vos processus de recrutement.</p>
            <p>Grâce à notre base de plus de 10 000 CV tech réindexés par intelligence artificielle , vous pouvez identifier les talents dont vous avez besoin en quelques clics seulement, sans attendre que les candidats postulent à vos annonces.</p>
            <p>En plus, notre plateforme vous offre la possibilité de :</p>
            <ul>
            <li>Publier vos offres d'emploi sur la plateforme.</li>
            <li>nous pouvons mettre en avant vos opportunités sur notre page Facebook pour toucher une large communauté de professionnels .</li>
            </ul>
            <p>Je serais ravie de vous montrer comment Olona-Talent peut transformer votre manière de recruter. Êtes-vous disponible pour un appel ou une démonstration personnalisée ?</p>
            <p>Merci pour votre attention, et j'espère avoir l'opportunité de collaborer avec vous.</p>
            <p>Cordialement,</p>
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
                ],
                'partenaires@olona-talents.com',
                'partenaires@olona-talents.com',
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }
        
        return $this->render('marketing/lead/email.html.twig', [
            'lead' => $lead,
            'form' => $form->createView(),
            'templateEmails' => $entityManager->getRepository(TemplateEmail::class)->findAll(),
            'notifications' => $entityManager->getRepository(Notification::class)->findBy([
                'destinataire' => $lead->getUser()
            ], ['id' => 'DESC']),
        ]);
    }
}
