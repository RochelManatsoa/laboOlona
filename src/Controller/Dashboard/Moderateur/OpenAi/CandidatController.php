<?php

namespace App\Controller\Dashboard\Moderateur\OpenAi;

use App\Entity\Candidate\Competences;
use DateTime;
use App\Service\PdfProcessor;
use App\Manager\OpenaiManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\OpenAITranslator;
use App\Service\User\UserService;
use App\Entity\Candidate\Experiences;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private OpenaiManager $openaiManager,
        private CandidatManager $candidatManager,
        private OpenAITranslator $openAITranslator,
        private PdfProcessor $pdfProcessor,
    ) {}
    
    #[Route('/api/openai/candidat/{id}', name: 'app_dashboard_moderateur_open_ai_candidat')]
    public function index(CandidateProfile $candidat): Response
    {     
        return $this->json([
            'candidat' => $candidat
        ], 200, [], ['groups' => 'open_ai']);
    }
    
    #[Route('/api/ocr/{id}', name: 'app_dashboard_moderateur_ocr_candidat')]
    public function ocrPdf(CandidateProfile $candidat)
    {
        $pdfText = $this->pdfProcessor->processPdf($candidat->getId());
        return $this->json(['text' => $pdfText], 200);
    }
    
    #[Route('/api/openai/generate/{id}', name: 'app_dashboard_moderateur_open_ai_generate_candidat')]
    public function resume(Request $request, CandidateProfile $candidat)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->em->getConnection()->close();
        try {
            /** Generate OpenAI resume */
            // $parsePdf = $this->pdfProcessor->processPdf($candidat);
            $parsePdf = $this->openAITranslator->parse($candidat);
            $report = $this->openAITranslator->report($candidat);
            [$text, $json] = $this->openaiManager->extractJsonAndText($report);
            $traduction = $this->openAITranslator->trans($text);
            $metaDescription = $json['professionalSummary'];
            $resumeCandidat = $this->arrayToStringResume($json);
            $fullResume = $this->arrayToString($json);
            $tools = $this->arrayToString($json['tools']);
            $keywords = $json['keywords'];
            $technologies = $this->arrayToString($json['technologies']);
            // dd($metaDescription, $resumeCandidat, $tools, $technologies, $text, $json);

            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->em->getConnection()->connect();

            // Commencer une transaction
            $this->em->getConnection()->beginTransaction();
            
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
            $this->em->persist($candidat);
            $this->em->flush();
            $this->em->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            // $this->em->getConnection()->rollBack();
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/api/openai/analyse/{id}', name: 'app_dashboard_moderateur_open_ai_analyse_candidat')]
    public function analyse(Request $request, CandidateProfile $candidat)
    {
        // Fermer la connexion à la base de données avant d'exécuter les scripts Node.js
        $this->em->getConnection()->close();
        try {
            /** Generate OpenAI analyse */
            // $parsePdf = $this->pdfProcessor->processPdf($candidat);
            $parsePdf = $this->openAITranslator->parse($candidat);
            $report = $this->openAITranslator->report($candidat);
            [$text, $json] = $this->openaiManager->extractJsonAndText($report);
            $traduction = $this->openAITranslator->trans($text);
            $metaDescription = $json['professionalSummary'];
            $resumeCandidat = $this->arrayToStringResume($json);
            $tools = $this->arrayToString($json['tools']);
            $technologies = $this->arrayToString($json['technologies']);
            $keywords = $json['keywords'];
            $experiences = $json['experiences'];
            
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
            
                // Création de l'objet expérience et ajout au candidat
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
            
            
            // Rouvrir la connexion à la base de données après l'exécution des scripts
            $this->em->getConnection()->connect();

            // Commencer une transaction
            $this->em->getConnection()->beginTransaction();
            
            // Mettre à jour l'entité candidat
            $candidat->setTesseractResult($report);
            $candidat->setResultFree($text);
            $candidat->setTraductionEn($traduction);
            $candidat->setMetaDescription($metaDescription);
            $candidat->setResumeCandidat($resumeCandidat);
            $candidat->setTools($tools);
            $candidat->setTechnologies($technologies);
            $candidat->setBadKeywords($keywords);
            $candidat->setIsGeneretated(true);

            // Persister les modifications dans la base de données
            $this->em->persist($candidat);
            $this->em->flush();
            $this->em->getConnection()->commit(); // Confirmer la transaction

            // Utiliser un retour JSON pour transmettre le message de succès
            return $this->json(['status' => 'success', 'message' => 'Rapport généré par IA sauvegardé']);
        } catch (\Exception $e) {
            // $this->em->getConnection()->rollBack();
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la génération du rapport par IA', 'error' => $e->getMessage()], 500);
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
        $html = "";
    
        if (isset($array['strengthsAndWeaknesses'])) {
            if (isset($array['strengthsAndWeaknesses']['strengths'])) {
                if (is_array($array['strengthsAndWeaknesses']['strengths'])) {
                    $html .= "<p>Points forts : <br>" . $this->arrayToString($array['strengthsAndWeaknesses']['strengths']) . "</p>";
                }else{
                    $html .= "<p>Points forts : <br>" . htmlspecialchars($array['strengthsAndWeaknesses']['strengths']) . "</p>";
                }
            }
            
            if (isset($array['strengthsAndWeaknesses']['weaknesses'])) {
                if (is_array($array['strengthsAndWeaknesses']['weaknesses'])) {
                    $html .= "<p>Points faibles : <br>" . $this->arrayToString($array['strengthsAndWeaknesses']['weaknesses']) . "</p>";
                }else{
                    $html .= "<p>Points faibles : <br>" . htmlspecialchars($array['strengthsAndWeaknesses']['weaknesses']) . "</p>";
                }
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
            
    private function getDateFromString($dateStr) {
        if (empty($dateStr)) {
            return null;
        }
    
        // Détecter le format et créer la date
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
    
        // Format YYYY
        return new DateTime($dateStr . '-01-01');  // Ajoutez "-01-01" pour compléter mois et jour
    }
}
