<?php 

namespace App\Command;

use App\Twig\AppExtension;
use App\Entity\Cron\CronLog;
use App\Entity\Errors\ErrorLog;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Controller\Dashboard\Moderateur\OpenAi\CandidatController;
use App\Service\ErrorLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        private ErrorLogger $errorLogger,
        private UrlGeneratorInterface $urlGenerator,
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
                    $response = $this->candidatController->analyse(new \Symfony\Component\HttpFoundation\Request(), $profile);
                    $data = json_decode($response->getContent(), true);

                    if ($data['status'] === 'error') {
                        $output->writeln(' - Report failed for '.$this->appExtension->generatePseudo($profile).' ' . $data['error']);
                        
                        $errorLog = new ErrorLog();
                        $url = '/api/openai/generate/'.$profile->getId();
                        $longueurMax = 255; 
                        $messageTronque = mb_substr($data['error'], 0, $longueurMax);
                        $errorLog->setMessage($messageTronque)
                            ->setType('openai') 
                            ->setUrl($url) 
                            ->setFileName('/var/www/olonaTalents/laboOlona/src/Controller/Dashboard/Moderateur/OpenAi/CandidateController') 
                            ->setLineNumber(96) 
                            ->setErrorObject($data['error'])
                            ->setErrorMessage($data['error'])
                            ->setUserAgent('openai') 
                            ->setUserId(null) 
                            ->setCreatedAt(new \DateTime()); 

                        $this->errorLogger->logError($errorLog);
                    }
                    $output->writeln(' - Report saved for '.$this->appExtension->generatePseudo($profile));
                    $emailsSent ++;

                } catch (\Exception $e) {
                    $output->writeln('Erreur de génération du rapport par IA pour le profil ID: ' . $this->appExtension->generatePseudo($profile));
                    $output->writeln($e->getMessage());
                    $this->mailerService->send(
                        'miandrisoa.olona@gmail.com',
                        "Erreur génération rapport IA pour le profil ID: " . $this->appExtension->generatePseudo($profile),
                        'error/user_openai.mail.twig',
                        [
                            'message' => $e->getMessage(),
                            'candidat' => $this->appExtension->generatePseudo($profile),
                            'dashboard_url' => $this->urlGenerator->generate(
                                'app_dashboard_moderateur_profile_candidat_view', 
                                [
                                    'id' => $profile->getId()
                                ], 
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                        ]
                    );
                    return Command::FAILURE;
                }

            }else{
                $this->mailerService->send(
                    'miandrisoa.olona@gmail.com',
                    "Erreur génération rapport IA pour le profil ID: " . $this->appExtension->generatePseudo($profile),
                    'error/user_openai.mail.twig',
                    [
                        'message' => 'CV manquant et pourtant validé',
                        'candidat' => $this->appExtension->generatePseudo($profile),
                        'dashboard_url' => $this->urlGenerator->generate(
                            'app_dashboard_moderateur_profile_candidat_view', 
                            [
                                'id' => $profile->getId()
                            ], 
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ]
                );
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
        $output->write('You are about to ');
        $output->writeln('generating recruitement report Olona Talents.');

        return Command::SUCCESS;
    }
}