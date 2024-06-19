<?php

namespace App\Manager;

class OpenaiManager
{
    public function extractProfessionalSummary(string $text): string
    {
        $pattern = '/Résumé Professionnel\s*(.*?)\s*Expériences Professionnelles/s';
        preg_match($pattern, $text, $matches);

        // If a match is found, clean up the result
        if (isset($matches[1])) {
            // Extract the text between "Résumé Professionnel" and "Expérience Professionnelle"
            $summary = trim($matches[1]);

            // Remove the first character if it is a colon ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            // Remove all dashes '-'
            $summary = str_replace('-', '', $summary);

            return $summary;
        }

        return '';
    }

    public function extractResumeCandidate(string $text): string
    {
        $pattern = '/Points forts et points faibles\s*(.*?)\s*Autres Informations/s';
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
}