<?php

namespace App\WhiteLabel\Command\Client1;

use App\WhiteLabel\Entity\Client1\ReferrerProfile;
use App\WhiteLabel\Entity\Client1\Finance\Employe;
use App\WhiteLabel\Entity\Client1\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:client1:create-employe-from-referrers',
    description: 'Create an employe profile for each referrer user.',
    hidden: false,
    aliases: ['app:client1:create-employe-from-referrers']
)]
class CreateEmployeFromReferrerCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var ReferrerProfile[] $referrers */
        $referrers = $this->entityManager->getRepository(ReferrerProfile::class)->findAll();

        foreach ($referrers as $referrerProfile) {
            $user = $referrerProfile->getReferrer();
            if (!$user instanceof User) {
                continue;
            }

            if (!$user->getEmploye()) {
                $employe = new Employe();
                $employe->setUser($user);
                $employe->setNombreEnfants(0);
                $employe->setSalaireBase(500000);
                $this->entityManager->persist($employe);
                $user->setEmploye($employe);
                $io->writeln('Employe created for user ID: ' . $user->getId());
            }

            $roles = $user->getRoles();
            if (!in_array('ROLE_EMPLOYE', $roles, true)) {
                $roles[] = 'ROLE_EMPLOYE';
            }
            $user->setRoles($roles);
            $user->setType(User::ACCOUNT_EMPLOYE);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
        $io->success('Employes created or updated for all referrers.');

        return Command::SUCCESS;
    }
}

