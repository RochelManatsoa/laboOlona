<?php 

namespace App\Command;

use App\Entity\Prestation;
use App\Entity\Cron\CronLog;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Controller\Dashboard\Moderateur\OpenAi\AnnonceController;

#[AsCommand(
    name: 'app:generate-description',
    description: 'Generate openai description.',
    hidden: false,
    aliases: ['app:generate-description']
)]
class ShortDescriptionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private AnnonceController $annonceController
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Generation openai description joblisting Olona Talents.',
            '==================',
            '',
        ]);

        $startTime = new \DateTime();

        $emailsSent = 0;

        $annonces = $this->em->getRepository(JobListing::class)->findJoblistingsForReport();
        $output->writeln(count($annonces) .' annonces ');
        foreach ($annonces as $annonce) {
            try {
                // Appeler directement la méthode du contrôleur
                $response = $this->annonceController->resume(new \Symfony\Component\HttpFoundation\Request(), $annonce);
                $data = json_decode($response->getContent(), true);

                if ($data['status'] === 'error') {
                    $output->writeln(' - Report failed for '.$annonce->getId().' ' . $data['error']);
                }
                $output->writeln(' - Report saved for '.$annonce->getId());

            } catch (\Exception $e) {
                $output->writeln('Erreur de génération du rapport par IA pour l\'annonce ID: ' . $annonce->getId());
                $output->writeln($e->getMessage());
                return Command::FAILURE;
            }

        }

        $prestations = $this->em->getRepository(Prestation::class)->findPrestationsForReport();
        $output->writeln(count($prestations) .' prestations ');
        foreach ($prestations as $prestation) {
            try {
                // Appeler directement la méthode du contrôleur
                $response = $this->annonceController->resumePrestation(new \Symfony\Component\HttpFoundation\Request(), $prestation);
                $data = json_decode($response->getContent(), true);

                if ($data['status'] === 'error') {
                    $output->writeln(' - Report failed for '.$prestation->getId().' ' . $data['error']);
                }
                $output->writeln(' - Report saved for '.$prestation->getId());

            } catch (\Exception $e) {
                $output->writeln('Erreur de génération du rapport par IA pour la prestation ID: ' . $prestation->getId());
                $output->writeln($e->getMessage());
                return Command::FAILURE;
            }

        }

        $endTime = new \DateTime();

        $cronLog = new CronLog();
        $cronLog->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCommandName('app:generate-description')
            ->setEmailsSent($emailsSent);

        $this->em->persist($cronLog);
        $this->em->flush();

        $output->writeln('AI-generated description saved');


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->writeln('generating short description Olona Talents.');

        return Command::SUCCESS;
    }
}