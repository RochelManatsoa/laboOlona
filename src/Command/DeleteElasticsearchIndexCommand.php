<?php

namespace App\Command;

use App\Service\ElasticsearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:delete-elasticsearch-index',
    description: 'Delete an Elasticsearch index',
    hidden: false,
    aliases: ['app:delete-elasticsearch-index']
)]
class DeleteElasticsearchIndexCommand extends Command
{
    
    public function __construct(private ElasticsearchService $elasticsearch)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Delete an Elasticsearch index')
            ->addArgument('index', InputArgument::REQUIRED, 'The name of the index to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $index = $input->getArgument('index');

        try {
            $this->elasticsearch->deleteIndex($index);
            $io->success(sprintf('Index "%s" deleted successfully.', $index));
        } catch (\Exception $e) {
            $io->error('Error deleting index: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
