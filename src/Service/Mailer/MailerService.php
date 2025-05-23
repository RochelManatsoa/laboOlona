<?php 

namespace App\Service\Mailer;

use App\Entity\BusinessModel\Subcription;
use App\Entity\User;
use App\Entity\Notification;
use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use Symfony\Component\Mime\Address;
use App\Manager\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TemplateEmailRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService 
{
    private $env;
    public function __construct(
        private MailerInterface $mailer,
        private TemplateEmailRepository $templateEmailRepository,
        private NotificationManager $notificationManager,
        private ModerateurManager $moderateurManager,
        private EntityManagerInterface $em,
        private UserService $userService,
        private UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $params
    ){
        $this->env = $params->get('app.env');
    }

    public function send(
        string $to,
        string $subject,
        string $template,
        array $context,
        string $from = '',
        string $replyTo = '',
    ): void
    {
        $email = new TemplatedEmail();
        $sender = $from === '' ? 'support@olona-talents.com': $from;
        $env = 'Olona Talents';
        if ($this->env === 'prod') {
            $email->to($to);
        } else {
            $env = '[Email Preprod] Olona Talents';
            $email->to('support@olona-talents.com'); 
            $email->addTo('miandrisoa.olona@gmail.com');
            $email->addTo('contact@olona-talents.com');
        }
        $email->from(new Address($sender, $env));
        if ($replyTo !== '') {
            $email->replyTo($replyTo);
        }else{
            $email->replyTo('contact@olona-talents.com');
        }
        $email->subject($subject)
            ->htmlTemplate("mails/$template")
            ->context($context)
        ;

        try{

            $this->mailer->send($email);

        }catch(TransportExceptionInterface $transportException){

            throw $transportException;

        }
    }
    
    public function sendMultiple(
        array $to,
        string $subject,
        string $template,
        array $context
    ): void {
        $email = new TemplatedEmail();
        $env = 'Olona Talents';
        if ($this->env === 'prod') {
            foreach ($to as $recipient) {
                $email->addTo($recipient);
            }
        } else {
            $env = '[Email Preprod] Olona Talents';
            $email->to('support@olona-talents.com'); 
            $email->addTo('contact@olona-talents.com');
            $email->addTo('miandrisoa.olona@gmail.com');
        }
        $email
            ->from(new Address('support@olona-talents.com', $env))
            ->replyTo('contact@olona-talents.com')
            ->subject($subject)
            ->htmlTemplate("mails/$template")
            ->context($context);
    
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $transportException) {
            throw $transportException;
        }
    }

    public function sendRelanceEmail(CandidateProfile $profile, string $type, string $categorie, string $compte)
    {
        $emailTemplate = $this->templateEmailRepository->findByTypeAndCategorieAndCompte($type, $categorie, $compte);
        // dd($emailTemplate, $type, $categorie, $compte);

        if ($emailTemplate) {
            $email = new TemplatedEmail();
            $sender = 'support@olona-talents.com';
            $env = 'Olona Talents';
            if ($this->env === 'prod') {
                $email->to($profile->getCandidat()->getEmail());
            } else {
                $env = '[Preprod] Olona Talents';
                $email->to('support@olona-talents.com'); 
            }
            $email 
                ->from(new Address($sender, $env))
                ->replyTo('contact@olona-talents.com')
                ->subject($emailTemplate->getTitre())
                ->htmlTemplate("mails/relance/profile/candidat.html.twig")
                ->context([
                    'user' => $profile->getCandidat(),
                    'contenu' => '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$emailTemplate->getContenu(),
                ])
                ;
    
            try{
    
                $this->mailer->send($email);
                $notification = $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $profile->getCandidat(), Notification::TYPE_PROFIL, $emailTemplate->getTitre(), '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$emailTemplate->getContenu() );
                $this->em->persist($notification);
                $this->em->flush();
    
            }catch(TransportExceptionInterface $transportException){
    
                throw $transportException;
    
            }
        }
    }
    
    public function sendSubcriptionRelanceEmail(User $user, Subcription $subcription, string $categorie, string $compte)
    {
        $prenom = $user->getPrenom();
        $montant = $compte === 'ENTREPRISE' ? '100 000 AR (≈ 20 €)' : '24 000 AR (≈ 5 €)';
        $subject = '';
        $contenu = '';
        $link = $this->urlGenerator->generate('app_tableau_de_bord_entreprise_mes_commandes');
        $nextRenewalDate = $subcription->getEndDate() ? $subcription->getEndDate()->modify('+1 month') : new \DateTimeImmutable('+1 month');
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            $nextRenewalDate->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            'd MMMM yyyy'
        );
        $formattedRenewalDate = $formatter->format($nextRenewalDate);

        switch ($categorie) {
            case 'premium_relance_1':
                $subject = 'Renouvellement de votre abonnement Premium ' . ucfirst(strtolower($compte)) . ' – Olona Talents';
                $contenu = "
                    <p>Bonjour $prenom,</p>
                    <p>Nous vous confirmons que votre abonnement Premium $compte a bien été renouvelé sur la plateforme Olona Talents.</p>
                    <p><strong>🔁 Résumé de l’abonnement</strong><br>
                    Formule : Premium $compte – Mensuel<br>
                    Montant : $montant<br>
                    Durée : 1 mois<br>
                    Date du prochain renouvellement : $formattedRenewalDate</p>

                    <p><strong>💳 Modalités de paiement disponibles</strong><br>
                    - Carte bancaire (CB)<br>
                    - PayPal<br>
                    - Mobile Money (Mvola, Orange Money, Airtel Money)<br>
                    - Virement bancaire<br>
                    - Paiement en espèces auprès de notre équipe</p>

                    <p>⚠️ Merci de bien vouloir effectuer votre règlement dans un délai maximum de 7 jours, si ce n’est pas déjà fait.</p>
                ";
                break;

            case 'premium_relance_2':
                $subject = 'Rappel – Paiement en attente pour votre abonnement Premium Olona Talents';
                $contenu = "
                    <p>Bonjour $prenom,</p>
                    <p>Nous nous permettons de vous adresser ce message de rappel concernant le renouvellement de votre abonnement Premium $compte.</p>
                    <p>À ce jour, nous n’avons pas encore reçu votre règlement de $montant.</p>
                    <p><strong>🕒 Délai de règlement</strong><br>
                    Le règlement doit être effectué dans un délai maximum de 7 jours après renouvellement.</p>
                    <p><strong>💳 Moyens de paiement disponibles</strong><br>
                    - Carte bancaire (CB)<br>
                    - PayPal<br>
                    - Mobile Money (Mvola, Orange Money, Airtel Money)<br>
                    - Virement bancaire<br>
                    - Paiement en espèces auprès de notre équipe</p>
                    <p>Passé ce délai, votre accès pourra être suspendu automatiquement.</p>
                ";
                break;

            case 'premium_relance_3':
                $subject = 'Relance finale – Suspension imminente de votre abonnement Premium Olona Talents';
                $contenu = "
                    <p>Bonjour $prenom,</p>
                    <p>Malgré nos précédents rappels, nous constatons que le règlement de votre abonnement Premium $compte de $montant n’a toujours pas été effectué.</p>
                    <p><strong>⚠️ Suspension et majoration</strong><br>
                    Sans règlement sous 48h :<br>
                    - Suspension temporaire de votre accès Premium<br>
                    - Majoration de 10 % à chaque période de 7 jours supplémentaires</p>
                    <p><strong>💳 Rappel des moyens de paiement</strong><br>
                    - Carte bancaire (CB)<br>
                    - PayPal<br>
                    - Mobile Money (Mvola, Orange Money, Airtel Money)<br>
                    - Virement bancaire<br>
                    - Paiement en espèces auprès de notre équipe</p>
                ";
                break;

            default:
                return;
        }

        $email = new TemplatedEmail();
        $sender = 'support@olona-talents.com';
        $envName = $this->env === 'prod' ? 'Olona Talents' : '[Preprod] Olona Talents';

        $email->from(new Address($sender, $envName))
            ->to($this->env === 'prod' ? $user->getEmail() : 'support@olona-talents.com')
            ->replyTo('contact@olona-talents.com')
            ->subject($subject)
            ->htmlTemplate('mails/relance/abonnement.html.twig')
            ->context([
                'user' => $user,
                'contenu' => $contenu,
                'dashboard_url' => $link,
            ]);

        try {
            $this->mailer->send($email);

            $notification = $this->notificationManager->createNotification(
                $this->moderateurManager->getModerateurs()[1],
                $user,
                Notification::TYPE_PROFIL,
                $subject,
                $contenu
            );

            $this->em->persist($notification);
            $this->em->flush();
        } catch (TransportExceptionInterface $e) {
            throw $e;
        }
    }


    public function sendAbonnementRelanceEmail(User $user, string $type, string $categorie, string $compte)
    {
        $emailTemplate = $this->templateEmailRepository->findByTypeAndCategorieAndCompte($type, $categorie, $compte);
        // dd($emailTemplate, $type, $categorie, $compte);

        if ($emailTemplate) {
            $email = new TemplatedEmail();
            $sender = 'support@olona-talents.com';
            $env = 'Olona Talents';
            if ($this->env === 'prod') {
                $email->to($user->getEmail());
            } else {
                $env = '[Preprod] Olona Talents';
                $email->to('support@olona-talents.com'); 
            }
            $email 
                ->from(new Address($sender, $env))
                ->replyTo('contact@olona-talents.com')
                ->subject($emailTemplate->getTitre())
                ->htmlTemplate("mails/relance/profile/candidat.html.twig")
                ->context([
                    'user' => $user,
                    'contenu' => '<p>Bonjour '.$user->getPrenom().',</p>'.$emailTemplate->getContenu(),
                ])
                ;
    
            try{
    
                $this->mailer->send($email);
                $notification = $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $user, Notification::TYPE_PROFIL, $emailTemplate->getTitre(), '<p>Bonjour '.$user->getPrenom().',</p>'.$emailTemplate->getContenu() );
                $this->em->persist($notification);
                $this->em->flush();
    
            }catch(TransportExceptionInterface $transportException){
    
                throw $transportException;
    
            }
        }
    }

    public function sendMultipleRelanceEmail(CandidateProfile $profile, string $titre, string $contenu)
    {
        $email = new TemplatedEmail();
        $sender = 'support@olona-talents.com';
        $env = 'Olona Talents';
        if ($this->env === 'prod') {
            $email->to($profile->getCandidat()->getEmail());
        } else {
            $env = '[Preprod] Olona Talents';
            $email->to('support@olona-talents.com'); 
        }
        $email 
            ->from(new Address($sender, $env))
            ->replyTo('contact@olona-talents.com')
            ->subject($titre)
            ->htmlTemplate("mails/relance/profile/candidat.html.twig")
            ->context([
                'user' => $profile->getCandidat(),
                'contenu' => '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$contenu,
            ])
            ;

        try{

            $this->mailer->send($email);
            $notification = 
            $this->notificationManager->createNotification(
                $this->userService->getCurrentUser(), 
                $profile->getCandidat(), Notification::TYPE_PROFIL, $titre, 
                '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$contenu 
            );
            $this->em->persist($notification);
            $this->em->flush();

        }catch(TransportExceptionInterface $transportException){

            throw $transportException;

        }
    }
}
