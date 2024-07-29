<?php

namespace App\Controller\V2\Candidate;

use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/job-offer')]
class JobOfferController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_job_offer')]
    public function index(): Response
    {
        return $this->render('v2/dashboard/candidate/job_offer/index.html.twig', [
            'controller_name' => 'JobOfferController',
        ]);
    }
}
