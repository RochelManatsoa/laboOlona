<?php

namespace App\Command;

use Faker\Factory;
use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Entity\EntrepriseProfile;
use App\Entity\Candidate\Competences;
use App\Entity\Entreprise\JobListing;
use App\Repository\SecteurRepository;
use App\Entity\Moderateur\TypeContrat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generate test data for Entreprise and Job Listings',
    hidden: false,
    aliases: ['app:generate-test-data']
)]
class GenerateTestDataCommand extends Command
{
    public function __construct(
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private SecteurRepository $secteurRepository,
        private SluggerInterface $sluggerInterface
    ) {
        parent::__construct();
        $this->entrepriseProfileRepository = $entrepriseProfileRepository;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->secteurRepository = $secteurRepository;
        $this->sluggerInterface = $sluggerInterface;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate test data for Entreprise and Job Listings')
            ->addArgument('entreprise-count', InputArgument::OPTIONAL, 'Number of entreprise profiles to generate', 5)
            ->addArgument('joblisting-count', InputArgument::OPTIONAL, 'Number of job listings to generate', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $enterpriseCount = $input->getArgument('entreprise-count');
        $jobListingCount = $input->getArgument('joblisting-count');

        $faker = Factory::create('fr_FR');
        $secteurs = $this->secteurRepository->findAll();
        $typeContrat = $this->entityManager->getRepository(TypeContrat::class)->findAll();
        $entreprises = [];
        $annonces = [
            [
                "titre" => "Graphiste Créatif",
                "description" => "Nous recherchons un graphiste créatif pour rejoindre notre équipe de design. Vous serez responsable de la création de visuels attrayants pour nos campagnes marketing, nos sites web et nos réseaux sociaux. Vous devez maîtriser les logiciels de design tels qu'Adobe Photoshop, Illustrator et InDesign. Une bonne compréhension des principes de design et une capacité à travailler sur plusieurs projets simultanément sont essentielles. Vous devez également être capable de recevoir des feedbacks et de les intégrer dans votre travail pour améliorer constamment la qualité des visuels produits. Si vous avez un œil pour le détail et une passion pour le design graphique, nous serions ravis de vous rencontrer.",
                "shortDescription" => "Graphiste créatif recherché pour créer des visuels attrayants. Maîtrise d'Adobe Photoshop, Illustrator et InDesign requise.",
                "competences" => ["Adobe Photoshop", "Adobe Illustrator", "Adobe InDesign", "Créativité", "Design graphique"],
            ],
            [
                "titre" => "Motion Designer",
                "description" => "Nous recherchons un motion designer talentueux pour créer des animations et des vidéos engageantes pour nos plateformes digitales. Vous serez responsable de la conception et de la réalisation de contenus animés qui captivent notre audience. Vous devez avoir une solide expérience en animation et en utilisation de logiciels comme After Effects et Premiere Pro. Une bonne compréhension de la typographie, de la couleur et du timing est essentielle. Vous travaillerez en étroite collaboration avec notre équipe de marketing pour développer des concepts et des storyboards. Si vous êtes passionné par l'animation et que vous aimez raconter des histoires visuellement, nous aimerions vous rencontrer.",
                "shortDescription" => "Motion designer recherché pour créer des animations et vidéos engageantes. Expérience avec After Effects et Premiere Pro requise.",
                "competences" => ["After Effects", "Premiere Pro", "Animation", "Typographie", "Storyboarding"],
            ],
            [
                "titre" => "Spécialiste Marketing Digital",
                "description" => "Nous recherchons un spécialiste marketing digital pour développer et exécuter des stratégies marketing efficaces. Vous serez responsable de la gestion des campagnes publicitaires en ligne, de l'analyse des performances et de l'optimisation des budgets. Vous devez avoir une bonne compréhension des plateformes publicitaires telles que Google Ads et Facebook Ads, ainsi qu'une expérience en SEO et SEM. Une capacité à analyser les données et à tirer des insights pour améliorer les performances des campagnes est essentielle. Si vous êtes passionné par le marketing digital et que vous aimez travailler dans un environnement dynamique, nous serions ravis de vous accueillir dans notre équipe.",
                "shortDescription" => "Spécialiste marketing digital recherché pour développer des stratégies marketing. Expérience avec Google Ads et Facebook Ads requise.",
                "competences" => ["Google Ads", "Facebook Ads", "SEO", "SEM", "Analyse de données"],
            ],
            [
                "titre" => "Responsable des Ventes",
                "description" => "Nous recherchons un responsable des ventes expérimenté pour diriger notre équipe commerciale et augmenter nos revenus. Vous serez responsable de la définition des objectifs de vente, de la mise en œuvre des stratégies et de la gestion de l'équipe de vente. Vous devez avoir une solide expérience en gestion des ventes, en négociation et en développement de relations clients. Une capacité à analyser les performances des ventes et à ajuster les stratégies en conséquence est essentielle. Si vous avez un fort leadership et une passion pour les ventes, nous serions ravis de vous accueillir.",
                "shortDescription" => "Responsable des ventes recherché pour diriger l'équipe commerciale. Expérience en gestion des ventes et négociation requise.",
                "competences" => ["Gestion des ventes", "Négociation", "Développement de relations clients", "Analyse de performances"],
            ],
            [
                "titre" => "Représentant Commercial",
                "description" => "Nous recherchons un représentant commercial dynamique pour développer notre portefeuille de clients et augmenter nos ventes. Vous serez responsable de la prospection de nouveaux clients, de la présentation de nos produits et services et de la conclusion des ventes. Vous devez avoir une bonne connaissance des techniques de vente et une capacité à établir des relations solides avec les clients. Une expérience en B2B est un plus. Si vous êtes motivé, orienté résultats et que vous avez une passion pour la vente, nous serions ravis de vous rencontrer.",
                "shortDescription" => "Représentant commercial recherché pour développer le portefeuille de clients. Expérience en B2B est un plus.",
                "competences" => ["Prospection", "Techniques de vente", "Relations clients", "B2B"],
            ],
            [
                "titre" => "Agent de Service Clientèle",
                "description" => "Nous recherchons un agent de service clientèle pour offrir un support exceptionnel à nos clients. Vous serez responsable de répondre aux demandes des clients, de résoudre les problèmes et de fournir des informations sur nos produits et services. Vous devez avoir d'excellentes compétences en communication et une capacité à gérer plusieurs tâches simultanément. Une expérience en service clientèle est un atout. Si vous aimez aider les gens et que vous êtes orienté solutions, nous serions ravis de vous accueillir dans notre équipe.",
                "shortDescription" => "Agent de service clientèle recherché pour offrir un support exceptionnel. Expérience en service clientèle est un atout.",
                "competences" => ["Service clientèle", "Communication", "Gestion des tâches", "Résolution de problèmes"],
            ],
            [
                "titre" => "Superviseur du Service Clientèle",
                "description" => "Nous recherchons un superviseur du service clientèle expérimenté pour gérer notre équipe de support. Vous serez responsable de la supervision des agents de service clientèle, de la formation du personnel et de l'amélioration des processus. Vous devez avoir une expérience en gestion d'équipe et en service clientèle. Une capacité à analyser les performances et à proposer des améliorations est essentielle. Si vous avez un esprit de leadership et que vous êtes passionné par l'amélioration du service client, nous aimerions vous rencontrer.",
                "shortDescription" => "Superviseur du service clientèle recherché pour gérer l'équipe de support. Expérience en gestion d'équipe requise.",
                "competences" => ["Gestion d'équipe", "Service clientèle", "Formation", "Amélioration des processus"],
            ],
            [
                "titre" => "Ingénieur en Intelligence Artificielle",
                "description" => "Nous recherchons un ingénieur en intelligence artificielle pour développer des solutions innovantes basées sur l'IA. Vous serez responsable de la conception, du développement et de l'implémentation de modèles d'apprentissage automatique. Vous devez avoir une solide expérience en programmation, en statistiques et en analyse de données. Une connaissance des frameworks d'IA tels que TensorFlow ou PyTorch est essentielle. Si vous êtes passionné par l'IA et que vous aimez résoudre des problèmes complexes, nous serions ravis de vous accueillir.",
                "shortDescription" => "Ingénieur en intelligence artificielle recherché pour développer des solutions basées sur l'IA. Expérience avec TensorFlow ou PyTorch requise.",
                "competences" => ["Programmation", "Apprentissage automatique", "TensorFlow", "PyTorch", "Analyse de données"],
            ],
            [
                "titre" => "Data Scientist",
                "description" => "Nous recherchons un data scientist pour analyser des données complexes et fournir des insights précieux à notre entreprise. Vous serez responsable de la collecte, du nettoyage et de l'analyse des données pour aider à la prise de décision. Vous devez avoir une solide expérience en statistiques, en programmation et en manipulation de données. Une connaissance des outils de visualisation de données tels que Tableau ou Power BI est un plus. Si vous avez un esprit analytique et une passion pour les données, nous serions ravis de vous accueillir.",
                "shortDescription" => "Data scientist recherché pour analyser des données complexes. Expérience avec des outils de visualisation de données est un plus.",
                "competences" => ["Statistiques", "Programmation", "Analyse de données", "Tableau", "Power BI"],
            ],
            [
                "titre" => "Spécialiste en Automatisation des Processus",
                "description" => "Nous recherchons un spécialiste en automatisation des processus pour optimiser nos opérations et augmenter notre efficacité. Vous serez responsable de l'identification des opportunités d'automatisation, de la conception de solutions et de leur mise en œuvre. Vous devez avoir une bonne compréhension des outils d'automatisation et une expérience en gestion de projets. Une capacité à analyser les processus et à proposer des améliorations est essentielle. Si vous êtes passionné par l'automatisation et que vous aimez résoudre des problèmes complexes, nous serions ravis de vous accueillir.",
                "shortDescription" => "Spécialiste en automatisation des processus recherché pour optimiser les opérations. Expérience en gestion de projets requise.",
                "competences" => ["Automatisation", "Gestion de projets", "Analyse de processus", "Outils d'automatisation"],
            ],
            [
                "titre" => "Responsable des Ressources Humaines",
                "description" => "Nous recherchons un responsable des ressources humaines pour superviser toutes les fonctions RH de notre entreprise. Vous serez responsable de la gestion des recrutements, de la formation du personnel et de l'élaboration des politiques RH. Vous devez avoir une solide expérience en gestion des ressources humaines et une bonne connaissance des lois du travail. Une capacité à gérer les conflits et à promouvoir un environnement de travail positif est essentielle. Si vous êtes passionné par les ressources humaines et que vous avez un fort leadership, nous serions ravis de vous accueillir.",
                "shortDescription" => "Responsable des ressources humaines recherché pour superviser les fonctions RH. Expérience en gestion des RH requise.",
                "competences" => ["Gestion des RH", "Recrutement", "Formation", "Législation du travail", "Gestion des conflits"],
            ],
            [
                "titre" => "Assistant Administratif RH",
                "description" => "Nous recherchons un assistant administratif RH pour soutenir notre département des ressources humaines. Vous serez responsable de la gestion des dossiers du personnel, de l'organisation des entretiens et de la coordination des formations. Vous devez avoir une bonne connaissance des procédures administratives et une capacité à gérer plusieurs tâches simultanément. Une expérience en ressources humaines est un plus. Si vous êtes organisé, méthodique et que vous aimez travailler dans un environnement dynamique, nous serions ravis de vous accueillir.",
                "shortDescription" => "Assistant administratif RH recherché pour soutenir le département RH. Expérience en ressources humaines est un plus.",
                "competences" => ["Administration", "Gestion des dossiers", "Organisation", "Coordination"],
            ],
            [
                "titre" => "Chargé de Recrutement",
                "description" => "Nous recherchons un chargé de recrutement pour gérer l'ensemble du processus de recrutement de notre entreprise. Vous serez responsable de la rédaction des offres d'emploi, de la présélection des candidats et de la conduite des entretiens. Vous devez avoir une bonne compréhension des techniques de recrutement et une capacité à identifier les talents. Une expérience en utilisation des réseaux sociaux et des plateformes de recrutement est essentielle. Si vous êtes passionné par le recrutement et que vous aimez travailler dans un environnement dynamique, nous serions ravis de vous accueillir.",
                "shortDescription" => "Chargé de recrutement recherché pour gérer le processus de recrutement. Expérience en utilisation des réseaux sociaux requise.",
                "competences" => ["Recrutement", "Réseaux sociaux", "Entrevues", "Identification des talents"],
            ],
            [
                "titre" => "Consultant RH",
                "description" => "Nous recherchons un consultant RH pour fournir des conseils stratégiques à notre équipe de direction sur les questions de ressources humaines. Vous serez responsable de l'analyse des besoins RH, de la formulation de recommandations et de l'implémentation de solutions. Vous devez avoir une solide expérience en gestion des ressources humaines et en conseil. Une capacité à communiquer efficacement et à travailler en étroite collaboration avec les parties prenantes est essentielle. Si vous êtes passionné par les ressources humaines et que vous avez un esprit stratégique, nous serions ravis de vous accueillir.",
                "shortDescription" => "Consultant RH recherché pour fournir des conseils stratégiques. Expérience en gestion des RH et en conseil requise.",
                "competences" => ["Conseil", "Analyse des besoins", "Gestion des RH", "Communication", "Stratégie"],
            ],
            [
                "titre" => "Spécialiste de la Paie",
                "description" => "Nous recherchons un spécialiste de la paie pour gérer toutes les opérations de paie de notre entreprise. Vous serez responsable de la préparation des fiches de paie, de la gestion des déclarations sociales et de l'administration des avantages sociaux. Vous devez avoir une solide expérience en gestion de la paie et une bonne connaissance des réglementations en vigueur. Une capacité à travailler avec précision et à respecter les délais est essentielle. Si vous êtes passionné par la paie et que vous avez un excellent sens du détail, nous serions ravis de vous accueillir.",
                "shortDescription" => "Spécialiste de la paie recherché pour gérer les opérations de paie. Expérience en gestion de la paie requise.",
                "competences" => ["Gestion de la paie", "Déclarations sociales", "Avantages sociaux", "Précision", "Réglementations"],
            ],
        ];
        $countryCodes = [
            'AF', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 
            'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR', 'IO', 'BN', 'BG', 'BF', 'BI', 
            'CV', 'KH', 'CM', 'CA', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CG', 'CD', 'CK', 'CR', 'HR', 
            'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FO', 'FJ', 
            'FI', 'FR', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 
            'GN', 'GW', 'GY', 'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 
            'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 
            'LT', 'LU', 'MO', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM', 'MD', 'MC', 
            'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI', 'NE', 'NG', 'NU', 'NF', 'MK', 
            'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 
            'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 
            'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'SS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SZ', 'SE', 'CH', 'SY', 'TW', 
            'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 
            'UM', 'UY', 'UZ', 'VU', 'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW'
        ];

        // Generate entreprise profiles
        $io->section('Generating entreprise profiles...');
        for ($i = 0; $i < $enterpriseCount; $i++) {
            $user = new User();
            $user->setEmail($faker->email)
                ->setRoles(['ROLE_USER'])
                ->setDateInscription($faker->dateTimeBetween('-1 year', 'now'))
                ->setPassword($this->passwordHasher->hashPassword($user, '000000'));

            $this->entityManager->persist($user);

            $entrepriseProfile = new EntrepriseProfile();
            $entrepriseProfile->setEntreprise($user)
                ->setTaille($faker->randomElement([EntrepriseProfile::SIZE_SMALL, EntrepriseProfile::SIZE_MEDIUM, EntrepriseProfile::SIZE_LARGE]))
                ->setLocalisation($faker->randomElement($countryCodes))
                ->setSiteWeb($faker->url)
                ->setNom($faker->company)
                ->setStatus($faker->randomElement([EntrepriseProfile::STATUS_VALID, EntrepriseProfile::STATUS_PENDING]))
                ->setDescription($faker->realText(200));

            for ($j = 0; $j < rand(1, 3); $j++) {
                $secteur = $faker->randomElement($secteurs);
                $entrepriseProfile->addSecteur($secteur);
            }

            $this->entityManager->persist($entrepriseProfile);
            $entreprises[] = $entrepriseProfile;
        }

        // Generate job listings
        $io->section('Generating job listings...');
        foreach($annonces as $annonce){
            $jobListing = new JobListing();
            $jobListing->setTitre($annonce['titre'])
                ->setDescription($annonce['description'])
                ->setDateCreation($faker->dateTimeBetween('-1 month', 'now'))
                ->setDateExpiration($faker->dateTimeBetween('now', '+1 year'))
                ->setStatus($faker->randomElement(JobListing::getArrayStatuses()))
                ->setTypeContrat($faker->randomElement($typeContrat))
                ->setSalaire($faker->randomFloat(2, 200, 1000))
                ->setLieu($faker->city)
                ->setJobId(Uuid::v4())
                ->setNombrePoste($faker->numberBetween(1, 10))
                ->setShortDescription($annonce['shortDescription'])
                ->setIsGenerated(true);

            if ($entreprises) {
                $entreprise = $faker->randomElement($entreprises);
                $jobListing->setEntreprise($entreprise);
            }

            if ($secteurs) {
                $secteur = $faker->randomElement($secteurs);
                $jobListing->setSecteur($secteur);
            }

            foreach ($annonce['competences'] as $competence) {
                $newCompetences = $this->entityManager->getRepository(Competences::class)->findOneBy([
                    'slug' => $this->sluggerInterface->slug($competence)
                ]);
                if (!$newCompetences instanceof Competences) {
                    $newCompetences = (new Competences())->setNom($competence)->setSlug($this->sluggerInterface->slug($competence));
                    $this->entityManager->persist($newCompetences);
                }
                $jobListing->addCompetence($newCompetences);
            }

            $this->entityManager->persist($jobListing);
        }

        $this->entityManager->flush();
        $io->success('Test data generated successfully.');

        return Command::SUCCESS;
    }
}