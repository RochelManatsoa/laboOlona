<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\CandidateProfile;
use App\Manager\ModerateurManager;
use App\Manager\NotificationManager;
use App\Entity\Facebook\ContestEntry;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Repository\Facebook\ContestEntryRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:contest-facebook',
    description: 'Add a short description for your command',
    aliases: ['app:contest-facebook']
)]

class ContestFacebookCommand extends Command
{
    public function __construct(
        private ContestEntryRepository $contestEntryRepository,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private NotificationManager $notificationManager,
        private ModerateurManager $moderateurManager,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $contestEntries = $this->contestEntryRepository->findEntryByStatus(ContestEntry::STATUS_SEND);
        
        foreach ($contestEntries as $contestEntry) {
            $this->processContestEntry($contestEntry, $io);
        }

        $this->entityManager->flush();
        $io->success('Traitement terminé avec succès.');

        return Command::SUCCESS;
    }

    private function processContestEntry(ContestEntry $contestEntry, SymfonyStyle $io): void
    {
        $user = $contestEntry->getUser();

        if (!$user instanceof User) {
            $this->removeContestEntry($contestEntry);
            $io->writeln(sprintf('Utilisateur non trouvé pour ContestEntry ID: %d', $contestEntry->getId()));
            return;
        }

        if (!$this->isUserInfoComplete($user)) {
            $this->updateStatus($contestEntry, ContestEntry::STATUS_INFOS_EMPTY, $io);
            return;
        }

        $candidateProfile = $contestEntry->getCandidateProfile();

        if (!$candidateProfile instanceof CandidateProfile || !$candidateProfile->getCv()) {
            $this->handleMissingCv($contestEntry, $user, $candidateProfile);
            $io->writeln(sprintf('CV manquant - mail envoyé pour ContestEntry ID: %d', $contestEntry->getId()));
            return;
        }

        if ($candidateProfile->getLocalisation() === null) {
            $candidateProfile->setLocalisation('MG');
            $this->entityManager->persist($candidateProfile);
        }

        if (!$candidateProfile->getTitre()) {
            $this->updateStatus($contestEntry, ContestEntry::STATUS_INFOS_EMPTY, $io);
            return;
        }

        $newStatus = in_array($candidateProfile->getStatus(), [CandidateProfile::STATUS_VALID, CandidateProfile::STATUS_FEATURED])
            ? ContestEntry::STATUS_VALIDATED
            : ContestEntry::STATUS_PENDING;

        $this->updateStatus($contestEntry, $newStatus, $io);
    }

    private function updateStatus(ContestEntry $contestEntry, string $status, SymfonyStyle $io): void
    {
        $contestEntry->setStatus($status);
        $this->entityManager->persist($contestEntry);
        $io->writeln(sprintf('ContestEntry ID %d mis à jour avec le statut %s.', $contestEntry->getId(), $status));
    }

    private function removeContestEntry(ContestEntry $contestEntry): void
    {
        $this->entityManager->remove($contestEntry);
    }

    private function isUserInfoComplete(User $user): bool
    {
        return $user->getNom() && $user->getPrenom() && $user->getEmail() && $user->getTelephone();
    }

    private function handleMissingCv(ContestEntry $contestEntry, User $user, ?CandidateProfile $candidateProfile = null): void
    {
        $contestEntry->setStatus(ContestEntry::STATUS_CV_EMPTY);

        if ($candidateProfile) {
            $candidateProfile->setRelanceCount($candidateProfile->getRelanceCount() + 1);
            $this->entityManager->persist($candidateProfile);
        }

        $this->sendMissingCvEmail($user);
        $this->entityManager->persist($contestEntry);
    }

    private function sendMissingCvEmail(User $user): void
    {
        $this->mailerService->send(
            $user->getEmail(),
            "CV manquant pour votre candidature au concours Olona Talents",
            "concours_relance.mail.twig",
            [
                'user' => $user,
                'dashboard_url' => $this->urlGenerator->generate(
                    'app_concours_etape_4',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
        
        $notification = $this->notificationManager->createNotification(
            $this->moderateurManager->getModerateurs()[1],
            $user,
            Notification::TYPE_PROFIL,
            'CV manquant pour votre candidature au concours Olona Talents',
            '
            <p>Bonjour '.$user->getPrenom().'</p>

            <p>
                Nous avons bien pris en compte votre inscription au concours <strong>Olona Talents</strong>. 🎉  
                Cependant, nous avons remarqué que votre CV n’a pas encore été reçu. Or, il est indispensable pour valider définitivement votre participation.
            </p>

            <div>
                <strong>📌 Pour finaliser votre participation, assurez-vous que :</strong>  
                <ul>
                    <li>Votre CV est bien au format <strong>PDF</strong>.</li>
                    <li>Il pèse <strong>moins de 1 Mo</strong> (compressez-le si nécessaire).</li>
                </ul>
            </div>

            <p>
                👉 <a href="#" style="text-decoration: none;">Ajoutez votre CV maintenant</a> pour valider votre participation au tirage au sort !
            </p>

            <p>
                🕒 <strong>Rappel :</strong>  
                <ul>
                    <li>🏆 3 gagnants sont tirés au sort chaque semaine pendant 3 semaines.</li>
                    <li>📅 Les tirages au sort ont lieu tous les vendredis à 16h.</li>
                    <li>📢 Les résultats sont annoncés sur notre page Facebook.</li>
                </ul>
            </p>

            <p>
                Pour ne rien manquer, suivez dès maintenant notre page Facebook :
            </p>

            <p>
                👉 <a href="https://www.facebook.com/olonatalents" style="text-decoration: none;">Suivre notre page Facebook</a>
            </p>

            <p>
                Si vous avez rencontré une difficulté pour ajouter votre CV, notre équipe est à votre disposition pour vous aider.  
                <strong>N’hésitez pas à nous contacter !</strong>
            </p>

            <p>
                Bonne chance et à bientôt sur <strong>Olona Talents</strong> ! 🚀  
            </p>
           '
        );
        
        $this->entityManager->persist($notification);
    }
}