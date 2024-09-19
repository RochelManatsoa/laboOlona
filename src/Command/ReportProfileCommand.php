<?php 

namespace App\Command;

use App\Twig\AppExtension;
use App\Entity\Cron\CronLog;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Controller\Dashboard\Moderateur\OpenAi\CandidatController;
use App\Entity\{CandidateProfile, EntrepriseProfile, ModerateurProfile, ReferrerProfile};

#[AsCommand(
    name: 'app:generate-report',
    description: 'Generate openai report.',
    hidden: false,
    aliases: ['app:generate-report']
)]
class ReportProfileCommand extends Command
{
    public function __construct(
        private CandidateProfileRepository $candidateProfileRepository,
        private MailerService $mailerService,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private CandidatController $candidatController
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Generation openai report profile Olona Talents.',
            '==================',
            '',
        ]);

        $startTime = new \DateTime();

        $emailsSent = 0;

        $profiles = $this->candidateProfileRepository->findProfilesForReport();
        $output->writeln(count($profiles) .' profiles ');
        foreach ($profiles as $profile) {
            if ($profile->getCv() != null) {
                try {
                    // Appeler directement la méthode du contrôleur
                    $response = $this->candidatController->resume(new \Symfony\Component\HttpFoundation\Request(), $profile);
                    $data = json_decode($response->getContent(), true);

                    if ($data['status'] === 'error') {
                        $output->writeln(' - Report failed for '.$this->appExtension->generatePseudo($profile).' ' . $data['error']);
                    }
                    $output->writeln(' - Report saved for '.$this->appExtension->generatePseudo($profile));
                    $emailsSent ++;

                } catch (\Exception $e) {
                    $output->writeln('Erreur de génération du rapport par IA pour le profil ID: ' . $this->appExtension->generatePseudo($profile));
                    $output->writeln($e->getMessage());
                    return Command::FAILURE;
                }

            }
        }

        $endTime = new \DateTime();

        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:generate-report')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();

        $output->writeln('AI-generated report saved');


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->writeln('generating recruitement report Olona Talents.');

        return Command::SUCCESS;
    }
}