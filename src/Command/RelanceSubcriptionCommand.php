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
    description: 'Envoie les relances email pour les abonnements Premium et désactive ceux expirés.'
)]
class RelanceSubcriptionCommand extends Command
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

        // Délais de relance : J, J+3, J+7
        $relanceDelays = [
            1 => 0,
            2 => 3,
            3 => 7,
        ];

        foreach ($relanceDelays as $relanceNumber => $daysAfterStart) {
            $targetDate = (clone $now)->modify("+{$daysAfterStart} days")->format('Y-m-d');
            $subcriptions = $this->subcriptionRepository->findAbonnementsToRelance($targetDate, $relanceNumber);
            foreach ($subcriptions as $subcription) {
                if (
                    $subcription->getRelance() === $relanceNumber - 1 &&
                    null !== $subcription->getEndDate()
                ) {
                    // Détermination du type de compte (ENTREPRISE ou CANDIDAT)
                    $compte = '';
                    $user = null;

                    if ($subcription->getEntreprise() instanceof EntrepriseProfile) {
                        $user = $subcription->getEntreprise()->getEntreprise();
                        $compte = 'ENTREPRISE';
                    } elseif ($subcription->getCandidat() instanceof CandidateProfile) {
                        $user = $subcription->getCandidat()->getCandidat();
                        $compte = 'CANDIDAT';
                    }

                    if ($user) {
                        $categorie = 'premium_relance_' . $relanceNumber;

                        // Envoi du mail correspondant
                        $this->mailerService->sendSubcriptionRelanceEmail(
                            $user,
                            $subcription,
                            $categorie,
                            $compte
                        );

                        // Mise à jour du statut de relance
                        $subcription->setRelance($relanceNumber);
                        $subcription->setActive(false);
                        $this->em->persist($subcription);
                        $emailsSent++;
                    }
                }
            }
        }

        // Désactivation automatique des abonnements à J+7
        $expiredAbos = $this->subcriptionRepository->findExpiredActives();
        foreach ($expiredAbos as $expiredAbo) {
            $expiredAbo->setActive(false);
            $expiredAbo->setRelance(3);

            if ($expiredAbo->getEntreprise() instanceof EntrepriseProfile) {
                $profile = $expiredAbo->getEntreprise();
                $profile->setIsPremium(false);
            } elseif ($expiredAbo->getCandidat() instanceof CandidateProfile) {
                $profile = $expiredAbo->getCandidat();
                $profile->setIsPremium(false);
            }

            $this->em->persist($expiredAbo);
            $this->em->persist($profile);
            $relanceDone++;
        }

        $this->em->flush();

        // Enregistrement du log
        $endTime = new \DateTime();
        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:relance-subcriptions')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();

        $output->writeln("✅ $emailsSent relances envoyées, $relanceDone abonnements désactivés.");

        return Command::SUCCESS;
    }
}
