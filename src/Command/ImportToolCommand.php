<?php 

namespace App\Command;

use DateTime;
use App\Data\ImportData;
use App\Twig\AppExtension;
use App\Service\WooCommerce;
use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Tag;
use App\Service\OpenAITranslator;
use App\Entity\AffiliateTool\Category;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use Symfony\Component\Console\Command\Command;
use App\Repository\AffiliateTool\TagRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use App\Repository\AffiliateTool\CategoryRepository;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:import-tool',
    description: 'Import AffiliateTools from postin.store',
    hidden: false,
    aliases: ['app:import-tool']
)]
class ImportToolCommand extends Command
{
    public function __construct(
        private WooCommerce $wooCommerce,
        private AffiliateToolRepository $affiliateToolRepository,
        private SluggerInterface $sluggerInterface,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private OpenAITranslator $openAITranslator,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ){
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('per_page', InputArgument::REQUIRED, 'Number of AffiliateTools per page.')
            ->addArgument('page', InputArgument::REQUIRED, 'Page.')
            ->addOption('option', 'o', InputOption::VALUE_REQUIRED, 'Would you want to translate description?','description')
            // ...
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Import Affiliate Tool from postin.store',
            '=======================================',
            '',
        ]);

        // the value returned by someMethod() can be an iterator (https://php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        $importData = new ImportData();
        $importData->page = $input->getArgument('page');
        $tool = $this->affiliateToolRepository->find($input->getArgument('per_page'));
        if($tool instanceof AffiliateTool){
            $output->writeln('Traduction de '. $tool->getNom());
            if($input->getOption('option') === "description"){
                $startTime = new DateTime(); // Heure de début
                $output->writeln('Traduction de la description ' . $tool->getNom() . ' à ' . $startTime->format('Y-m-d H:i:s'));
                $descriptionFr = $this->openAITranslator->trans($tool->getDescriptionEn());
                $endTime = new DateTime(); // Heure de fin
                $interval = $startTime->diff($endTime); // Calcul de la différence
                $output->writeln(' ===> Traduction terminée pour ' . $tool->getNom() . ' à ' . $endTime->format('Y-m-d H:i:s'));
                $output->writeln('     Durée de traitement: ' . $interval->format('%i minutes %s secondes'));
            }
            if($input->getOption('option') === "short_description"){
                $startTime = new DateTime(); // Heure de début
                $output->writeln('Traduction de la description ' . $tool->getNom() . ' à ' . $startTime->format('Y-m-d H:i:s'));
                $shortDescriptionFr = $this->openAITranslator->trans($tool->getShortDescription());
                $endTime = new DateTime(); // Heure de fin
                $interval = $startTime->diff($endTime); // Calcul de la différence
                $output->writeln(' ===> Traduction terminée pour ' . $tool->getNom() . ' à ' . $endTime->format('Y-m-d H:i:s'));
                $output->writeln('     Durée de traitement: ' . $interval->format('%i minutes %s secondes'));
            }
            if ($input->getOption('option') === "slogan") {
                $sloganFr = $this->openAITranslator->translateCategory($tool->getSlogan(), 'en', 'fr');
                $output->writeln(' -> Traduction du slogan effectuée ');
            }
            if($input->getOption('option') === "description"){
                $tool->setDescriptionFr($descriptionFr);
            }
            if($input->getOption('option') === "short_description"){
                $tool->setShortDescriptionFr($shortDescriptionFr);
            }
            if($input->getOption('option') === "slogan"){
                $tool->setSloganFr($sloganFr);
            }
            $tool->setStatus('PENDING');
            $this->em->persist($tool);
            $this->em->flush(); // S'assure que tous les produits restants sont flushés
            $output->writeln(' -> Tous les produits restants sont flushés ');
            $this->em->clear(); // Nettoie l'EntityManager à la fin
        }else{
            $output->writeln(' -> AI Tools Invalide');
        }


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->writeln('You are about to ');
        $output->writeln('importing AffiliateTools on Olona Talents.');

        return Command::SUCCESS;
    }
}