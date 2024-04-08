<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\MailManager;
use App\Service\FileUploader;
use App\Entity\ReferrerProfile;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\Referrer\Referral;
use App\Form\Profile\AccountType;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Profile\EntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use App\Form\Profile\Candidat\StepThreeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\Profile\Referrer\StepOneType as ReferrerStepOne;
use App\Form\Profile\Referrer\StepTwoType as ReferrerStepTwo;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailManager $mailManager,
        private ProfileManager $profileManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private FileUploader $fileUploader,
        private CandidatManager $candidatManager,
        private ModerateurManager $moderateurManager,
    ) {}

    private function redirectAction(?string $type)
    {
        switch ($type) {
            case User::ACCOUNT_CANDIDAT :
                return $this->redirectToRoute('app_profile_candidate_step_one', []);
                break;

            case User::ACCOUNT_ENTREPRISE :
                return $this->redirectToRoute('app_profile_entreprise', []);
                break;

            case User::ACCOUNT_REFERRER :
                return $this->redirectToRoute('app_profile_referrer_step_one', []);
                break;
            
            default:
                return $this->redirectToRoute('app_profile_account', []);
                break;
        }
    }
    
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        
        if(null !== $user->getType()){
            return $this->redirectAction($user->getType());
        }

        return $this->redirectToRoute('app_profile_account');
    }
    
    #[Route('/profile/account', name: 'app_profile_account')]
    public function account(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        if(null !== $user->getType()){
            return $this->redirectAction($user->getType());
        }
        
        $form = $this->createForm(AccountType::class, $user,[]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();
            /** welcome mail */
            $this->mailManager->welcome($user);

            return $this->redirectAction($user->getType());
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
            // if($candidat instanceof CandidateProfile && !$candidat->isEmailSent()){
            //     $candidat->setEmailSent(true);
            //     /** notify moderateurs */
            //     $this->mailManager->newUser($candidat->getCandidat());
            // }
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
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }

    #[Route('/profile/candidate/step-three', name: 'app_profile_candidate_step_three')]
    public function candidateStepThree(Request $request): Response
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
            'candidat' => $candidat,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/referrer/step-one', name: 'app_profile_referrer_step_one')]
    public function referrerStepOne(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $referrer = $user->getReferrerProfile();

        if (!$referrer instanceof ReferrerProfile) {
            $referrer = $this->profileManager->createReferrer($user);
        }

        $form = $this->createForm(ReferrerStepOne::class, $referrer, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $referrer = $form->getData();
            $this->em->persist($referrer);
            $this->em->flush();

            /** notify moderateurs here */

            return $this->redirectToRoute('app_profile_referrer_step_two', []);
        }
        
        return $this->render('profile/referrer/step-one.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/referrer/step-two', name: 'app_profile_referrer_step_two')]
    public function referrerStepTwo(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $referrer = $user->getReferrerProfile();

        if (!$referrer instanceof ReferrerProfile) {
            $referrer = $this->redirectToRoute('app_profile_referrer_step_one');
        }

        $form = $this->createForm(ReferrerStepTwo::class, $referrer, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $referrer = $form->getData();
            $this->em->persist($referrer);
            $this->em->flush();

            /** notify moderateurs here */

            return $this->redirectToRoute('app_profile_confirmation', []);
        }
        
        return $this->render('profile/referrer/step-two.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/confirmation', name: 'app_profile_confirmation')]
    public function confirmation(): Response
    {
        $referralCode = $this->requestStack->getSession()->get('referralCode');
        
        $refered = $this->em->getRepository(Referral::class)->findOneBy(['referralCode' => $referralCode]);
        if($refered instanceof Referral && $this->userService->checkProfile() instanceof CandidateProfile){
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $refered->getAnnonce()->getJobId()]);
        }

        return $this->render('profile/confirmation.html.twig', []);
    }
}
