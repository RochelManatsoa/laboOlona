<?php

namespace App\Command;

use Faker\Factory;
use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:add-prestations',
    description: 'Add prestation fixtures',
    hidden: false,
    aliases: ['app:add-prestations']
)]
class AddPrestationsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Ajoute des données aléatoires aux entités Prestation, CandidateProfile et EntrepriseProfile.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('fr_FR');

        // Récupérer 10 candidats et 10 entreprises au hasard
        $candidateProfiles = $this->entityManager->getRepository(CandidateProfile::class)->findBy(['status' => CandidateProfile::STATUS_VALID ], null, 10);
        $entrepriseProfiles = $this->entityManager->getRepository(EntrepriseProfile::class)->findBy(['status' => EntrepriseProfile::STATUS_VALID ], null, 10);

        $prestations = [
            0 => [
                'titre' => 'Développement d\'une application web responsive',
                'description' => 'Création de sites web responsive avec une approche mobile-first. Utilisation des dernières technologies comme PHP, JavaScript, CSS, et HTML pour assurer une expérience utilisateur fluide et performante.',
                'shortDescription' => 'Création de sites web responsive avec les technologies actuelles.',
                'competencesRequises' => ['PHP', 'JavaScript', 'CSS', 'HTML'],
                'specialisations' => ['Développement front-end', 'Responsive Design'],
                'evaluations' => [
                    ['client' => 'John Doe', 'commentaire' => 'Travail exceptionnel et livré dans les délais.', 'note' => '5'],
                    ['client' => 'Jane Smith', 'commentaire' => 'Le projet nécessitait des ajustements, mais ont été bien gérés.', 'note' => '4'],
                ],
            ],
            1 => [
                'titre' => 'Optimisation SEO pour sites web',
                'description' => 'Amélioration de la visibilité des sites web sur les moteurs de recherche grâce à des techniques avancées de SEO, incluant l\'analyse de mots-clés, l\'optimisation on-page et le link building.',
                'shortDescription' => 'Amélioration de visibilité sur les moteurs de recherche via SEO.',
                'competencesRequises' => ['SEO', 'Analyse de mots-clés', 'Link Building'],
                'specialisations' => ['SEO on-page', 'SEO off-page'],
                'evaluations' => [
                    ['client' => 'Emily Roux', 'commentaire' => 'Excellent travail de SEO, nette amélioration du ranking.', 'note' => '5'],
                ],
            ],
            2 => [
                'titre' => 'Rédaction de contenu marketing',
                'description' => 'Conception et rédaction de contenu captivant pour blogs, sites web et médias sociaux. Spécialisation en création de contenu qui engage les audiences et renforce la marque.',
                'shortDescription' => 'Rédaction de contenu captivant pour diverses plateformes.',
                'competencesRequises' => ['Rédaction', 'Content Marketing', 'SEO'],
                'specialisations' => ['Blogs', 'Médias sociaux'],
                'evaluations' => [
                    ['client' => 'Martin Lopes', 'commentaire' => 'Contenus créatifs et très engageants, très professionnel.', 'note' => '5'],
                ],
            ],
            3 => [
                'titre' => 'Développement de logiciels sur mesure',
                'description' => 'Conception et développement de solutions logicielles personnalisées pour répondre aux besoins spécifiques des entreprises, en utilisant des technologies modernes comme Python, Java et C#.',
                'shortDescription' => 'Développement de solutions logicielles personnalisées.',
                'competencesRequises' => ['Python', 'Java', 'C#'],
                'specialisations' => ['Applications d\'entreprise', 'Automatisation des processus'],
                'evaluations' => [
                    ['client' => 'Sophie Durant', 'commentaire' => 'Solution parfaitement adaptée à nos besoins.', 'note' => '4'],
                ],
            ],
            4 => [
                'titre' => 'Gestion de projets digitaux',
                'description' => 'Gestion et coordination de projets digitaux, de la conception à la mise en œuvre, en assurant le respect des délais, budgets et exigences qualité.',
                'shortDescription' => 'Gestion de projets digitaux de la conception à la livraison.',
                'competencesRequises' => ['Gestion de projets', 'Scrum', 'Agile'],
                'specialisations' => ['Projets web', 'Projets mobiles'],
                'evaluations' => [
                    ['client' => 'Carlos Nom', 'commentaire' => 'Gestion impeccable du projet, très satisfait.', 'note' => '5'],
                ],
            ],
            5 => [
                'titre' => 'Conception graphique et branding',
                'description' => 'Création de concepts visuels et identités de marque qui communiquent efficacement les valeurs et la vision de l\'entreprise, utilisant des outils comme Adobe Photoshop et Illustrator.',
                'shortDescription' => 'Création de concepts visuels et identités de marque.',
                'competencesRequises' => ['Design graphique', 'Adobe Photoshop', 'Illustrator'],
                'specialisations' => ['Logos', 'Identité visuelle'],
                'evaluations' => [
                    ['client' => 'Anita Schmidt', 'commentaire' => 'Identité visuelle frappante et mémorable, grand talent artistique.', 'note' => '5'],
                ],
            ],
            6 => [
                'titre' => 'Analyse de données et Business Intelligence',
                'description' => 'Analyse de grandes quantités de données pour générer des insights actionnables qui aident les entreprises à prendre des décisions éclairées, utilisant des outils comme SQL et Tableau.',
                'shortDescription' => 'Analyse de données pour soutenir les décisions d\'entreprise.',
                'competencesRequises' => ['SQL', 'Analyse de données', 'Tableau'],
                'specialisations' => ['Business Intelligence', 'Visualisation de données'],
                'evaluations' => [
                    ['client' => 'Lucas Girard', 'commentaire' => 'Insights très précis, a grandement aidé notre stratégie.', 'note' => '5'],
                ],
            ],
            7 => [
                'titre' => 'Audit et conseil en cybersécurité',
                'description' => 'Fourniture de services d\'audit et de conseils en cybersécurité pour protéger les infrastructures IT contre les menaces et les vulnérabilités, en utilisant des technologies de pointe.',
                'shortDescription' => 'Audit et conseils pour renforcer la sécurité IT.',
                'competencesRequises' => ['Cybersécurité', 'Pentesting', 'Cryptographie'],
                'specialisations' => ['Sécurité réseau', 'Sécurité des applications'],
                'evaluations' => [
                    ['client' => 'Marie Renaud', 'commentaire' => 'Expertise en sécurité de haut niveau, très recommandé.', 'note' => '5'],
                ],
            ],
            8 => [
                'titre' => 'Développement et intégration de systèmes ERP',
                'description' => 'Implémentation et personnalisation de systèmes ERP pour intégrer et automatiser les processus d\'entreprise, améliorant l\'efficacité opérationnelle et la gestion des ressources.',
                'shortDescription' => 'Implémentation de systèmes ERP pour automatiser les processus.',
                'competencesRequises' => ['SAP', 'Oracle', 'Gestion de base de données'],
                'specialisations' => ['Automatisation des processus', 'Intégration de systèmes'],
                'evaluations' => [
                    ['client' => 'Nicolas Petit', 'commentaire' => 'Intégration ERP réussie, support excellent.', 'note' => '4'],
                ],
            ],
            9 => [
                'titre' => 'Consultation en transformation digitale',
                'description' => 'Aide aux entreprises pour naviguer la transformation digitale, en mettant en œuvre des stratégies innovantes et des technologies avancées pour rester compétitives.',
                'shortDescription' => 'Guidage des entreprises à travers la transformation digitale.',
                'competencesRequises' => ['Consultation', 'Stratégie digitale', 'Innovation technologique'],
                'specialisations' => ['Digitalisation des processus', 'Tech Disruption'],
                'evaluations' => [
                    ['client' => 'Isabelle Moreau', 'commentaire' => 'Transformation digitale menée avec brio, résultats impressionnants.', 'note' => '5'],
                ],
            ],
        ];
        
        

        foreach ($candidateProfiles as $key => $candidateProfile) {
            $prestation = new Prestation();            
            $prestation
                ->setTitre($prestations[$key]['titre'])
                ->setCreatedAt(new \Datetime())
                ->setStatus(Prestation::STATUS_VALID)
                ->setIsGenerated(true)
                ->setOpenai($prestations[$key]['shortDescription'])
                ->setDescription($prestations[$key]['description'])
                ->setCleanDescription($prestations[$key]['description'])
                ->setCompetencesRequises($prestations[$key]['competencesRequises'])
                ->setTarifsProposes(['par_heure' => $faker->numberBetween(30, 100), 'par_projet' => $faker->numberBetween(300, 1000)])
                ->setModalitesPrestation($faker->randomElement(['à distance', 'sur site', 'hybride']))
                ->setDisponibilites([$faker->dayOfWeek . ' ' . $faker->time])
                ->setSpecialisations($prestations[$key]['specialisations'])
                ->setMedias(['images' => [$faker->imageUrl()], 'videos' => [$faker->url]])
                ->setEvaluations($prestations[$key]['evaluations'])
                ->setCandidateProfile($candidateProfile);

            $this->entityManager->persist($prestation);
        }

        // foreach ($entrepriseProfiles as $entrepriseProfile) {
        //     $prestation = new Prestation();
        //     $prestation->setDescription($faker->text)
        //         ->setCompetencesRequises($faker->words(5))
        //         ->setTarifsProposes(['par_heure' => $faker->numberBetween(30, 100), 'par_projet' => $faker->numberBetween(300, 1000)])
        //         ->setModalitesPrestation($faker->randomElement(['à distance', 'sur site', 'hybride']))
        //         ->setDisponibilites([$faker->dayOfWeek . ' ' . $faker->time])
        //         ->setSpecialisations($faker->words(3))
        //         ->setMedias(['images' => [$faker->imageUrl()], 'videos' => [$faker->url]])
        //         ->setEvaluations([['client' => $faker->name, 'commentaire' => $faker->sentence, 'note' => $faker->numberBetween(1, 5)]])
        //         ->setEntrepriseProfile($entrepriseProfile);

        //     $this->entityManager->persist($prestation);
        // }

        $this->entityManager->flush();

        $io->success('Les données aléatoires ont été ajoutées avec succès.');

        return Command::SUCCESS;
    }
}