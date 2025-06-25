<?php

namespace App\WhiteLabel\Command\Client1;

use App\Twig\ProfileExtension;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:client1:index-joblistings',
    description: 'Index all joblistings to Elasticsearch',
    hidden: false,
    aliases: ['app:client1:index-joblistings']
)]
class IndexJobListingsCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager, 
        private ElasticsearchService $elasticsearch,
        private ProfileExtension $extension
    )
    {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index all joblistings to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $annonces = $this->entityManager->getRepository(JobListing::class)->findPublishedJobListing();
        $premiums = $this->entityManager->getRepository(JobListing::class)->findPremiumJobListing();

        foreach ($annonces as $annonce) {
            $body = [
                'titre'             => $annonce->getTitre(),
                'description'       => $annonce->getDescription(),
                'secteur'           => $annonce->getSecteur()->getNom(),
                'dateCreation'      => $annonce->getDateCreation()->format('Y-m-d\TH:i:s'),
                'dateExpiration'    => $annonce->getDateExpiration()->format('Y-m-d\TH:i:s'),
                'lieu'              => $annonce->getLieu(),
                'nombrePoste'       => $annonce->getNombrePoste(),
                'shortDescription'  => $annonce->getShortDescription(),
                'typeContrat'       => $annonce->getTypeContrat()->getNom(),
                'budgetAnnonce'     => $this->extension->getBudgetAnnonceStrById($annonce->getId()),
                'primeAnnonce'      => $this->extension->getPrimeAnnonceStrById($annonce->getId()),
                'competences'       => [],
                'applications'      => [],
                'langues'           => [],
                'annonceVues'           => [],
            ];

            foreach ($annonce->getCompetences() as $competence) {
                $body['competences'][] = [
                    'nom' => $competence->getNom(),
                ];
            }

            foreach ($annonce->getApplications() as $application) {
                $body['applications'][] = [
                    'id'       => $application->getId(),
                ];
            }

            foreach ($annonce->getLangues() as $langue) {
                $body['langages'][] = [
                    'nom' => $langue()->getNom(),
                ];
            }

            foreach ($annonce->getAnnonceVues() as $application) {
                $body['annonceVues'][] = [
                    'id'       => $application->getId(),
                ];
            }

            $this->elasticsearch->index([
                'index' => 'joblisting_index',
                'id'    => $annonce->getId(),
                'body'  => $body,
            ]);

            $this->elasticsearch->index([
                'index' => 'joblisting_white_label_index',
                'id'    => $annonce->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Joblisting ID: ' . $annonce->getId());
        }

        foreach ($annonces as $annonce) {
            $body = [
                'titre'             => $annonce->getTitre(),
                'cleanDescription'  => $annonce->getCleanDescription(),
                'secteur'           => $annonce->getSecteur()->getNom(),
                'dateCreation'      => $annonce->getDateCreation()->format('Y-m-d\TH:i:s'),
                'dateExpiration'    => $annonce->getDateExpiration()->format('Y-m-d\TH:i:s'),
                'lieu'              => $annonce->getLieu(),
                'nombrePoste'       => $annonce->getNombrePoste(),
                'shortDescription'  => $annonce->getShortDescription(),
                'typeContrat'       => $annonce->getTypeContrat()->getNom(),
                'budgetAnnonce'     => $this->extension->getBudgetAnnonceStrById($annonce->getId()),
                'primeAnnonce'      => $this->extension->getPrimeAnnonceStrById($annonce->getId()),
                'competences'       => [],
                'applications'      => [],
                'langues'           => [],
                'annonceVues'       => [],
            ];

            foreach ($annonce->getCompetences() as $competence) {
                $body['competences'][] = [
                    'nom' => $competence->getNom(),
                ];
            }

            foreach ($annonce->getApplications() as $application) {
                $body['applications'][] = [
                    'id'       => $application->getId(),
                ];
            }

            foreach ($annonce->getLangues() as $langue) {
                $body['langages'][] = [
                    'nom' => $langue()->getNom(),
                ];
            }

            foreach ($annonce->getAnnonceVues() as $application) {
                $body['annonceVues'][] = [
                    'id'       => $application->getId(),
                ];
            }

            $this->elasticsearch->index([
                'index' => 'joblisting_white_label_index',
                'id'    => $annonce->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Joblisting ID: ' . $annonce->getId());
        }

        return Command::SUCCESS;
    }
}
