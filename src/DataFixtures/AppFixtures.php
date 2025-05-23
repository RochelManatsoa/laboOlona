<?php

namespace App\DataFixtures;

use DateTime;
use Faker\Factory;
use App\Entity\User;
use App\Entity\Langue;
use App\Entity\Secteur;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Candidate\Langages;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use App\Entity\Entreprise\JobListing;
use App\Entity\Candidate\Applications;
use App\Entity\Vues\AnnonceVues;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserPasswordHasherInterface $encoder,
        private SluggerInterface $sluggerInterface,
    ){
    }

    public function load(ObjectManager $manager): void
    {
        // create 20 products! Bam!
        $faker = Factory::create('fr_FR');

        $job = [
            'Développeur mobile',
            'Développeur web',
            'Administrateur réseau',
            'Consultant SEO',
            'Graphiste',
            'Monteur vidéo',
            'Rédacteur web',
            'Community manager',
            'Assistant virtuel',
            'Traducteur',
            'Correcteur',
            'Développeur full stack',
        ];


        $jobdesc = [
            "Hello !

            On recherche un freelance pour automatiser la création de comptes Fruitz (application de rencontre) avec l'émulateur BlueStacks.
            
            L'objectif est d'avoir (assez rapidement) une application exécutable en local sur Windows. Nous pensons donc nécessaire de créer l'app avec plusieurs langages de codage (Python, Node, et C++)
            
            L'UI/UX devra être user-friendly, sans pour autant être jolie, nous recherchons l'efficacité avant tout. Egalement, il sera nécessaire de pouvoir faire plusieurs espaces utilisateurs, car nous avons plusieurs profils Fruitz différent à créer.
            
            Le principe est de cloner une instance BlueStacks qui servira de modèle pour les suivantes. On t'apportera plus de précision lors d'un call :)

            
            A très vite !",
            "Pour une plateforme de restitution d'un projet européen, notre association a fait le choix de l'outil YesWiki. Nous avons construit la majorité du site (arborescences, principales pages, structure des données, etc.) et cherchons désormais :
                - un prestataire (prestations 1 et 2) pour réaliser le webdesign du site (et la création d'éléments visuels de types pictogrammes),
                - un prestataire (prestations 3) pour réaliser l'intégration sous YesWiki.
                Il peut s'agir d'une équipe constituée ou de deux prestataires indépendants.",
            "- Prestation 1 : webdesign de la plateforme avec livraison des fichiers déclinés sur la base de 4 écrans types (livrables : design des interfaces en format vectoriel et ensemble des informations nécessaires pour l’intégrateur, cf. prestation 3)
            - Prestation 2 : déclinaison d'éléments d'illustration complémentaires, cohérents avec l'esthétique générale du site.
            - Prestation 3 : Intégration du design sur le gestionnaire de contenus YesWiki
            
            Dates de réalisations :
            - Date de livraison prestation 1 : 10/11/23
            - Date de livraison prestation 2 : 17/11/23
            - Date de livraison prestation 3 : 01/12/23
            
            Un cahier des charges détaillé et ses annexes est joint pour détailler nos attentes.
            
            Merci de nous transmettre vos devis détaillés (avec références, portfolio et éléments techniques) par email ou via la plateforme.",
            "Nous recherchons un freelance expert dev Wordpress/elementor/woo-commerce pour reprendre un site Wordpress (Elementor, Woo-commerce, 50 plugins, 5000 références produits, comptes clients, factures, admin fabricant différent de l'admin webmaster):",
            "Aujourd'hui, il semble périlleux de:
            - le mettre à jour (ou mettre à jour chaque plug-in)
            - Ré-upload la base de données produits avec des modifications de ces produits (en bulk) ou par exemple, ajout d'une deuxième et troisième photos pour l'ensemble des produits
            - Conserver des performances top (rapidité & stabilité) dans le temps avec ajout de plus de références et création de nombreux comptes clients
            - Mettre à jour le serveur selon une manoeuvre demandée par OVH, sans risquer de casser les plug-ins qui ne s'adapteraient pas à cete nouvelle version du serveur...",
            "Nous souhaitons donc le reprendre, sans repayer un site complet (celui ci nous a couté déjà 10k euros et il est impossible de remettre la moitié de ce budget pour la suite)...
            Cela signifie remplacer une partie des plugins par une fonctionnalité reprenant ces derniers, et ainsi assurer la maintenance possible et la rapidité et sécurité du site web dans le temps.",
            "Nous cherchons quelqu'un de disponible, qui comprendra vraiment le site, ses fonctionnalités, le projet et la sensibilité de ce site afin de tester sur un serveur bis un site copié (amélioré progressivement) puis migrer sans aucune instabilité le travail sur le serveur live afin de pouvoir améliorer ce site sans compromettre les ventes qui ont lieu chaque jour dessus, ni perdre de la data.",
            "Nous avons, à dispo une fois votre intérêt et expertise démontrés, un cahier des charges détaillé de toutes les fonctionnalités principales du site. Je serai aussi ravie de faire connaissance au tel ou en visio pour un tel projet, afin de nous assurer du fit.

            Pour info, la refonte est tout récente et le site en lui-même (design, usage, fonctionnalités) doivent rester intactes..",
        ];


        $experiencesArray = [
            // IT - Développement
            "Développeur Web Full-Stack",
            "Ingénieur Logiciel Backend Java",
            "Architecte Cloud AWS",
            "Data Scientist en Machine Learning",
        
            // Marketing Digital
            "Chef de Projet SEO",
            "Spécialiste en Publicité Facebook Ads",
            "Content Manager pour Blog Tech",
            "Expert en Email Marketing",
        
            // Commercial
            "Responsable Commercial B2B",
            "Attaché Commercial Région Sud",
            "Négociateur en Vente de Solutions IT",
            "Gestionnaire de Comptes Clés",
        
            // Recrutement
            "Consultant en Recrutement Tech",
            "Talent Acquisition Manager",
            "Chasseur de Têtes pour Profils Rares",
            "Coordinateur de Recrutement RH",
        
            // RH - Administration
            "Gestionnaire RH et Paie",
            "Coordinateur de Formation Continue",
            "Responsable des Avantages Sociaux",
            "Spécialiste en Relations du Travail"
        ];

        $s = [
            0 => [
                'name' => 'IT - Devéloppement',
                'slug' => 'it-developpement',
                'competences' => [
                    'Programmation',
                    'Maîtrise des systèmes de gestion de bases de données',
                    'Développement Front-end ',
                    'Conception et architecture logicielle',
                    'Intégration continue et déploiement continu (CI/CD)',
                    'Développement mobile (e.g., Android, iOS)',
                ],
            ],
            1 => [
                'name' => 'Marketing Digital',
                'slug' => 'marketing-digital',
                'competences' => [
                    'SEO (Optimisation pour les moteurs de recherche)',
                    'SEM (Marketing sur les moteurs de recherche)',
                    'Marketing sur les réseaux sociaux',
                    'Analyse de données et KPIs',
                    'Content marketing',
                    'E-mail marketing',
                ],
            ],
            2 => [
                'name' => 'Commercial',
                'slug' => 'commercial',
                'competences' => [
                    'Techniques de vente',
                    'Négociation commerciale',
                    'Gestion de la relation client (CRM)',
                    'Prospection commerciale',
                    'Connaissance des produits/services de l\'entreprise',
                    'Stratégie de vente et marketing',
                    'Analyse des besoins du client',
                ],
            ],
            3 => [
                'name' => 'Recrutement',
                'slug' => 'recrutement',
                'competences' => [
                    'Sourcing de candidats',
                    'Entretiens d\'embauche',
                    'Évaluation et sélection des candidats',
                    'Connaissance des outils de recrutement (e.g., LinkedIn, plateformes d\'offres d\'emploi)',
                    'Conception et rédaction d\'offres d\'emploi',
                    'Législation du travail et réglementation de l\'embauche',
                    'Gestion des relations avec les agences de recrutement',
                ],
            ],
            4 => [
                'name' => 'RH - Administration',
                'slug' => 'rh-administration',
                'competences' => [
                    'Gestion administrative du personnel',
                    'Gestion des paies',
                    'Connaissance de la législation du travail',
                    'Formation et développement des employés',
                    'Gestion des conflits et médiation',
                    'Évaluation des performances',
                    'Gestion des avantages et rémunérations',
                ],
            ],
            5 => [
                'name' => 'Finance',
                'slug' => 'finance',
                'competences' => [
                    'Analyse financière',
                    'Comptabilité',
                    'Gestion de trésorerie',
                    'Planification financière et budgétisation',
                    'Connaissance des réglementations financières',
                    'Évaluation d\'investissement',
                    'Gestion des risques financiers',
                ],
            ],
            6 => [
                'name' => 'Construction',
                'slug' => 'construction',
                'competences' => [
                    'Gestion de projet de construction',
                    'Connaissance des matériaux de construction',
                    'Lecture et interprétation des plans et schémas',
                    'Sécurité sur les chantiers',
                    'Connaissance des réglementations de construction',
                    'Estimation des coûts de construction',
                    'Gestion d\'équipe sur chantier',
                ],
            ],
            7 => [
                'name' => 'Immobilier',
                'slug' => 'immobilier',
                'competences' => [
                    'Estimation de la valeur des biens',
                    'Connaissance du marché immobilier local',
                    'Techniques de négociation immobilière',
                    'Réglementation et droit immobilier',
                    'Gestion de portefeuille de biens',
                    'Marketing et promotion immobilière',
                    'Gestion locative et relation avec les locataires',
                ],
            ],
            8 => [
                'name' => 'Transport et logistique',
                'slug' => 'transport-et-logistique',
                'competences' => [
                    'Planification et optimisation des itinéraires',
                    'Gestion de flotte et entretien des véhicules',
                    'Connaissance des réglementations de transport',
                    'Gestion des stocks et entreposage',
                    'Suivi et optimisation de la chaîne d\'approvisionnement',
                    'Logistique inverse (retours et échanges)',
                    'Sécurité et conformité dans le transport',
                ],
            ],
            9 => [
                'name' => 'Éducation',
                'slug' => 'education',
                'competences' => [
                    'Pédagogie',
                    'Conception de programmes d\'études',
                    'Technologies éducatives',
                    'Gestion de classe',
                    'Évaluation des étudiants',
                    'Formation continue et développement professionnel',
                ],
            ],
        ];

        $a = [
            0 => [
                'name' => 'Entreprise',
                'slug' => 'ressource'
            ],
            1 => [
                'name' => 'Expert',
                'slug' => 'expert'
            ]
        ];

        $l = [
            0 => [
                'name' => 'English',
                'slug' => 'english',
                'code' => 'gb',
            ],
            1 => [
                'name' => 'Русский',
                'slug' => 'russian',
                'code' => 'rs',
            ],
            2 => [
                'name' => 'Français',
                'slug' => 'francais',
                'code' => 'fr',
            ],
            3 => [
                'name' => 'Español',
                'slug' => 'espagnole',
                'code' => 'es',
            ],
            4 => [
                'name' => 'Deutsch',
                'slug' => 'deutsch',
                'code' => 'de',
            ],
        ];

        $statusJob = [
            'PUBLISHED',
            'PUBLISHED',
            'PUBLISHED',
            'PUBLISHED',
            'PUBLISHED',
            'PUBLISHED',
            'PUBLISHED',
            'PENDING',
            'EXPIRED',
            'ARCHIVED',
            'FEATURED',
            'RESERVED',
        ];

        $status = ['OPEN', 'CLOSED', 'FILLED'];
        $statusCandidature = ['PENDING', 'ACCEPTED', 'REFUSED'];
        $typeContrat = [
            "CDI",
            "CDD",
            "STAGE",
            "ALTERNANCE",
        ];

        $langs = [];
        foreach ($l as $key => $value) {
            $language = new Langue();
            $language
                ->setNom($value['name'])
                ->setSlug($value['slug'])
                ->setCode($value['code']);
            $manager->persist($language);
            $langs[] = $language;
        }

        $languages = [];
        for($i=0; $i<10; $i++){
            $la = new Langages();
            $ll = $faker->randomElement($langs);
            $la
            ->setNiveau($faker->numberBetween(1, 5))
            ->setLangue($ll)
            ->setTitre($ll->getNom())
            ->setCode($ll->getCode())
            ;
            $manager-> persist($la);
            $languages[] = $la;
        }

        $sectors = [];
        $technicalskills = [];
        foreach ($s as $key => $value) {
            $sector = new Secteur();
            $sector
                ->setNom($value['name'])
                ->setSlug($value['slug']);

                foreach ($value['competences'] as $competence) {
                    $skill = new Competences();
                    $skill
                        ->setNom($competence)
                        ->setSlug($this->sluggerInterface->slug($competence));

                    $manager->persist($skill);
                    $technicalskills[] = $skill;
                }

            $manager->persist($sector);
            $sectors[] = $sector;
        }

        $user = new User();
        $plainPassword = '000000';
        $user->setNom('Client')
            ->setPrenom('Olona')
            ->setDateInscription(new DateTime())
            ->setType(User::ACCOUNT_MODERATEUR)
            ->setemail('moderateur@gmail.com')
            ->setPassword($this->encoder->hashPassword($user, $plainPassword));

        $moderateur = new ModerateurProfile();
        $moderateur->setModerateur($user);
        $manager->persist($user);
        $manager->persist($moderateur);


        $entreprises = [];

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $plainPassword = '000000';
            $user->setNom($faker->lastName)
                ->setPrenom($faker->firstName)
                ->setDateInscription(new DateTime())
                ->setType(User::ACCOUNT_ENTREPRISE)
                ->setEmail($faker->email)
                ->setPassword($this->encoder->hashPassword($user, $plainPassword));

            $entreprise = new EntrepriseProfile();
            $entreprise->setEntreprise($user)
                ->setNom($faker->company())
                ->setTaille('SM')
                ->setDescription($faker->paragraph(4))
                ->setLocalisation($faker->countryCode())
                ->setSiteWeb('http://olona-talents.com');
                $manager->persist($user);
                $manager->persist($entreprise);

            $entreprises[] = $entreprise;
        }

        $postingViews = [];

        for ($i = 0; $i < 20; $i++) {
            $ip = rand(1, 254) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
            $view = new AnnonceVues();
            $view->setIdAdress($ip);
            $manager->persist($view);

            $postingViews[] = $view;
        }

        $jobListings = [];

        for ($i = 0; $i < 50; $i++) {
            $jobListing = new JobListing();
            $jobListing->setTitre($faker->randomElement($job));
            $jobListing->setDescription($faker->randomElement($jobdesc));
            $jobListing->setDateCreation($faker->dateTime());
            $jobListing->setDateExpiration($faker->dateTime());
            $jobListing->setJobId(new Uuid(Uuid::v1()));
            $jobListing->setStatus($faker->randomElement($statusJob));
            $jobListing->setSecteur($faker->randomElement($sectors));
            $jobListing->setTypeContrat($faker->randomElement($typeContrat));
            $jobListing->addAnnonceVue($faker->randomElement($postingViews));
            $jobListing->addAnnonceVue($faker->randomElement($postingViews));
            $jobListing->addAnnonceVue($faker->randomElement($postingViews));
            $jobListing->addAnnonceVue($faker->randomElement($postingViews));
            $jobListing->setSalaire(200.00);
            $jobListing->setEntreprise($faker->randomElement($entreprises));
            $manager->persist($jobListing);

            $jobListings[] = $jobListing;
        }

        $experiences = [];
        foreach ($experiencesArray as $key => $value) {
            $experience = new Experiences();
            $experience
                ->setNom($value)
                ->setEntreprise($faker->company())
                ->setEnPoste($faker->boolean())
                ->setDateDebut($faker->dateTime())
                ->setDateFin($faker->dateTime())
                ->setDescription($faker->paragraph(4));

            $manager->persist($experience);
            $experiences[] = $experience;
        }

        $candidats = [];

        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $plainPassword = '000000';
            $user->setNom($faker->lastName)
                ->setPrenom($faker->firstName)
                ->setDateInscription(new DateTime())
                ->setType(User::ACCOUNT_CANDIDAT)
                ->setEmail($faker->email)
                ->setPassword($this->encoder->hashPassword($user, $plainPassword));

            $candidat = new CandidateProfile();
            $candidat->setCandidat($user)
                ->addExperience($faker->randomElement($experiences))
                ->addExperience($faker->randomElement($experiences))
                ->addCompetence($faker->randomElement($technicalskills))
                ->addCompetence($faker->randomElement($technicalskills))
                ->addLangage($faker->randomElement($languages))
                ->addLangage($faker->randomElement($languages))
                ->addSecteur($faker->randomElement($sectors))
                ->addSecteur($faker->randomElement($sectors))
                ->setResume($faker->paragraph(4));

                $manager->persist($user);
                $manager->persist($candidat);

            $candidats[] = $candidat;
        }

        for ($i = 0; $i < 20; $i++) {
            $application = new Applications();
            $application
                ->setAnnonce($faker->randomElement($jobListings))
                ->setCandidat($faker->randomElement($candidats))
                ->setLettreMotivation($faker->paragraph(3))
                ->setDateCandidature($faker->dateTime())
                ->setPretentionSalariale(120.00)
                ->setStatus($faker->randomElement($statusCandidature));

            $manager->persist($application);
        }


        $manager->flush();
    }
}