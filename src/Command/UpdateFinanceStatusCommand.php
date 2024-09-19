<?php 

namespace App\Command;

use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-statusFinance',
    description: 'Update simulateur statusFinance Olona Talents.',
    hidden: false,
    aliases: ['app:update-statusFinance']
)]
class UpdateFinanceStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Update simulateur status Olona Talents.',
            '==================',
            '',
        ]);

        $simulateurs = $this->em->getRepository(Simulateur::class)->findAll();
        foreach ($simulateurs as $simulateur) {
            if($simulateur->getContrat() instanceof Contrat){
                $simulateur->setStatusFinance(Simulateur::STATUS_SEND);
                if($simulateur->getContrat()->getStatus() === Contrat::STATUS_VALID){
                    $simulateur->setStatusFinance(Simulateur::STATUS_CONTACT);
                }
            }else{
                $simulateur->setStatusFinance(Simulateur::STATUS_LIBRE);
            }
        
            $this->em->persist($simulateur);
            $this->em->flush();
        }
        $output->writeln('Updated!');

        $output->writeln('Whoa!');

        $output->write('You are about to ');
        $output->writeln('update status simulateur Olona Talents.');

        return Command::SUCCESS;
    }
}