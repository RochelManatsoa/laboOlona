<?php

namespace App\Command;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:remove-expired-boosts',
    description: 'Remove expired boosts command',
    hidden: false,
    aliases: ['app:remove-expired-boosts']
)]
class RemoveExpiredBoostCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ElasticsearchService $elasticsearchService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove expired boosts to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $expiredJobListings = $this->entityManager->getRepository(JobListing::class)->findExpiredPremium();
        $expiredPrestations = $this->entityManager->getRepository(Prestation::class)->findExpiredPremium();
        $expiredCandidateProfile = $this->entityManager->getRepository(CandidateProfile::class)->findExpiredPremium();
        $expiredRecruiterProfile = $this->entityManager->getRepository(EntrepriseProfile::class)->findExpiredPremium();
        
        
        foreach ($expiredJobListings as $listing) {
            $params = [
                'index' => 'joblisting_premium_index',
                'id'    => $listing->getId(),
            ];

            if ($this->elasticsearchService->exists($params)) {

                if ($this->elasticsearchService->exists($params)) {
                    try {
                        $this->elasticsearchService->delete($params);
                        $io->note(sprintf('Deleted expired Premium Prestation ID: %s', $listing->getId()));
                    } catch (\Exception $e) {
                        $output->writeln('Failed to delete Joblisting ID: ' . $listing->getId() . ' with error: ' . $e->getMessage());
                    }
                } else {
                    $io->note(sprintf('No document found to delete for ID: %s', $listing->getId()));
                }
            } else {
                $io->note(sprintf('No document found to delete for ID: %s', $listing->getId()));
            }
        }

        foreach ($expiredPrestations as $listing) {
            $params = [
                'index' => 'prestation_premium_index',
                'id'    => $listing->getId(),
            ];

            if ($this->elasticsearchService->exists($params)) {
                try {
                    $this->elasticsearchService->delete($params);
                    $io->note(sprintf('Deleted expired Premium Prestation ID: %s', $listing->getId()));
                } catch (\Exception $e) {
                    $output->writeln('Failed to delete Joblisting ID: ' . $listing->getId() . ' with error: ' . $e->getMessage());
                }
            } else {
                $io->note(sprintf('No document found to delete for ID: %s', $listing->getId()));
            }
        }

        foreach ($expiredCandidateProfile as $listing) {
            $params = [
                'index' => 'candidate_premium_index',
                'id'    => $listing->getId(),
            ];

            if ($this->elasticsearchService->exists($params)) {
                try {
                    $this->elasticsearchService->delete($params);
                    $io->note(sprintf('Deleted expired Premium Prestation ID: %s', $listing->getId()));
                } catch (\Exception $e) {
                    $output->writeln('Failed to delete Joblisting ID: ' . $listing->getId() . ' with error: ' . $e->getMessage());
                }
            } else {
                $io->note(sprintf('No document found to delete for ID: %s', $listing->getId()));
            }
        }

        // foreach ($expiredRecruiterProfile as $listing) {
        //     $params = [
        //         'index' => 'candidate_premium_index',
        //         'id'    => $listing->getId(),
        //     ];

        //     if ($this->elasticsearchService->exists($params)) {
        //         try {
        //             $this->elasticsearchService->delete($params);
        //             $io->note(sprintf('Deleted expired Premium Prestation ID: %s', $listing->getId()));
        //         } catch (\Exception $e) {
        //             $output->writeln('Failed to delete Joblisting ID: ' . $listing->getId() . ' with error: ' . $e->getMessage());
        //         }
        //     } else {
        //         $io->note(sprintf('No document found to delete for ID: %s', $listing->getId()));
        //     }
        // }

        $io->success('All expired boost removed from elasticsearch');

        return Command::SUCCESS;
    }
}
