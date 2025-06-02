<?php

namespace App\WhiteLabel\Command\Client1;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\Prestation;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:deindex-elastic-search',
    description: 'De-index from Elasticsearch',
    hidden: false,
    aliases: ['app:deindex-elastic-search']
)]
class DeindexElasticSearchCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em, 
        private ElasticsearchService $elasticsearchService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('De-index from Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $validProfiles = $this->em->getRepository(CandidateProfile::class)->findValidProfiles();
        $validJobListings = $this->em->getRepository(JobListing::class)->findValidJobListings();
        $validPrestations = $this->em->getRepository(Prestation::class)->findValidPrestations();

        foreach ($validProfiles as $profile) {
            if ($profile->isGeneretated() != true) {
                $params = [
                    'index' => 'candidate_index',
                    'id'    => $profile->getId(),
                ];
    
                if ($this->elasticsearchService->exists($params)) {
    
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
                } else {
                    $io->note(sprintf('No document found to delete for candidateProfile ID: %s', $profile->getId()));
                }
            }
        }

        foreach ($validJobListings as $job) {
            if ($job->isGeneretated() != true) {
                $params = [
                    'index' => 'joblisting_index',
                    'id'    => $job->getId(),
                ];
    
                if ($this->elasticsearchService->exists($params)) {
    
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
                } else {
                    $io->note(sprintf('No document found to delete for jobListing ID: %s', $job->getId()));
                }
            }
        }

        foreach ($validPrestations as $prestation) {
            if ($prestation->isGeneretated() != true) {
                $params = [
                    'index' => 'joblisting_index',
                    'id'    => $prestation->getId(),
                ];
    
                if ($this->elasticsearchService->exists($params)) {
    
                    if ($this->elasticsearchService->exists($params)) {
                        try {
                            $this->elasticsearchService->delete($params);
                            $io->note(sprintf('Deleted prestation ID: %s', $prestation->getId()));
                        } catch (\Exception $e) {
                            $output->writeln('Failed to delete prestation ID: ' . $prestation->getId() . ' with error: ' . $e->getMessage());
                        }
                    } else {
                        $io->note(sprintf('No document found to delete for prestation ID: %s', $prestation->getId()));
                    }
                } else {
                    $io->note(sprintf('No document found to delete for prestation ID: %s', $prestation->getId()));
                }
            }
        }

        return Command::SUCCESS;
    }
}
