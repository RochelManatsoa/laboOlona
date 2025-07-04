<?php

namespace App\WhiteLabel\Command\Client1;

use App\WhiteLabel\Entity\Client1\User;
use App\WhiteLabel\Manager\Client1\ProfileManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\WhiteLabel\Repository\Client1\ReferrerProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:client1:create-employes-for-referrers',
    description: 'Create an employe for each referrer',
    hidden: false,
    aliases: ['app:client1:create-employes-for-referrers']
)]
class CreateEmployesForReferrersCommand extends Command
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private ReferrerProfileRepository $referrerProfileRepository,
        private ProfileManager $profileManager,
    ) {
        parent::__construct();
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Creating employe profiles for all referrers',
            '=========================================',
            '',
        ]);

        $profiles = $this->referrerProfileRepository->findAll();
        foreach ($profiles as $profile) {
            $user = $profile->getReferrer();
            if (!$user instanceof User) {
                continue;
            }

            if (!$user->getEmploye()) {
                $employe = $this->profileManager->createEmploye($user);
                $this->entityManager->persist($employe);
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

        $output->writeln('Done.');

        return Command::SUCCESS;
    }
}
