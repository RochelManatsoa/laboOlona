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
    name: 'app:import-tools',
    description: 'Import AffiliateTools from postin.store',
    hidden: false,
    aliases: ['app:import-tools']
)]
class ImportToolsCommand extends Command
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
        $importData->per_page = $input->getArgument('per_page');
        $products = $this->wooCommerce->importProduct($importData);
        
        $counter = 0;
        foreach ($products as $product) {
            $slug = $this->sluggerInterface->slug(strtolower($product['name']));
            $description = $this->appExtension->filterContent($this->appExtension->doShortcode($product['description']));
            $descriptionFr = null;
            if($input->getOption('option') === "description"){
                $startTime = new DateTime(); // Heure de début
                $output->writeln('Début de la mise à jour de ' . $product['name'] . ' à ' . $startTime->format('Y-m-d H:i:s'));
                $descriptionFr = $this->openAITranslator->trans($description);
                $endTime = new DateTime(); // Heure de fin
                $interval = $startTime->diff($endTime); // Calcul de la différence
                $output->writeln(' ===> Modification terminée pour ' . $product['name'] . ' à ' . $endTime->format('Y-m-d H:i:s'));
                $output->writeln('     Durée de traitement: ' . $interval->format('%i minutes %s secondes'));
            }
            $short_description = $this->appExtension->filterContent($this->appExtension->doShortcode($product['short_description']));
            $slogan = $this->appExtension->filterContent($this->appExtension->doShortcode($product['slogan']));
            $affiliateTool = $this->affiliateToolRepository->findOneBy(['slug' => $slug]);

            if(!$affiliateTool instanceof AffiliateTool){
                $output->writeln('Création de l\'outil '. $product['name']);
                $affiliateTool = new AffiliateTool();
                $affiliateTool->setSlug($slug);
                $output->writeln(' -> Création terminée ');
            }

            $affiliateTool->setNom($product['name']);
            $affiliateTool->setDescription($product['description']);
            $affiliateTool->setDescriptionEn($description);
            $output->writeln('Traduction de la description de '. $product['name']);
            // Vérification et traduction de la description
            if (empty($affiliateTool->getDescriptionFr())) {
                $affiliateTool->setDescriptionFr($descriptionFr);
                $output->writeln(' -> Traduction de la description effectuée ');
            }
            $affiliateTool->setLienAffiliation($product['external_url']);
            $affiliateTool->setCommission(0.90);
            $affiliateTool->setType($product['status']);
            $affiliateTool->setImage($product['images'][0]->src);
            $affiliateTool->setCustomId($product['id']);
            $affiliateTool->setShortDescription($short_description);
            $output->writeln('Traduction de la description courte de '. $product['name']);
            // Vérification et traduction de la description courte
            if (empty($affiliateTool->getShortDescriptionFr()) && $input->getOption('option') === "short_description") {
                $short_descriptionFr = $this->openAITranslator->translate($short_description, 'en', 'fr');
                $affiliateTool->setShortDescriptionFr($short_descriptionFr);
                $output->writeln(' -> Traduction de la description courte effectuée ');
            }
            $affiliateTool->setSlogan($slogan);
            $output->writeln('Traduction du slogan '. $product['name']);
            // Vérification et traduction du slogan
            if (empty($affiliateTool->getSloganFr()) && $input->getOption('option') === "slogan") {
                $sloganFr = $this->openAITranslator->translateCategory($slogan, 'en', 'fr');
                $affiliateTool->setSloganFr($sloganFr);
                $output->writeln(' -> Traduction du slogan effectuée ');
            }
            $affiliateTool->setPrix(number_format(floatval($product['price']), 2, '.', ''));
            $affiliateTool->setCreeLe(new DateTime($product['date_created']));
            $affiliateTool->setEditeLe(new DateTime());
            $affiliateTool->setRelatedIds($product['related_ids']);

            $this->em->persist($affiliateTool);

            if (++$counter % 5 == 0) {
                $this->em->flush(); // Flush par lots de 5
                $output->writeln(' -> Flush par lots de 5 ');
                $this->em->clear(); // Libère les objets de la mémoire
                $output->writeln(' -> Liberation des objets de la mémoire ');
            }
        }

        $this->em->flush(); // S'assure que tous les produits restants sont flushés
        $output->writeln(' -> Tous les produits restants sont flushés ');
        $this->em->clear(); // Nettoie l'EntityManager à la fin
        $output->writeln(' -> Nettoyage de l\'EntityManager');


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->writeln('You are about to ');
        $output->writeln('importing AffiliateTools on Olona Talents.');

        return Command::SUCCESS;
    }
}