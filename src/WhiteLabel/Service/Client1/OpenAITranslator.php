<?php

namespace App\WhiteLabel\Service\Client1;

use App\Twig\AppExtension;
use App\WhiteLabel\Service\Client1\PdfProcessor;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Entity\Client1\Prestation;
use App\WhiteLabel\Manager\Client1\CandidatManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OpenAITranslator
{
    private string $projectDir;

    public function __construct(
        private HttpClientInterface $client,
        private AppExtension $appExtension,
        private CandidatManager $candidatManager,
        private PdfProcessor $pdfProcessor,
        private string $apiKey,
        ParameterBagInterface $params
    ) {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        $segments = $this->splitTextIntoSegments($text, 450);
        $translatedText = '';

        foreach ($segments as $segment) {
            try {
                $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ],
                    'json' => [
                        'model' => 'text-davinci-003',
                        'prompt' => "Please translate the following text from {$sourceLang} to {$targetLang}, but do not translate the HTML tags and their attributes: '{$segment}'",
                        'max_tokens' => 1024,
                    ],
                    'timeout' => 60,
                ]);

                $content = $response->toArray();
                $translatedSegment = $content['choices'][0]['text'] ?? '';
                $translatedText .= $translatedSegment;
            } catch (\Exception $e) {
                return 'Error: ' . $e->getMessage();
            }
        }

        return $translatedText;
    }

    public function translateCategory(string $text, string $sourceLang, string $targetLang): string
    {
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'text-davinci-003',
                    'prompt' => "Please translate the following text from {$sourceLang} to {$targetLang} : '{$text}'",
                    'max_tokens' => 1024,
                ],
                'timeout' => 60,
            ]);

            $content = $response->toArray();
            $translatedSegment = $content['choices'][0]['text'] ?? '';

            return $translatedSegment;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function generateDescription(string $text)
    {
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'text-davinci-003',
                    'prompt' => "Write in french a creative and informative description for an AI tools category named '{$text}':",
                    'max_tokens' => 1024,
                ],
                'timeout' => 60,
            ]);

            $content = $response->toArray();
            $description = $content['choices'][0]['text'] ?? '';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

        return $description;
    }

    private function splitTextIntoSegments($text, $maxTokens = 1024)
    {
        $segments = [];
        $currentSegment = '';
        $buffer = '';

        foreach (explode(' ', $text) as $word) {
            $buffer .= $word . ' ';
            if (strpos($word, '.') !== false || strpos($word, '</p>') !== false) {
                if ($this->appExtension->countTokens($currentSegment . $buffer) > $maxTokens) {
                    $segments[] = trim($currentSegment);
                    $currentSegment = $buffer;
                    $buffer = '';
                } else {
                    $currentSegment .= $buffer;
                    $buffer = '';
                }
            }
        }

        if (!empty(trim($currentSegment . $buffer))) {
            $segments[] = trim($currentSegment . $buffer);
        }

        return $segments;
    }

    public function trans($text)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/index.js';
        $nodePath = '/root/.nvm/versions/node/v18.17.0/bin/node';
        $command = sprintf('sudo %s %s %s %s 2>&1', escapeshellarg($nodePath), escapeshellarg($scriptPath), escapeshellarg($text), escapeshellarg($this->apiKey));

        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return implode("\n", $output);
        }

        return "Erreur lors de l'exécution du script index.js";
    }

    public function parse(CandidateProfile $candidateProfile)
    {
        $cv = $candidateProfile->getCv();
        if ($cv === null) {
            return 'CV manquant';
        }
        $scriptPath = $this->projectDir . '/assets/node_app/test1.js';
        $nodePath = '/root/.nvm/versions/node/v18.17.0/bin/node';
        $command = sprintf('sudo %s %s %s %s 2>&1', escapeshellarg($nodePath), escapeshellarg($scriptPath), escapeshellarg($candidateProfile->getCv()), escapeshellarg($this->apiKey));
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return implode("\n", $output);
        }

        return "Erreur lors de l'exécution du script parse.js";
    }

    public function report(CandidateProfile $candidateProfile)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/assistant.js';
        $nodePath = '/root/.nvm/versions/node/v18.17.0/bin/node';
        $command = sprintf('sudo %s %s %s %s %s 2>&1', escapeshellarg($nodePath), escapeshellarg($scriptPath), escapeshellarg($candidateProfile->getCv()), escapeshellarg($this->apiKey), escapeshellarg($candidateProfile->getId()));

        exec($command, $output, $return_var);
        if ($return_var === 0) {
            return implode("\n", $output);
        }

        return "Erreur lors de l'exécution du script assistant.js";
    }

    public function metaDescription(JobListing $annonce)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/shortdesc.js';
        $nodePath = '/root/.nvm/versions/node/v18.17.0/bin/node';
        $command = sprintf('sudo %s %s %s %s %s 2>&1', escapeshellarg($nodePath), escapeshellarg($scriptPath), escapeshellarg($annonce->getDescription()), escapeshellarg($this->apiKey), escapeshellarg($annonce->getId()));

        exec($command, $output, $return_var);
        if ($return_var === 0) {
            return implode("\n", $output);
        }

        return "Erreur lors de l'exécution du script shortdesc.js";
    }

    public function resumePrestation(Prestation $prestation)
    {
        $scriptPath = $this->projectDir . '/assets/node_app/prestation.js';
        $nodePath = '/root/.nvm/versions/node/v18.17.0/bin/node';
        $command = sprintf('sudo %s %s %s %s %s 2>&1', escapeshellarg($nodePath), escapeshellarg($scriptPath), escapeshellarg($prestation->getDescription()), escapeshellarg($this->apiKey), escapeshellarg($prestation->getTitre()));

        exec($command, $output, $return_var);
        if ($return_var === 0) {
            return implode("\n", $output);
        }

        return "Erreur lors de l'exécution du script prestation.js";
    }
}
