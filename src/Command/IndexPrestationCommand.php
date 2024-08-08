<?php
namespace App\Command;

use App\Twig\ProfileExtension;
use App\Entity\Prestation;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:index-prestations',
    description: 'Index all prestations to Elasticsearch',
    hidden: false,
    aliases: ['app:index-prestations']
)]
class IndexPrestationCommand extends Command
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
            ->setDescription('Index all prestations to Elasticsearch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prestations = $this->em->getRepository(Prestation::class)->findValidPrestationsElastic();

        foreach ($prestations as $prestation) {
            $body = [
                'titre'                 => $prestation->getTitre(),
                'cleanDescription'      => $prestation->getCleanDescription(),
                'competencesRequises'   => [],
                'secteurs'              => [],
                'fileName'              => $prestation->getFileName(),
                'tarifsProposes'        => $prestation->getTarifPrestation() ? ['value' => (string) $prestation->getTarifPrestation()] : ['value' => ''],
                'modalitesPrestation'   => $prestation->getModalitesPrestation(),
                'specialisations'       => [],
                'disponibilites'        => $prestation->getAvailability() ? ['value' => (string) $prestation->getAvailability()] : ['value' => ''],
                'createdAt'             => $prestation->getCreatedAt(),
                'openai'                => $prestation->getOpenai(),
            ];

            foreach ($prestation->getCompetences() as $specialisation) {
                $body['specialisations'][] = [
                    'nom' => $specialisation->getNom(),
                ];
            }

            $body['secteurs'][] = [
                'nom' => $prestation->getSecteurs() ? $prestation->getSecteurs()->getNom() : 'non définie',
            ];

            $this->elasticsearch->index([
                'index' => 'prestation_index',
                'id'    => $prestation->getId(),
                'body'  => $body,
            ]);

            $output->writeln('Indexed Prestaion ID: ' . $prestation->getId());
        }

        return Command::SUCCESS;
    }
}
