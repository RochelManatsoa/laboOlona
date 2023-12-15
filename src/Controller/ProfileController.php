<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Candidate\CV;
use App\Entity\Enum\TypeUser;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Form\Profile\AccountType;
use App\Service\User\UserService;
use App\Form\Profile\EntrepriseType;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use App\Form\Profile\Candidat\StepThreeType;
use App\Manager\ModerateurManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ProfileManager $profileManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private FileUploader $fileUploader,
        private ModerateurManager $moderateurManager,
    ){
    }
    
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        if (null === $user || $user->getType() === User::ACCOUNT_CANDIDAT) {
            return $this->redirectToRoute('app_profile_candidate_step_one');
        }
        if (null === $user || $user->getType() === User::ACCOUNT_ENTREPRISE) {
            return $this->redirectToRoute('app_profile_entreprise');
        }
        if (null === $user || $user->getType() === User::ACCOUNT_MODERATEUR) {
            return $this->redirectToRoute('app_profile_moderateur');
        }
        
        return $this->redirectToRoute('app_profile_account', []);
    }
    
    #[Route('/profile/account', name: 'app_profile_account')]
    public function account(Request $request): Response
    {
        $user = $this->userService->getCurrentUser();
        $form = $this->createForm(AccountType::class, $user,[]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();
            $this->mailerService->send(
                $user->getEmail(),
                "Bienvenue sur Olona Talents",
                "welcome.html.twig",
                [
                    'user' => $user,
                    'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            if($user->getType() !== User::ACCOUNT_CANDIDAT ) return $this->redirectToRoute('app_profile_entreprise', []);
            
            return $this->redirectToRoute('app_profile_candidate_step_one', []);
        }

        return $this->render('profile/account.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/company', name: 'app_profile_entreprise')]
    public function company(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $company = $user->getEntrepriseProfile();

        if (!$company instanceof EntrepriseProfile) {
            $company = $this->profileManager->createCompany($user);
        }

        $form = $this->createForm(EntrepriseType::class, $company, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileManager->saveForm($form);

            return $this->redirectToRoute('app_profile_confirmation', []);
        }

        return $this->render('profile/entreprise.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/candidate/step-one', name: 'app_profile_candidate_step_one')]
    public function candidateStepOne(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        if (!$candidat instanceof CandidateProfile) {
            $candidat = $this->profileManager->createCandidat($user);
        }

        $form = $this->createForm(StepOneType::class, $candidat, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $candidat = $form->getData();
            $this->em->persist($candidat);
            $this->em->flush();

            if($candidat instanceof CandidateProfile && !$candidat->isEmailSent()){
                $candidat->setEmailSent(true);
                $this->mailerService->sendMultiple(
                    $this->moderateurManager->getModerateurEmails(),
                    "Nouvel inscrit sur Olona Talents",
                    "moderateur/notification_welcome.html.twig",
                    [
                        'user' => $candidat->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidat_view', ['id' => $candidat->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }
            $this->em->persist($candidat);
            $this->em->flush();

            return $this->redirectToRoute('app_profile_candidate_step_two', []);
        }

        return $this->render('profile/candidat/step-one.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/candidate/step-two', name: 'app_profile_candidate_step_two')]
    public function candidateStepTwo(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $initialCounts = [
            'competences' => count($candidat->getCompetences()),
            'experiences' => count($candidat->getExperiences()),
            'langages' => count($candidat->getLangages())
        ];

        $form = $this->createForm(StepTwoType::class, $candidat, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $profile = $form->getData();
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile);
                $profile->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $profile);
            }
            $this->profileManager->saveCandidate($profile);
            $reloadSamePage = false;
            foreach ($initialCounts as $field => $initialCount) {
                if (count($form->get($field)->getData()) !== $initialCount) {
                    $reloadSamePage = true;
                    break;
                }
            }

            if ($reloadSamePage) {
                // Si le nombre d'éléments dans un des CollectionType a changé, rechargez la même page
                return $this->redirectToRoute('app_profile_candidate_step_two', []);
            } else {
                // Sinon, redirigez vers app_profile_candidate_step_three
                return $this->redirectToRoute('app_profile_candidate_step_three', []);
            }

            return $this->redirectToRoute('app_profile_confirmation', []);
        }

        return $this->render('profile/candidat/step-two.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
            'competences' => $candidat->getCompetences(),
            'experiences' => $candidat->getExperiences(),
            'langages' => $candidat->getLangages(),
        ]);
    }

    #[Route('/profile/candidate/step-three', name: 'app_profile_candidate_step_three')]
    public function stepThree(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        $form = $this->createForm(StepThreeType::class, $candidat, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileManager->saveForm($form);

            return $this->redirectToRoute('app_profile_confirmation', []);
        }

        return $this->render('profile/candidat/step-three.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/cv/{id}/select', name: 'app_profile_candidate_select_CV')]
    public function candidateSelectCV(Request $request, CV $cv)
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if ($cv instanceof CV) {
            $candidat->setCv($cv->getCvLink());
            $this->em->flush();
            $message = "ok";
        }else{
            $message = "error: CV not found";
        }

        return $this->json([
            'message' => $message
        ], 200);
    }

    #[Route('/profile/moderateur', name: 'app_profile_moderateur')]
    public function moderateur(): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();

        if (!$moderateur instanceof ModerateurProfile) {
            $moderateur = $this->profileManager->createModerateur($user);
        }
        return $this->render('profile/confirmation.html.twig', [
            'controller_name' => 'profileController',
        ]);
    }

    #[Route('/profile/confirmation', name: 'app_profile_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('profile/confirmation.html.twig', [
            'controller_name' => 'profileController',
        ]);
    }

    private function userType()
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        
        switch ($user->getType()) {
            case TypeUser::Candidat:
                $profile = $user->getCandidateProfile();
                break;
            
            case TypeUser::Entreprise:
                $profile = $user->getEntrepriseProfile();
                break;

            case TypeUser::Moderateur:
                $profile = $user->getModerateurProfile();
                break;

            default:
                $profile = null;
                break;
        }

        return $profile;
    }
}
