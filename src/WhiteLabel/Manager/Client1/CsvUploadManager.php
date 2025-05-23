<?php

namespace App\WhiteLabel\Manager\Client1;

use Symfony\Component\Uid\Uuid;
use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\WhiteLabel\Repository\Client1\CandidateProfileRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CsvUploadManager
{
    public function __construct(
        private SluggerInterface $sluggerInterface,
        private EntityManagerInterface $entityManager,
        private CandidateProfileRepository $candidateProfileRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
    ){}

    
    public function importCsvToDatabase(string $filePath): void
    {
        set_time_limit(0);
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Fichier CSV introuvable : $filePath");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier : $filePath");
        }

        $headers = fgetcsv($handle, 1000, ',');
        $count = 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data = array_combine($headers, $row);

            // Vérifie si un utilisateur avec cet email existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                continue; // Ignorer si email déjà existant
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setNom($data['nom']);
            $user->setPrenom($data['prenom']);
            $user->setTelephone($data['telephone']);
            $user->setAdress($data['adresse']);
            $user->setCity($data['ville']);
            $user->setPostalCode($data['code_postale']);
            $user->setType(User::ACCOUNT_CANDIDAT);
            $user->setRoles(['ROLE_CANDIDAT']);
            $user->setIsVerified(false);
            $user->setDateInscription(new \DateTime());

            // Hash du mot de passe '000000'
            $hashedPassword = $this->passwordHasher->hashPassword($user, '000000');
            $user->setPassword($hashedPassword);

            $profile = new CandidateProfile();
            $profile->setCandidat($user);
            $profile->setResume($data['biographie']);
            $profile->setStatus(CandidateProfile::STATUS_PENDING);
            $profile->setIsValid(false);
            $profile->setCreatedAt(new \DateTime());
            $profile->setUid(Uuid::v4());
            $profile->setGender($data['genre']);
            $profile->setProvince($data['province']);
            $profile->setRegion($data['region']);
            $profile->setLocalisation('MG'); // localisation fixée à MG

            $user->setCandidateProfile($profile);

            // Valider les données
            $errors = $this->validator->validate($user);
            if (count($errors) === 0) {
                $this->entityManager->persist($user);
                $count++;
            }
        }

        fclose($handle);
        $this->entityManager->flush();
    }

    private function transformToHtml(string $text): string
    {
        // Convert the text to HTML
        $html = '<div>';

        // Split sections based on headers
        $sections = preg_split('/(\r\n|\r|\n){2,}/', $text);
        foreach ($sections as $section) {
            // Split each section into lines
            $lines = preg_split('/(\r\n|\r|\n)/', $section);

            if (count($lines) > 1) {
                // First line as header
                $html .= '<h3>' . htmlspecialchars(array_shift($lines)) . '</h3>';
                // Remaining lines as list items
                $html .= '<ul>';
                foreach ($lines as $line) {
                    // Remove leading dash and space if present
                    $line = preg_replace('/^\s*-\s*/', '', $line);
                    $html .= '<li>' . htmlspecialchars($line) . '</li>';
                }
                $html .= '</ul>';
            } else {
                // Single line section
                $html .= '<p>' . htmlspecialchars($section) . '</p>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    private function extractProfessionalSummary(string $text): string
    {
        // Match the text after "Résumé Professionnel"
        $pattern = '/Résumé Professionnel\s*(.*)/s';
        preg_match($pattern, $text, $matches);

        // If a match is found, clean up the result
        if (isset($matches[1])) {
            // Extract the text after "Résumé Professionnel"
            $summary = trim($matches[1]);

            // Remove the first character if it is a colon ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            // Remove all dashes '-'
            $summary = str_replace('-', '', $summary);

            // Split the text into words and get the first 100 words
            $words = preg_split('/\s+/', $summary);
            $first100Words = array_slice($words, 0, 30);

            // Join the first 100 words back into a string
            $summary = implode(' ', $first100Words);

            return $summary;
        }

        // Return an empty string if no match is found
        return '';
    }

    public function slug(string $text): string
    {
        return $this->sluggerInterface->slug($text);
    }
}