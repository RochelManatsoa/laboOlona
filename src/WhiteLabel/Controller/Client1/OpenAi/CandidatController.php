<?php

namespace App\WhiteLabel\Controller\Client1\OpenAi;

use App\WhiteLabel\Entity\Client1\Candidate\Competences;
use Exception;
use App\WhiteLabel\Service\Client1\PdfProcessor;
use App\WhiteLabel\Manager\Client1\OpenaiManager;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Manager\Client1\CandidatManager;
use App\WhiteLabel\Service\Client1\OpenAITranslator;
use App\WhiteLabel\Service\Client1\UserService;
use App\WhiteLabel\Entity\Client1\Candidate\Experiences;
use App\WhiteLabel\Repository\Client1\Candidate\CompetencesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

class CandidatController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private UserService $userService,
        private OpenaiManager $openaiManager,
        private CandidatManager $candidatManager,
        private OpenAITranslator $openAITranslator,
        private PdfProcessor $pdfProcessor,
        private SluggerInterface $sluggerInterface,
        private CompetencesRepository $competencesRepository,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/assistant/openai/candidat/{id}', name: 'app_white_label_client1_open_ai_candidat')]
    public function index(CandidateProfile $candidat): Response
    {     
        return $this->json([
            'candidat' => $candidat
        ], 200, [], ['groups' => 'open_ai']);
    }
    
    #[Route('/assistant/ocr/{id}', name: 'app_white_label_client1_ocr_candidat')]
    public function ocrPdf(CandidateProfile $candidat)
    {
        $pdfText = $this->pdfProcessor->processPdf($candidat->getId());
        return $this->json(['text' => $pdfText], 200);
    }
    
    #[Route('/assistant/openai/generate/{id}', name: 'app_white_label_client1_open_ai_generate_candidat')]
    public function resume(Request $request, CandidateProfile $candidat)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->entityManager->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            $report = $this->openAITranslator->report($candidat);
            $json = $this->openaiManager->extractJsonAndText($report);
            $text = $json['frenchSummary'] ?? null;
            $traduction = $json['englishSummary'] ?? null;
            $metaDescription = $json['professionalSummary'];
            $resumeCandidat = $this->arrayToStringResume($json);
            $fullResume = $this->arrayToString($json);
            $tools = $this->arrayToString($json['tools']);
            $keywords = isset($json['keywords']) ? $json['keywords'] : null;
            $technologies = $this->arrayToString($json['technologies']);

            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->entityManager->getConnection()->connect();

            // Commencer une transaction
            $this->entityManager->getConnection()->beginTransaction();
            
            // Mettre à jour l'entité candidat
            $candidat->setTesseractResult($report);
            $candidat->setResultFree($text);
            $candidat->setResultPremium($fullResume);
            $candidat->setTraductionEn($traduction);
            $candidat->setMetaDescription($metaDescription);
            $candidat->setResumeCandidat($resumeCandidat);
            $candidat->setTools($tools);
            $candidat->setTechnologies($technologies);
            $candidat->setBadKeywords($keywords);
            $candidat->setIsGeneretated(true);

            // Persister les modifications dans la base de données
            $this->entityManager->persist($candidat);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            if ($e instanceof \Exception) {
                $errorMessage = $e->getMessage();
            } else {
                $errorMessage = 'Type d\'exception inattendu';
            }
            return $this->json([
                'status' => 'error', 
                'message' => 'Erreur lors de la génération du rapport par IA', 
                'error' => $errorMessage
            ], 500);
        }
    }
    
    #[Route('/assistant/openai/analyse/{id}', name: 'app_white_label_client1_open_ai_analyse_candidat')]
    public function analyse(Request $request, CandidateProfile $candidat)
    {
        $this->entityManager->getConnection()->close();
        try {
            /** Generate OpenAI analyse */
            $report = $this->openAITranslator->report($candidat);
            $json = $this->openaiManager->extractJsonAndText($report);
            $text = $json['frenchSummary'] ?? null;
            $traduction = $json['englishSummary'] ?? null;
            $metaDescription = $json['professionalSummary'];
            $resumeCandidat = $this->arrayToStringResume($json);
            $tools = $this->arrayToString($json['tools']);
            $technologies = $this->arrayToString($json['technologies']);
            $keywords = isset($json['keywords']) ? $json['keywords'] : null;
            $experiences = $json['experiences'];

            if (empty($metaDescription) || empty($text)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Infos manquants ou invalides.',
                    'error' => 'Infos manquants ou invalides pour le candidat ' . $candidat->getId()
                ], 500);
            }

            // Effacer les expériences actuelles si elles existent
            if (count($json['experiences']) > 0) {
                foreach ($candidat->getExperiences() as $existingExperience) {
                    $candidat->removeExperience($existingExperience);
                }
            }
            
            // Effacer les compétences actuelles si elles existent
            if (count($json['technologies']) > 0) {
                foreach ($candidat->getCompetences() as $existingCompetence) {
                    $candidat->removeCompetence($existingCompetence);
                }
            }
            
            foreach ($experiences as $key => $value) {
                // Passer à l'itération suivante si les dates de début et de fin ne sont pas fournies
                if (empty($value['dateStart']) && empty($value['dateEnd'])) {
                    continue;  // Continue avec la prochaine itération de la boucle
                }
            
                // Gérer le format de dateStart
                $dateStart = $this->getDateFromString($value['dateStart']);
            
                // Gérer dateEnd et vérifier si le candidat est toujours en poste
                $dateEnd = null;
                $enPoste = false;
                if (!empty($value['dateEnd'])) {
                    if ($value['dateEnd'] === "présent" || $value['dateEnd'] === "Aujourd’hui" ) {
                        $enPoste = true;
                    } else {
                        $dateEnd = $this->getDateFromString($value['dateEnd']);
                    }
                }
            
                if ($dateStart || $dateEnd) {
                    $experience = new Experiences();
                    $experience
                        ->setNom($value['title'])
                        ->setDescription($value['description'])
                        ->setDateDebut($dateStart)
                        ->setDateFin($dateEnd)
                        ->setEnPoste($enPoste)
                        ->setEntreprise($value['company']);
            
                    $candidat->addExperience($experience);
                }
            }

            foreach ($json['technologies'] as $key => $value) {            
                if (isset($value['name'])) {
                    $competence = new Competences();
                    $competence->setNom($value['name']);
                    $competence->setSlug($this->sluggerInterface->slug($value['name']));
                    $competence->setNote(1);
            
                    $candidat->addCompetence($competence);
                }
            }

            $this->entityManager->getConnection()->connect();
            $this->entityManager->getConnection()->beginTransaction();
            $candidat->setTesseractResult($report);
            $candidat->setResultFree($text);
            $candidat->setTraductionEn($traduction);
            $candidat->setMetaDescription($metaDescription);
            $candidat->setResumeCandidat($resumeCandidat);
            $candidat->setTools($tools);
            $candidat->setTechnologies($technologies);
            $candidat->setBadKeywords($keywords);
            $candidat->setIsGeneretated(true);

            $this->entityManager->persist($candidat);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit(); 

            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            // $this->entityManager->getConnection()->rollBack();
            if ($e instanceof \Exception) {
                $errorMessage = $e->getMessage();
            } else {
                $errorMessage = 'Type d\'exception inattendu';
            }
            return $this->json([
                'status' => 'error', 
                'message' => 'Erreur lors de la génération du rapport par IA', 
                'error' => $errorMessage
            ], 500);
        }
    }

    /**
     * Convert an array to a formatted string listing its key-value pairs.
     *
     * @param array $array The input array.
     * @return string A formatted string containing the list of key-value pairs in the array.
     */
    function arrayToString(array $array): string
    {
        $result = "";
        
        foreach ($array as $key => $value) {
            // Vérifier si la valeur est elle-même un tableau
            if (is_array($value)) {
                $result .= $this->arrayToStringWithIndent($value, 2);
            } else {
                $result .= "- $value\n";
            }
        }
        
        return $result;
    }
    
    function arrayToStringResume(array $array): string
    {
        $html = '';

        // Vérification de l'existence de 'strengthsAndWeaknesses'
        if (!empty($array['strengthsAndWeaknesses'])) {
            // Gestion des 'strengths'
            if (!empty($array['strengthsAndWeaknesses']['strengths'])) {
                $html .= '<p>Points forts :<br>' . $this->arrayToString((array)$array['strengthsAndWeaknesses']['strengths']) . '</p>';
            }

            // Gestion des 'weaknesses'
            if (!empty($array['strengthsAndWeaknesses']['weaknesses'])) {
                $html .= '<p>Points faibles :<br>' . $this->arrayToString((array)$array['strengthsAndWeaknesses']['weaknesses']) . '</p>';
            }
        }

        return $html;
    }

    function arrayToStringWithIndent(array $array, int $indentLevel): string
    {
        $result = "";
        $indent = str_repeat("  ", $indentLevel);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result .= $this->arrayToStringWithIndent($value, $indentLevel + 1);
            } else {
                $result .= "- $value<br>";
            }
        }
        
        return $result;
    }
    
    private function getDateFromString($dateStr) 
    {
        if (empty($dateStr)) {
            return null;
        }
    
        if (strtolower($dateStr) === 'present') {
            return new \DateTime(); 
        }
    
        if (strpos($dateStr, '-') !== false) {
            $parts = explode('-', $dateStr);
            if (count($parts) == 2) {
                if (strlen($parts[0]) === 4) { // Format YYYY-MM
                    return new DateTime($dateStr . '-01');  // Ajoutez "-01" pour compléter le jour
                } elseif (strlen($parts[1]) === 4) { // Format MM-YYYY
                    return new DateTime($parts[1] . '-' . $parts[0] . '-01');  // Inversez et ajoutez "-01" pour compléter le jour
                }
            }
        }
    
        try {
            return new DateTime($dateStr . '-01-01');  // Ajoutez "-01-01" pour compléter mois et jour
        } catch (Exception $e) {
            error_log('Date parsing failed for: ' . $dateStr . ' with error: ' . $e->getMessage());
            return null;
        }
    }
    
}

