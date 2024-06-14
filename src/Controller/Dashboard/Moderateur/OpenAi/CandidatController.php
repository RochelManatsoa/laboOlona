<?php

namespace App\Controller\Dashboard\Moderateur\OpenAi;

use App\Entity\CandidateProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CandidatController extends AbstractController
{
    #[Route('/api/openai/candidat/{id}', name: 'app_dashboard_moderateur_open_ai_candidat')]
    public function index(CandidateProfile $candidat): Response
    {     
        return $this->json([
            'candidat' => $candidat
        ], 200, [], ['groups' => 'open_ai']);
    }
}
