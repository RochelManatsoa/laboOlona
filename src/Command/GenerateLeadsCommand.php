<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Marketing\Lead;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Finance\ContratRepository;
use App\Repository\Logs\ActivityLogRepository;
use App\Repository\Marketing\SourceRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument('source', InputArgument::REQUIRED, 'The source.')
            ->setDescription('Génération des leads pour les candidats.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = $input->getArgument('source');
        $source = $this->sourceRepository->findOneBy(['slug' => $slug]);
        $repository = $this->entityManager->getRepository($source->getNameSpace());
        $elements = $repository->findAll();
        $io->writeln('Génération des leads pour '. $source->getName());
        foreach ($elements as $element) {
            if($element instanceof EntrepriseProfile){
                $user = null;
                if(count($element->getJobListings()) > 0){
                   $user = $element->getEntreprise();
                }
            }
            if($user instanceof User){
                $lead = new Lead();
                $lead->setSource($source);
                $lead->setFullName($user);
                $lead->setComment($source->getDescription());
                $lead->setEmail($user->getEmail());
                $lead->setPhone($user->getTelephone());
                $lead->setUser($user);
                $this->entityManager->persist($lead);
                $io->writeln(sprintf('Lead créé : %s', $source->getName()));
            }
        }

        $this->entityManager->flush();

        $io->success('Les leads ont été ajoutés avec succès.');

        return Command::SUCCESS;
    }
}