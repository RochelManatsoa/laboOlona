<?php 

namespace App\Command;

use App\Entity\{CandidateProfile, EntrepriseProfile, ModerateurProfile, ReferrerProfile};
use App\Entity\Finance\Employe;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:roles',
    description: 'Attribute roles Olona Talents.',
    hidden: false,
    aliases: ['app:roles']
)]
class RolesCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Attribute roles Olona Talents.',
            '==================',
            '',
        ]);

        // the value returned by someMethod() can be an iterator (https://php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            // Initialiser un tableau vide pour les rôles
            $roles = []; 
        
            // Ajouter des rôles en fonction des profils de l'utilisateur
            if ($user->getCandidateProfile() instanceof CandidateProfile) {
                $roles[] = 'ROLE_CANDIDATE';
            }
            if ($user->getEntrepriseProfile() instanceof EntrepriseProfile) {
                $roles[] = 'ROLE_ENTREPRISE';
            }
            if ($user->getModerateurProfile() instanceof ModerateurProfile) {
                $roles[] = 'ROLE_MODERATEUR';
            }
            if ($user->getReferrerProfile() instanceof ReferrerProfile) {
                $roles[] = 'ROLE_COOPTEUR';
            }
            if ($user->getEmploye() instanceof Employe) {
                $roles[] = 'ROLE_EMPLOYE';
            }
        
            // S'il y a des rôles à attribuer, les définir pour l'utilisateur
            if (!empty($roles)) {
                $user->setRoles($roles);
                $this->em->persist($user);
                $this->em->flush();
            }
        }
        $output->writeln('Languages and sectors initialized');


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->writeln('attribute roles Olona Talents.');

        return Command::SUCCESS;
    }
}