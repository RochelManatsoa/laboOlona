<?php 

namespace App\Command;

use App\Entity\Cron\CronLog;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:notify-candidate',
    description: 'Import AffiliateTools from postin.store',
    hidden: false,
    aliases: ['app:notify-candidate']
)]
class NotifyCandidateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobListingRepository $jobListingRepository,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private MailerService $mailerService,
        private CandidateProfileRepository $candidateProfileRepository,
    ){
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Envoie un email pour une annonce de recrutement')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startTime = new \DateTime();
        $emailsSent = 0;

        $jobListings = $this->jobListingRepository->findJoblistingsForNotification();
        foreach ($jobListings as $jobListing) {
            $candidats = $this->candidateProfileRepository->findBySecteur($jobListing->getSecteur());
            foreach ($candidats as $candidat) {
                if ($candidat->getCandidat() && $candidat->getCandidat()->getEmail()) {
                    // Envoi de l'email au candidat
                    $this->mailerService->send(
                        $candidat->getCandidat()->getEmail(),
                        "Nouvelle opportunité dans votre secteur d'activité",
                        "candidat/nouvelle_opportunite.html.twig",
                        [
                            'user' => $candidat->getCandidat(),
                            'details_annonce' => $jobListing,
                            'dashboard_url' => $this->urlGeneratorInterface->generate('app_dashboard_candidat_annonce_show', ['jobId' => $jobListing->getJobId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]
                    );
                    $emailsSent++;
                    $io->writeln(sprintf('Mail envoyé à : %d', $candidat->getId()));
                }
            }
            $jobListing->setIsNotified(true);
            $this->em->persist($jobListing);
            $this->em->flush();
        }
        $endTime = new \DateTime();

        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:notify-candidate')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();

        $io->success('Mail de notification envoyé pour les annonces');

        return Command::SUCCESS;
    }
}