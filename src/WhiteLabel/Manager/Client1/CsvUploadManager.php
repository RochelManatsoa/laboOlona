<?php

namespace App\WhiteLabel\Manager\Client1;

use Symfony\Component\Uid\Uuid;
use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use App\WhiteLabel\Entity\Client1\Finance\Employe;
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
        
        $secteurs = [
            ['name' => 'Analyse et risque', 'slug' => 'analyse-et-risque'],
            ['name' => 'Animation commerciale', 'slug' => 'animation-commerciale'],
            ['name' => 'Audit', 'slug' => 'audit'],
            ['name' => 'Commerciale : Réseau et centre d\'affaires', 'slug' => 'commerciale-reseau-et-centre-d-affaires'],
            ['name' => 'Compliance', 'slug' => 'compliance'],
            ['name' => 'Contrôle', 'slug' => 'controle'],
            ['name' => 'Digitale', 'slug' => 'digitale'],
            ['name' => 'Finance et comptabilité', 'slug' => 'finance-et-comptabilite'],
            ['name' => 'Gestion de crédit', 'slug' => 'gestion-de-credit'],
            ['name' => 'Informatique', 'slug' => 'informatique'],
            ['name' => 'Juridique', 'slug' => 'juridique'],
            ['name' => 'Marketing et communication', 'slug' => 'marketing-et-communication'],
            ['name' => 'Monétique et transfert d\'argent', 'slug' => 'monetique-et-transfert-d-argent'],
            ['name' => 'Moyen généraux', 'slug' => 'moyen-generaux'],
            ['name' => 'Opérations (Locale et étrangère)', 'slug' => 'operations-locale-et-etrange'],
            ['name' => 'Organisation et qualité', 'slug' => 'organisation-et-qualite'],
            ['name' => 'Projet', 'slug' => 'projet'],
            ['name' => 'Recouvrement et contentieux', 'slug' => 'recouvrement-et-contentieux'],
            ['name' => 'Ressources humaines', 'slug' => 'ressources-humaines'],
            ['name' => 'Sécurité', 'slug' => 'securite'],
            ['name' => 'Trésorerie', 'slug' => 'tresorerie'],
        ]; 

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
            $randomKey = array_rand($secteurs);
            $randomSecteur = $secteurs[$randomKey];

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
            $profile->setTitre($data['poste']);
            $profile->setStatus(CandidateProfile::STATUS_PENDING);
            $profile->setIsValid(false);
            $profile->setCreatedAt(new \DateTime());
            $profile->setUid(Uuid::v4());
            $profile->setGender($data['genre']);
            $profile->setProvince($data['province']);
            $profile->setRegion($data['region']);
            $profile->setLocalisation('MG'); // localisation fixée à MG

            $user->setCandidateProfile($profile);

            $employe = new Employe();
            $employe->setDateEmbauche(new \DateTime());
            $employe->setNombreEnfants(0);
            $employe->setCategorie($randomSecteur['slug']);
            $employe->setUser($user);

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

    public function slug(string $text): string
    {
        return $this->sluggerInterface->slug($text);
    }
}