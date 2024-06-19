<?php

namespace App\Controller\Dashboard\Moderateur\OpenAi;

use App\Manager\OpenaiManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\OpenAITranslator;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private OpenaiManager $openaiManager,
        private CandidatManager $candidatManager,
        private OpenAITranslator $openAITranslator,
    ) {}
    
    #[Route('/api/openai/candidat/{id}', name: 'app_dashboard_moderateur_open_ai_candidat')]
    public function index(CandidateProfile $candidat): Response
    {     
        return $this->json([
            'candidat' => $candidat
        ], 200, [], ['groups' => 'open_ai']);
    }
    
    #[Route('/api/openai/generate/{id}', name: 'app_dashboard_moderateur_open_ai_generate_candidat')]
    public function resume(Request $request, CandidateProfile $candidat)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->em->getConnection()->close();
        // dd($candidat->getTesseractResult(), $this->candidatManager->generatePdfLink($candidat), $this->openAITranslator->parse($candidat));
        try {
            /** Generate OpenAI resume */
            $parsePdf = $this->openAITranslator->parse($candidat);
            $report = $this->openAITranslator->report($candidat);
            $traduction = $this->openAITranslator->trans($report);
            $metaDescription = $this->openaiManager->extractProfessionalSummary($report);
            $resumeCandidat = $this->openaiManager->extractResumeCandidate($report);
            $tools = $this->openaiManager->extractCandidateTools($report);

            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->em->getConnection()->connect();

            // Commencer une transaction
            $this->em->getConnection()->beginTransaction();
            
            // Mettre à jour l'entité candidat
            $candidat->setTesseractResult($parsePdf);
            $candidat->setResultFree($report);
            $candidat->setTraductionEn($traduction);
            $candidat->setMetaDescription($metaDescription);
            $candidat->setResumeCandidat($resumeCandidat);
            $candidat->setTools($tools);

            // Persister les modifications dans la base de données
            $this->em->persist($candidat);
            $this->em->flush();
            $this->em->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack(); // Annuler la transaction en cas d'erreur
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }

        // Rediriger vers la page précédente ou vers une route par défaut
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view', ['id' => $candidat->getId()]);
    }


}
