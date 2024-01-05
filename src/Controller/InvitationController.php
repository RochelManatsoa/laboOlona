<?php

namespace App\Controller;

use DateTime;
use App\Form\NewPasswordFormType;
use App\Entity\Moderateur\Invitation;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\InvitationUsedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\InvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class InvitationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvitationRepository $repository,
        private TokenStorageInterface $tokenStorage,
        private UserPasswordHasherInterface $userPasswordHasher
    )
    {}

    #[Route('/invitation/{uuid}', name: 'app_invitation')]
    public function index(Request $request, Invitation $invitation): Response
    {
        if($invitation->getStatus() === Invitation::STATUS_USED){
            throw new InvitationUsedException('L\'invitation a été déjà utilisé ');
        }

        $user = $invitation->getReader();
        $form = $this->createForm(NewPasswordFormType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // encode the plain password
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $invitation = $this->repository->findOneBy(['reader' => $user->getId()]);
            $invitation->setUsedAt(new DateTime());
            $invitation->setStatus(Invitation::STATUS_USED);
            $this->em->persist($user);
            $this->em->flush();
            
            // Connecter l'utilisateur après la vérification de l'e-mail
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);


            return $this->redirectToRoute('app_connect');
        }

        return $this->render('invitation/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
