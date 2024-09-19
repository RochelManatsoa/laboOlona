<?php

namespace App\Controller\Dashboard\Moderateur\OpenAi;

use App\Manager\OpenaiManager;
use App\Entity\Entreprise\JobListing;
use App\Entity\Prestation;
use App\Manager\CandidatManager;
use App\Service\OpenAITranslator;
use App\Service\PdfProcessor;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AnnonceController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private OpenaiManager $openaiManager,
        private CandidatManager $candidatManager,
        private OpenAITranslator $openAITranslator,
    ) {}
    
    #[Route('/api/openai/annonce/{id}', name: 'app_dashboard_moderateur_open_ai_annonce')]
    public function index(JobListing $annonce): Response
    {     
        return $this->json([
            'annonce' => $annonce
        ], 200, [], ['groups' => 'open_ai']);
    }
    
    #[Route('/api/openai/short-description/{id}', name: 'app_dashboard_moderateur_open_ai_short_description_annonce')]
    public function resume(Request $request, JobListing $annonce)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->em->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            // $parsePdf = $this->pdfProcessor->processPdf($candidat);
            $openai = $this->openAITranslator->metaDescription($annonce);
            [$short, $clean] = $this->openaiManager->extractCleanAndShortText($openai);
            // dd($metaDescription, $resumeCandidat, $tools, $technologies, $text, $json);

            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->em->getConnection()->connect();

            // Commencer une transaction
            $this->em->getConnection()->beginTransaction();
            
            // Mettre à jour l'entité jobListing
            $annonce->setShortDescription($short);
            $annonce->setCleanDescription($clean);
            $annonce->setIsGenerated(true);

            // Persister les modifications dans la base de données
            $this->em->persist($annonce);
            $this->em->flush();
            $this->em->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            // $this->em->getConnection()->rollBack();
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/api/openai/prestation/{id}', name: 'app_dashboard_moderateur_open_ai_short_description_prestation')]
    public function resumePrestation(Request $request, Prestation $prestation)
    {
        $this->em->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            $openai = $this->openAITranslator->resumePrestation($prestation);
            [$short, $clean] = $this->openaiManager->extractCleanAndShortText($openai);

            $this->em->getConnection()->connect();
            $this->em->getConnection()->beginTransaction();
            
            $prestation->setOpenai($short);
            $prestation->setCleanDescription($clean);
            $prestation->setIsGenerated(true);

            $this->em->persist($prestation);
            $this->em->flush();
            $this->em->getConnection()->commit(); 

            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);

        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }
    }
}
