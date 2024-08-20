<?php 

namespace App\Command;

use App\Entity\BusinessModel\Credit;
use App\Manager\BusinessModel\CreditManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:attribute-credit',
    description: 'Give 200 credit for all users',
    hidden: false,
    aliases: ['app:attribute-credit']
)]
class AttributeCreditCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private CreditManager $creditManager,
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exécute la commande app:attribute-credit Give 200 credit for all users.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findAll();
        
        foreach ($users as $key => $user) {
            if(!$user->getCredit() instanceof Credit){
                $credit = $this->creditManager->init();
                $user->setCredit($credit);
                $this->em->persist($user);
            }
        }

        $this->em->flush();
        $io->success('Les crédits initials ont été ajoutées avec succès.');

        return Command::SUCCESS;
    }
}