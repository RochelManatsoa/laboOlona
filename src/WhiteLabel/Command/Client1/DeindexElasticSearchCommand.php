<?php

namespace App\WhiteLabel\Command\Client1;

use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:client1:deindex-elastic-search',
    description: 'De-index from Elasticsearch',
    hidden: false,
    aliases: ['app:client1:deindex-elastic-search']
)]
class DeindexElasticSearchCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager, 
        private ElasticsearchService $elasticsearchService
    ) {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function configure(): void
    {
        $this->setDescription('De-index from Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $validProfiles = $this->entityManager->getRepository(CandidateProfile::class)->findValidProfiles();
        $validJobListings = $this->entityManager->getRepository(JobListing::class)->findValidJobListings();

        foreach ($validProfiles as $profile) {
            if ($profile->isGeneretated() != true) {
                $params = [
                    'index' => 'candidate_white_label_index',
                    'id'    => $profile->getId(),
                ];

                if ($this->elasticsearchService->exists($params)) {
                    try {
                        $this->elasticsearchService->delete($params);
                        $io->note(sprintf('Deleted candidateProfile ID: %s', $profile->getId()));
                    } catch (\Exception $e) {
                        $output->writeln('Failed to delete candidateProfile ID: ' . $profile->getId() . ' with error: ' . $e->getMessage());
                    }
                } else {
                    $io->note(sprintf('No document found to delete for candidateProfile ID: %s', $profile->getId()));
                }
            }
        }

        foreach ($validJobListings as $job) {
            if ($job->isGeneretated() != true) {
                $params = [
                    'index' => 'joblisting_white_label_index',
                    'id'    => $job->getId(),
                ];

                if ($this->elasticsearchService->exists($params)) {
                    try {
                        $this->elasticsearchService->delete($params);
                        $io->note(sprintf('Deleted jobListing ID: %s', $job->getId()));
                    } catch (\Exception $e) {
                        $output->writeln('Failed to delete jobListing ID: ' . $job->getId() . ' with error: ' . $e->getMessage());
                    }
                } else {
                    $io->note(sprintf('No document found to delete for jobListing ID: %s', $job->getId()));
                }
            }
        }

        return Command::SUCCESS;
    }
}
