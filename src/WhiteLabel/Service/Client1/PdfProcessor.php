<?php

namespace App\WhiteLabel\Service\Client1;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\WhiteLabel\Entity\Client1\CandidateProfile;

class PdfProcessor
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HttpClientInterface $httpClient,
        private FileUploader $fileUploader,
        EntityManagerInterface $entityManager
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
    }

    public function processPdf(int $candidatId)
    {
        $candidateProfile = $this->entityManager->getRepository(CandidateProfile::class)->find($candidatId);
        $pdfPath = $this->fileUploader->getTargetDirectory() . '/' . $candidateProfile->getCv();

        $boundary = '----WebKitFormBoundary' . md5(time());
        $body = "--$boundary\r\n" .
            "Content-Disposition: form-data; name=\"pdf\"; filename=\"" . basename($pdfPath) . "\"\r\n" .
            "Content-Type: application/pdf\r\n\r\n" .
            file_get_contents($pdfPath) . "\r\n" .
            "--$boundary--\r\n";

        $response = $this->httpClient->request('POST', 'https://technique.olona-talents.com/api/tesseract', [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => $body,
            'timeout' => 60,
        ]);

        return $response->getContent();
    }
}
