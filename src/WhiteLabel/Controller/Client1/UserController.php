<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_client1_user')]
    public function index(): Response
    {
        return $this->render('white_label/client1/user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
    
    #[Route('/mes-infos', name: 'app_white_label_client1_user_profile')]
    public function profile(): Response
    {
        return $this->render('white_label/client1/user/profile.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/trouver-une-mission', name: 'app_white_label_client1_user_missions')]
    public function missions(): Response
    {
        return $this->render('white_label/client1/user/missions.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/modifier-mot-de-passe', name: 'app_white_label_client1_user_password')]
    public function password(): Response
    {
        return $this->render('white_label/client1/user/password.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/candidatures', name: 'app_white_label_client1_user_candidatures')]
    public function candidatures(): Response
    {
        return $this->render('white_label/client1/user/candidatures.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
}
