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
        
        foreach ($expiredJobListings as $jobListing) {
            $this->elasticsearchService->delete([
                'index' => 'joblisting_premium_index',
                'id'    => $jobListing->getId(),
            ]);
            
            $io->note(sprintf('Deleted expired Premium Joblisting ID: %s', $jobListing->getId()));
        }

        foreach ($expiredPrestations as $prestation) {
            $this->elasticsearchService->delete([
                'index' => 'prestation_premium_index',
                'id'    => $prestation->getId(),
            ]);
            
            $io->note(sprintf('Deleted expired Premium prestation ID: %s', $prestation->getId()));
        }

        foreach ($expiredCandidateProfile as $candidate) {
            $this->elasticsearchService->delete([
                'index' => 'candidate_premium_index',
                'id'    => $candidate->getId(),
            ]);
            
            $io->note(sprintf('Deleted expired Premium candidate ID: %s', $candidate->getId()));
        }

        // foreach ($expiredRecruiterProfile as $recruiter) {
        //     $this->elasticsearchService->delete([
        //         'index' => 'joblisting_premium_index',
        //         'id'    => $recruiter->getId(),
        //     ]);
            
        //     $io->note(sprintf('Deleted expired Premium recruiter ID: %s', $recruiter->getId()));
        // }

        $io->success('All expired boost removed from elasticsearch');

        return Command::SUCCESS;
    }
}
