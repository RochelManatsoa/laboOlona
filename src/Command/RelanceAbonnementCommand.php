<?php  
namespace App\Command;

use App\Entity\Cron\CronLog;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\BusinessModel\SubcriptionRepository;

#[AsCommand(
    name: 'app:relance-subcriptions',
    description: 'Envoie des relances email pour les abonnements qui expirent bientôt.',
)]
class RelanceAbonnementCommand extends Command
{
    public function __construct(
        private SubcriptionRepository $subcriptionRepository,
        private MailerService $mailerService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Relance email abonnements Olona Talents',
            '========================================',
            '',
        ]);

        $startTime = new \DateTime();
        $now = new \DateTime();
        $emailsSent = 0;
        $relanceDone = 0;

        $relanceDelays = [
            1 => 5,
            2 => 2,
            3 => 1,
        ];

        foreach ($relanceDelays as $relanceNumber => $daysBeforeEnd) {
            $targetDate = (clone $now)->modify("+{$daysBeforeEnd} days")->format('Y-m-d');

            $subcriptions = $this->subcriptionRepository->findAbonnementsToRelance($targetDate, $relanceNumber);
            foreach ($subcriptions as $subcription) {
                if (
                    $subcription->getRelance() === $relanceNumber - 1 && 
                    $subcription->isActive() &&
                    null !== $subcription->getEndDate()
                ) {
                    $categorie = 'abonnement_' . $relanceNumber;
                    $relanceType = 'RELANCE';
                    $compte = 'ENTREPRISE';
                    if($subcription->getEntreprise() instanceof EntrepriseProfile){
                        $user = $subcription->getEntreprise()->getEntreprise();
                    }
                    if($subcription->getCandidat() instanceof CandidateProfile){
                        $user = $subcription->getCandidat()->getCandidat();
                    }
                    $this->mailerService->sendAbonnementRelanceEmail(
                        $user,
                        $relanceType,
                        $categorie,
                        $compte
                    );

                    $subcription->setRelance($relanceNumber);
                    $this->em->persist($subcription);
                    $emailsSent++;
                }
            }
        }

        // Désactivation des subcriptions expirés
        $expiredAbos = $this->subcriptionRepository->findExpiredActives();
        foreach ($expiredAbos as $expiredAbo) {
            $expiredAbo->setActive(false);
            if($expiredAbo->getEntreprise() instanceof EntrepriseProfile){
                $profile = $expiredAbo->getEntreprise();
                $profile->setIsPremium(false);
            }
            if($expiredAbo->getCandidat() instanceof CandidateProfile){
                $profile = $expiredAbo->getCandidat();
                $profile->setIsPremium(false);
            }
            $this->em->persist($expiredAbo);
            $this->em->persist($profile);
            $relanceDone++;
        }

        $this->em->flush();

        // Enregistrement dans le log
        $endTime = new \DateTime();
        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:relance-subcriptions')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();

        $output->writeln("✅ $emailsSent relances envoyées, $relanceDone subcriptions désactivés.");

        return Command::SUCCESS;
    }
}
