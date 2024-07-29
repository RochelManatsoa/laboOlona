<?php

namespace App\Manager;

class OpenaiManager
{
    public function extractProfessionalSummary(string $text): string
    {
        // Utilisation d'un pattern regex flexible qui capture les titres en différents formats et avec différents nombres d'astérisques ou dièses
        $pattern = '/(\*{2,3}\s*Résumé Professionnel\s*\*{2,3}|#{2,3}\s*Résumé Professionnel\s*#{2,3}|Résumé Professionnel):\s*(.*?)\s*(\*{2,3}\s*Expérience[s]? Professionnelle[s]?\s*\*{2,3}|#{2,3}\s*Expérience[s]? Professionnelle[s]?\s*#{2,3}|Expérience[s]? Professionnelle[s]?):/s';
        preg_match($pattern, $text, $matches);

        // Si une correspondance est trouvée, nettoyer le résultat
        if (isset($matches[2])) {
            $summary = trim($matches[2]);

            // Suppression du premier caractère si c'est un deux-points ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            // Suppression de tous les traits d'union '-'
            $summary = str_replace('-', '', $summary);

            return $summary;
        }

        return '';
    }

    public function extractResumeCandidate(string $text): string
    {
        // Utilisation d'un pattern regex flexible qui capture les titres en différents formats et avec différents nombres d'astérisques ou dièses
        $pattern = '/(\*{2,3}\s*Forces\s*et\s*Faiblesses\s*\*{2,3}|\*{2,3}\s*Forces\s*\*{2,3}|#{2,3}\s*Forces\s*et\s*Faiblesses\s*#{2,3}|#{2,3}\s*Forces\s*#{2,3}|Forces et Faiblesses|Forces):\s*(.*?)\s*(\*{2,3}\s*Faiblesses\s*\*{2,3}|#{2,3}\s*Faiblesses\s*#{2,3}|\*{2,3}\s*Autres Informations\s*\*{2,3}|#{2,3}\s*Autres Informations\s*#{2,3}|Faiblesses|Autres Informations)/s';
        preg_match($pattern, $text, $matches);

        // Si une correspondance est trouvée, nettoyer le résultat
        if (isset($matches[2])) {
            $summary = trim($matches[2]);

            // Suppression du premier caractère si c'est un deux-points ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            return $summary;
        }

        return '';
    }


    public function extractCandidateTools(string $text): string
    {
        $pattern = '/Outils\s*(.*?)\s*Langages/s';
        preg_match($pattern, $text, $matches);

        // If a match is found, clean up the result
        if (isset($matches[1])) {
            // Extract the text between "Résumé Professionnel" and "Expérience Professionnelle"
            $summary = trim($matches[1]);

            // Remove the first character if it is a colon ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            return $summary;
        }

        return '';
    }
    
    public function extractJsonAndText(string $text): array
    {
        $delimiter = "```json";
        $parts = explode($delimiter, $text);
    
        $formattedText = trim($parts[0]);
        $jsonString = trim($parts[1], " \t\n\r\0\x0B`");
        $jsonData = json_decode($jsonString, true);
    
        return [
            $formattedText,
            $jsonData
        ];
    }

    public function extractCleanAndShortText(string $text): array
    {
        $delimiter = "####";
        $parts = explode($delimiter, $text);
        
        $cleanDescription = trim($parts[1]);
        $shortDescription = trim($parts[2]);
        
        return [
            $shortDescription,
            $cleanDescription,
        ];
    }
}