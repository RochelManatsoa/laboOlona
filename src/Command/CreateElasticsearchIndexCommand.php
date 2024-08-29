<?php

namespace App\Command;

use App\Service\ElasticsearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-elasticsearch-index',
    description: 'Create Elasticsearch indices',
    hidden: false,
    aliases: ['app:create-elasticsearch-index']
)]
class CreateElasticsearchIndexCommand extends Command
{
    public function __construct(private ElasticsearchService $elasticsearch)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create Elasticsearch indices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $settings = [
            'number_of_shards' => 1,
            'number_of_replicas' => 1,
        ];        

        $mappingsCandidate = [
            'properties' => [
                'titre' => ['type' => 'text'],
                'resume' => ['type' => 'text'],
                'fileName' => ['type' => 'text'],
                'localisation' => ['type' => 'keyword'],
                'technologies' => ['type' => 'text'],
                'isValid' => ['type' => 'boolean'],
                'status' => ['type' => 'keyword'],
                'tools' => ['type' => 'text'],
                'badKeywords' => ['type' => 'text'],
                'resultFree' => ['type' => 'text'],
                'metaDescription' => ['type' => 'text'],
                'traductionEn' => ['type' => 'text'],
                'competences' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'experiences' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'secteurs' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'langages' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
            ],
        ];      

        $mappingsJoblisting = [
            'properties' => [
                'titre' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'secteur' => ['type' => 'text'],
                'dateCreation' => ['type' => 'date'],
                'dateExpiration' => ['type' => 'date'],
                'lieu' => ['type' => 'text'],
                'nombrePoste' => ['type' => 'integer'],
                'shortDescription' => ['type' => 'text'],
                'typeContrat' => ['type' => 'text'],
                'budgetAnnonce' => ['type' => 'text'],
                'primeAnnonce' => ['type' => 'text'],
                'competences' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'applications' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
                'langues' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
            ],
        ];    

        $mappingsPrestation = [
            'properties' => [
                'description' => ['type' => 'text'],
                'competencesRequises' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'tarifsProposes' => [
                    'type' => 'nested',
                    'properties' => [
                        'par_heure' => ['type' => 'double'],
                        'par_projet' => ['type' => 'double'],
                    ],
                ],
                'modalitesPrestation' => ['type' => 'text'],
                'specialisations' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
                'medias' => [
                    'type' => 'nested',
                    'properties' => [
                        'images' => ['type' => 'text'],
                        'video' => ['type' => 'text'],
                    ],
                ],
                'evaluations' => [
                    'type' => 'nested',
                    'properties' => [
                        'client' => ['type' => 'text'],
                        'commentaire' => ['type' => 'text'],
                        'note' => ['type' => 'text'],
                    ],
                ],
                'disponibilites' => [
                    'type' => 'nested',
                    'properties' => [
                        'nom' => ['type' => 'text'],
                    ],
                ],
            ],
        ];

        try {
            $this->elasticsearch->createIndex('candidate_profile_index', $settings, $mappingsCandidate);
            $io->success('Index "candidate_profile_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        try {
            $this->elasticsearch->createIndex('candidate_premium_index', $settings, $mappingsCandidate);
            $io->success('Index "candidate_premium_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        try {
            $this->elasticsearch->createIndex('joblisting_index', $settings, $mappingsJoblisting);
            $io->success('Index "joblisting_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        try {
            $this->elasticsearch->createIndex('joblisting_premium_index', $settings, $mappingsJoblisting);
            $io->success('Index "joblisting_premium_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        try {
            $this->elasticsearch->createIndex('prestation_index', $settings, $mappingsPrestation);
            $io->success('Index "prestation_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        try {
            $this->elasticsearch->createIndex('prestation_premium_index', $settings, $mappingsPrestation);
            $io->success('Index "prestation_premium_index" created successfully.');
        } catch (\Exception $e) {
            $io->error('Error creating index: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
