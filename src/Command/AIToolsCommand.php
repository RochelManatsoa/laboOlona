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
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use Symfony\Component\Console\Command\Command;
use App\Repository\AffiliateTool\TagRepository;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use App\Repository\AffiliateTool\CategoryRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsCommand(
    name: 'app:import-tools',
    description: 'Transalte AI tools description',
    hidden: false,
    aliases: ['app:translate-tools']
)]
class AIToolsCommand extends Command
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

    protected function configure()
    {
        $this
            ->setDescription('Exécute la commande app:translate-tools avec des paramètres dynamiques.')
            ->addArgument('start', InputArgument::REQUIRED, 'Le premier paramètre')
            ->addArgument('end', InputArgument::REQUIRED, 'Le deuxième paramètre')
            ->addOption('option', null, InputOption::VALUE_REQUIRED, 'Option à passer à la commande app:translate-tools');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startLoop = $input->getArgument('start'); // Cet argument détermine le début de la boucle
        $endLoop = $input->getArgument('end'); // Cet argument détermine la fin de la boucle
        $option = $input->getOption('option') ? '--option=description' : '--option='.$input->getOption('option');

        // Boucle pour exécuter la commande plusieurs fois
        for ($i = $startLoop; $i <= $endLoop; $i++) {
            $commandLine = sprintf('php bin/console app:import-tool 1 %d %s', $i, $option);

            // Exécution de la commande
            $process = new Process(explode(' ', $commandLine));
            $process->run();

            // Affichage du retour de la commande
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output->writeln($process->getOutput());
        }

        return Command::SUCCESS;
    }
}