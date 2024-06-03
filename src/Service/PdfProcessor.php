<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CandidateProfile;

class PdfProcessor
{
    private $httpClient;
    private $entityManager;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
    }

    public function processPdf(string $pdfPath, CandidateProfile $candidateProfile): void
    {
        
        // Effectuer la requête curl pour envoyer le fichier PDF
        $boundary = '----WebKitFormBoundary'.md5(time());
        $body = "--$boundary\r\n".
                "Content-Disposition: form-data; name=\"pdf\"; filename=\"".basename($pdfPath)."\"\r\n".
                "Content-Type: application/pdf\r\n\r\n".
                file_get_contents($pdfPath)."\r\n".
                "--$boundary--\r\n";

        $response = $this->httpClient->request('POST', 'https://technique.olona-talents.com/api/tesseract', [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary='.$boundary,
            ],
            'body' => $body,
        ]);

        // Récupérer le contenu de la réponse
        $content = $response->getContent();

        // Stocker la réponse dans l'entité CandidateProfile
        $candidateProfile->setTesseractResult($content);

        // Sauvegarder les modifications dans la base de données
        $this->entityManager->persist($candidateProfile);
        $this->entityManager->flush();
    }
}
