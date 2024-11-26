<?php 

namespace App\Command;

use App\Entity\Cron\CronLog;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-relance',
    description: 'Relance profile crontab.',
    hidden: false,
    aliases: ['app:send-relance']
)]
class RelanceProfileCommand extends Command
{
    public function __construct(
        private CandidateProfileRepository $candidateProfileRepository,
        private MailerService $mailerService,
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Relance email profile Olona Talents.',
            '==================',
            '',
        ]);

        $startTime = new \DateTime();

        $relanceIntervals = [
            1 => ['daysSinceCreation' => 3, 'daysSinceLastRelance' => 0],
            2 => ['daysSinceCreation' => 3, 'daysSinceLastRelance' => 7],
            3 => ['daysSinceCreation' => 3, 'daysSinceLastRelance' => 14],
        ];

        $emailsSent = 0;

        foreach ($relanceIntervals as $relanceNumber => $interval) {
            $profiles = $this->candidateProfileRepository->findProfilesToRelance($interval['daysSinceCreation'], $interval['daysSinceLastRelance'], $relanceNumber);
            foreach ($profiles as $profile) {
                if ($profile->getRelanceCount() === $relanceNumber - 1) {
                    $categorie = 'relance_' . $relanceNumber;
                    $relanceType = 'PROFIL';
                    $compte = 'CANDIDAT'; 

                    $this->mailerService->sendRelanceEmail($profile, $relanceType, $categorie, $compte);
                    $profile->incrementRelanceCount();
                    $this->em->persist($profile);
                    $emailsSent++;
                }
            }
        }

        $this->em->flush();

        $endTime = new \DateTime();

        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:send-relance')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();
        $output->write('You are about to ');
        $output->writeln('sending relance emails Olona Talents.');

        return Command::SUCCESS;
    }
}