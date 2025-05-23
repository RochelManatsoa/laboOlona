<?php

namespace App\Command;

use Faker\Factory;
use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Repository\Logs\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-logs',
    description: 'Add pageUrl to log',
    hidden: false,
    aliases: ['app:update-logs']
)]
class UpdateLogsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActivityLogRepository $activityLogRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Ajoute les données de pageUrl dans les logs des PAGE_VIEW.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logs = $this->activityLogRepository->findAllPageVieuw();
        foreach ($logs as $log) {
            $details = $log->getDetails();
            $pageUrl = preg_replace('/^Page consultée: /', '', $details);
            $log->setPageUrl($pageUrl);
            $log->setDerationInSeconds(0);
            $this->entityManager->persist($log);
            $io->writeln(sprintf('Mis à jour : %d', $log->getId()));
        }

        $this->entityManager->flush();

        $io->success('Les données ont été mis à jour avec succès.');

        return Command::SUCCESS;
    }
}