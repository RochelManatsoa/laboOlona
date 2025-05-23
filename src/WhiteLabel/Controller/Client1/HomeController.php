<?php

namespace App\WhiteLabel\Controller\Client1;

use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Form\Client1\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/home', name: 'app_white_label_home')]
    public function index(Security $security): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_client1_login');
        }

        return match (true) {
            $security->isGranted('ROLE_ADMIN')     => $this->redirectToRoute('app_white_label_client1_admin'),
            $security->isGranted('ROLE_RECRUITER') => $this->redirectToRoute('app_white_label_client1_recruiter'),
            default => $this->render('white_label/client1/home/index.html.twig', [
                'user' => $user,
            ]),
        };
    }

    #[Route('/inscription', name: 'app_white_label_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response {
        $user = new User();
        $user->setDateInscription(new \DateTime());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_home');

        }

        return $this->render('white_label/client1/home/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
