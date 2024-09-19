<?php 

namespace App\Command;

use App\Entity\AffiliateTool\Category;
use App\Twig\AppExtension;
use App\Service\OpenAITranslator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use App\Repository\AffiliateTool\CategoryRepository;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:categories',
    description: 'Creating categories Olona Talents.',
    hidden: false,
    aliases: ['app:categories']
)]
class CategoryCommand extends Command
{
    public function __construct(
        private SluggerInterface $sluggerInterface,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private OpenAITranslator $openAITranslator,
        private CategoryRepository $categoryRepository,
    ){
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->addOption('option', 'o', InputOption::VALUE_REQUIRED, 'Would you want to truncate or create categories?','create')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            '',
            'Creating AI Tools categories Olona Talents',
            '==========================================',
            '',
        ]);

        if($input->getOption('option') === "create"){

            $categories = [
                'AI Marketing Tools',
                'Affiliate Tools',
                'Copywriting Tools',
                'CryptoCurrency',
                'Products Informatique',
            ];
    
            foreach ($categories as $key => $value) {
                $categorie = $this->categoryRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower(html_entity_decode($value)))]);
                if(!$categorie instanceof Category){
                    $output->writeln('Création de la catégorie '. $value);
                    $categorie = new Category();
                }
                $categorie->setNom($value);
                $categorie->setNomFr($this->openAITranslator->translateCategory(
                    html_entity_decode($value) ,
                    'en',
                    'fr'
                ));
                $categorie->setSlug($this->sluggerInterface->slug(strtolower($value)));
                $categorie->setDescription($this->openAITranslator->generateDescription(
                    html_entity_decode($value) ,
                ));
                $this->em->persist($categorie);
                $output->writeln('Catégorie '. $value .' créé');
            }
    
            $this->em->flush();
            $output->writeln('Termminée');
            
            $output->writeln('Catégories initialisées');
        }

        if($input->getOption('option') === "truncate"){

            $categories = $this->categoryRepository->findAll();
    
            $output->writeln('Suppression des catégories');
            foreach ($categories as $categorie) {
                // Dissocier toutes les relations ManyToMany avec affiliateTool
                foreach ($categorie->getAffiliateTool() as $affiliateTool) {
                    $categorie->removeAffiliateTool($affiliateTool);
                }
    
                $this->em->remove($categorie);
            }
    
            $this->em->flush();
            $output->writeln('Termminée');
            
            $output->writeln('Catégories supprimées');
        }


        return Command::SUCCESS;
    }
}