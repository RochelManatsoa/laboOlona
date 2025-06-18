<?php

namespace App\WhiteLabel\Controller\Client1;

use App\WhiteLabel\Manager\Client1\JobListingManager;
use App\WhiteLabel\Entity\Client1\User;
use App\WhiteLabel\Form\Client1\RegistrationFormType;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

        return match (true) {
            $security->isGranted('ROLE_ADMIN')     => $this->redirectToRoute('app_white_label_client1_admin'),
            $security->isGranted('ROLE_RECRUITER') => $this->redirectToRoute('app_white_label_client1_recruiter'),
            $security->isGranted('ROLE_EMPLOYE')   => $this->redirectToRoute('app_white_label_client1_employe'),
            $security->isGranted('ROLE_CANDIDAT')  => $this->redirectToRoute('app_white_label_client1_user'),
            default => $this->render('white_label/client1/home/home.html.twig', [
                'job_offers' => $this->entityManager->getRepository(JobListing::class)->findBy(
                    ['status' => JobListing::STATUS_PUBLISHED],
                    ['id' => 'DESC'],
                    5
                ),
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
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($form->get('createOlonaAccount')->getData()) {
                $defaultEm = $this->managerRegistry->getManager();
                $existing = $defaultEm->getRepository(\App\Entity\User::class)->findOneBy(['email' => $user->getEmail()]);
                if (!$existing) {
                    $mainUser = new \App\Entity\User();
                    $mainUser->setEmail($user->getEmail());
                    $mainUser->setNom($user->getNom());
                    $mainUser->setPrenom($user->getPrenom());
                    $mainUser->setDateInscription(new \DateTime());
                    $mainUser->setPassword(
                        $userPasswordHasher->hashPassword($mainUser, $plainPassword)
                    );
                    $defaultEm->persist($mainUser);
                    $defaultEm->flush();
                }
            }

            return $this->redirectToRoute('app_white_label_home');

        }

        return $this->render('white_label/client1/home/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/annonces', name: 'app_white_label_annonces')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);        
        return $this->render('white_label/client1/home/annonces.html.twig', [
            'joblistings' => $this->entityManager->getRepository(JobListing::class)->paginateJobListings($paginatorInterface, JobListing::STATUS_PUBLISHED, $page, $size),
        ]);
    }

    #[Route('/job/{jobId}', name: 'app_white_label_annonce_view')]
    public function viewJobOffer(Request $request, JobListing $annonce, JobListingManager $jobListingManager): Response
    {
        if ($annonce === null || $annonce->getStatus() === JobListing::STATUS_DELETED || $annonce->getStatus() === JobListing::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le annonce demandé n\'existe pas.');
        }
        $currentUser = $this->getUser();
        if($currentUser){
            return $this->render("white_label/client1/user/annonce.html.twig", [
                'jobOffer' => $annonce,
            ]);
        }
        // if ($currentUser && $profile) {
        //     if($profile instanceof CandidateProfile){
        //         return $this->redirectToRoute('app_tableau_de_bord_candidat_view_job_offer', ['id' => $id]);
        //     }
        //     if($profile instanceof EntrepriseProfile){
        //         return $this->redirectToRoute('app_tableau_de_bord_entreprise_view_job_offer', ['id' => $id]);
        //     }
        // }
        $jobListingManager->incrementView($annonce, $request->getClientIp());

        return $this->render("white_label/client1/home/annonce.html.twig", [
            'jobOffer' => $annonce,
        ]);
    }
}
