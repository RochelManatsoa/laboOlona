<?php

namespace App\Command;

use App\Entity\Marketing\Lead;
use App\Repository\Finance\ContratRepository;
use App\Repository\Logs\ActivityLogRepository;
use App\Repository\Marketing\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-leads',
    description: 'Add pageUrl to log',
    hidden: false,
    aliases: ['app:generate-leads']
)]
class GenerateLeadsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContratRepository $contratRepository,
        private SourceRepository $sourceRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Génération des leads pour les candidats.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contrats = $this->contratRepository->findAll();
        foreach ($contrats as $contrat) {
            $source = $this->sourceRepository->findOneBy(['slug' => 'simulateur-salaire']);
            $lead = new Lead();
            $lead->setSource($source);
            if ($contrat->getUpdatedAt() !== null) {
                $lead->setCreatedAt(clone $contrat->getUpdatedAt());
            } else {
                $lead->setCreatedAt(new \DateTime());
            }
            $lead->setFullName($contrat->getSimulateur()->getEmploye()->getUser());
            $lead->setEmail($contrat->getSimulateur()->getEmploye()->getUser()->getEmail());
            $lead->setPhone($contrat->getSimulateur()->getEmploye()->getUser()->getTelephone());
            $lead->setUser($contrat->getSimulateur()->getEmploye()->getUser());
            $this->entityManager->persist($lead);
            $io->writeln(sprintf('Lead créé : %s', $source->getName()));
        }

        $this->entityManager->flush();

        $io->success('Les leads ont été ajoutés avec succès.');

        return Command::SUCCESS;
    }
}