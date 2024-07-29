<?php
namespace App\Command;

use App\Twig\ProfileExtension;
use App\Entity\Entreprise\JobListing;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:index-joblistings',
    description: 'Index all joblistings to Elasticsearch',
    hidden: false,
    aliases: ['app:index-joblistings']
)]
class IndexJobListingsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em, 
        private ElasticsearchService $elasticsearch,
        private ProfileExtension $extension
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index all joblistings to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $annonces = $this->em->getRepository(JobListing::class)->findPublishedJobListing();
        $premiums = $this->em->getRepository(JobListing::class)->findPremiumJobListing();

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
                'index' => 'joblisting_premium_index',
                'id'    => $annonce->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Premium Joblisting ID: ' . $annonce->getId());
        }

        return Command::SUCCESS;
    }
}
