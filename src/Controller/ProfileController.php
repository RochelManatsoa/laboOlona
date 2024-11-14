<?php

namespace App\Controller;

use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{    
    public function __construct(
        private UserService $userService
    )
    {}

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        if ($currentUser) {
            return $this->redirectToRoute('app_v2_dashboard_create_profile', ['id' => $currentUser->getId()]);
        }

        return $this->redirectToRoute('app_login');
    }
    
    #[Route('/profile/account', name: 'app_profile_account')]
    public function account(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/company', name: 'app_profile_entreprise')]
    public function company(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/candidate/step-one', name: 'app_profile_candidate_step_one')]
    public function candidateStepOne(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/candidate/step-two', name: 'app_profile_candidate_step_two')]
    public function candidateStepTwo(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/candidate/step-three', name: 'app_profile_candidate_step_three')]
    public function candidateStepThree(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/referrer/step-one', name: 'app_profile_referrer_step_one')]
    public function referrerStepOne(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/referrer/step-two', name: 'app_profile_referrer_step_two')]
    public function referrerStepTwo(): Response
    {
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/confirmation', name: 'app_profile_confirmation')]
    public function confirmation(): Response
    {
        return $this->redirectToRoute('app_profile');
    }
}
