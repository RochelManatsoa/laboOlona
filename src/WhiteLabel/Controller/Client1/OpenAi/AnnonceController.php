<?php

namespace App\WhiteLabel\Controller\Client1\OpenAi;

use App\WhiteLabel\Manager\Client1\OpenaiManager;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\Entity\Prestation;
use App\WhiteLabel\Manager\Client1\CandidatManager;
use App\WhiteLabel\Service\Client1\OpenAITranslator;
use App\WhiteLabel\Service\Client1\PdfProcessor;
use App\WhiteLabel\Service\Client1\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AnnonceController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private UserService $userService,
        private OpenaiManager $openaiManager,
        private CandidatManager $candidatManager,
        private OpenAITranslator $openAITranslator,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/api/openai/annonce/{id}', name: 'app_white_label_client1_open_ai_annonce')]
    public function index(JobListing $annonce): Response
    {     
        return $this->json([
            'annonce' => $annonce
        ], 200, [], ['groups' => 'open_ai']);
    }
    
    #[Route('/api/openai/short-description/{id}', name: 'app_white_label_client1_open_ai_short_description_annonce')]
    public function resume(Request $request, JobListing $annonce)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->entityManager->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            $openai = $this->openAITranslator->metaDescription($annonce);
            [$short, $clean] = $this->openaiManager->extractCleanAndShortText($openai);
            // dd($metaDescription, $resumeCandidat, $tools, $technologies, $text, $json);

            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->entityManager->getConnection()->connect();

            // Commencer une transaction
            $this->entityManager->getConnection()->beginTransaction();
            
            // Mettre à jour l'entité jobListing
            $annonce->setShortDescription($short);
            $annonce->setCleanDescription($clean);
            $annonce->setIsGenerated(true);

            // Persister les modifications dans la base de données
            $this->entityManager->persist($annonce);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            // $this->entityManager->getConnection()->rollBack();
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/api/openai/prestation/{id}', name: 'app_white_label_client1_open_ai_short_description_prestation')]
    public function resumePrestation(Request $request, Prestation $prestation)
    {
        $this->entityManager->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            $openai = $this->openAITranslator->resumePrestation($prestation);
            $json = $this->openaiManager->extractJsonAndText($openai);

            $this->entityManager->getConnection()->connect();
            $this->entityManager->getConnection()->beginTransaction();
            
            $prestation->setOpenai($json['shortDescription']);
            $prestation->setCleanDescription($json['cleanDescription']);
            $prestation->setIsGenerated(true);

            $this->entityManager->persist($prestation);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit(); 

            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);

        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }
    }
}

