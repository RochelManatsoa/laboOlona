<?php

namespace App\Controller\Moderateur;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\Mailer;
use App\Entity\Moderateur\Invitation;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\InvitationCoworkingType;
use App\Repository\Moderateur\InvitationRepository;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/invitation')]
#[IsGranted('ROLE_MODERATEUR')]
class InvitationController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_moderateur_invitation', methods: ['GET'])]
    public function index(InvitationRepository $invitationRepository, Request $request): Response
    {
        $page = $request->query->get('page', 1);

        return $this->render('moderateur/invitation/index.html.twig', [
            'invitations' => $invitationRepository->paginateInvitation($page),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_moderateur_invitation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        UserRepository $userRepository, 
        MailerService $mailerService,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ): Response
    {
        $invitation = new Invitation();
        $form = $this->createForm(InvitationCoworkingType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            if(!$userRepository->findOneBy(['email' => $email])){
                
                $user = new User();
                    $user->setDateInscription(new \DateTime())
                    ->setEmail($email)
                ;
                $invitation->setCreatedAt(new \DateTime());
                $invitation->setReader($user);
                $invitation->setStatus(Invitation::STATUS_PENDING);
                $invitation->setUuid(Uuid::v4());
                $entityManager->persist($user);
                $entityManager->persist($invitation);
                $entityManager->flush();
                $this->addFlash('success', 'Invitation envoyée');
                /** Envoi email de mot de passe */
                $mailerService->send(
                    $email,
                    "Invitation exclusive de Olona Talents : Débloquez votre potentiel dès maintenant !",
                    "invitation.html.twig",
                    [
                        'user' => $invitation->getReader(),
                        'dashboard_url' => $urlGenerator->generate(
                            'app_invitation',
                            [
                                'uuid' => $invitation->getUuid()
                            ], 
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ]
                );
    
                return $this->redirectToRoute('app_moderateur_invitation_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->addFlash('danger', 'Cet email est déjà utilisé');
        }

        return $this->render('moderateur/invitation/new.html.twig', [
            'invitation' => $invitation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_moderateur_invitation_show', methods: ['GET'])]
    public function show(Invitation $invitation): Response
    {
        return $this->render('moderateur/invitation/show.html.twig', [
            'invitation' => $invitation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_moderateur_invitation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Invitation $invitation, 
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(InvitationCoworkingType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_invitation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/invitation/edit.html.twig', [
            'invitation' => $invitation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_moderateur_invitation_delete', methods: ['POST'])]
    public function delete(Request $request, Invitation $invitation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$invitation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($invitation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_invitation_index', [], Response::HTTP_SEE_OTHER);
    }
}
