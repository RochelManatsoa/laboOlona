<?php 

namespace App\Service\Mailer;

use App\Entity\Notification;
use App\Entity\CandidateProfile;
use App\Manager\ModerateurManager;
use Symfony\Component\Mime\Address;
use App\Manager\NotificationManager;
use App\Repository\TemplateEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MailerService 
{
    private $env;
    public function __construct(
        private MailerInterface $mailer,
        private TemplateEmailRepository $templateEmailRepository,
        private NotificationManager $notificationManager,
        private ModerateurManager $moderateurManager,
        private EntityManagerInterface $em,
        ParameterBagInterface $params
    ){
        $this->env = $params->get('app.env');
    }

    public function send(
        string $to,
        string $subject,
        string $template,
        array $context,
        string $from = ''
    ): void
    {
        $email = new TemplatedEmail();
        $sender = $from === '' ? 'noreply@olona-talents.com': $from;
        $env = 'Olona Talents';
        if ($this->env === 'prod') {
            $email->to($to);
        } else {
            $env = '[Preprod] Olona Talents';
            $email->to('nirinarocheldev@gmail.com'); 
            $email->addTo('jrandriamalala.olona@gmail.com');
            $email->addTo('miandrisoa.olona@gmail.com');
        }
        $email 
            ->from(new Address($sender, $env))
            ->subject($subject)
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
            $env = '[Preprod] Olona Talents';
            $email->to('nirinarocheldev@gmail.com'); 
            $email->addTo('jrandriamalala.olona@gmail.com');
            $email->addTo('s.maurel@olona-outsourcing.com');
        }
        $email
            ->from(new Address('noreply@olona-talents.com', $env))
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
            $sender = 'noreply@olona-talents.com';
            $env = 'Olona Talents';
            if ($this->env === 'prod') {
                $email->to($profile->getCandidat()->getEmail());
            } else {
                $env = '[Preprod] Olona Talents';
                $email->to('nirinarocheldev@gmail.com'); 
            }
            $email 
                ->from(new Address($sender, $env))
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

    public function sendMultipleRelanceEmail(CandidateProfile $profile, string $titre, string $contenu)
    {
        $email = new TemplatedEmail();
        $sender = 'noreply@olona-talents.com';
        $env = 'Olona Talents';
        if ($this->env === 'prod') {
            $email->to($profile->getCandidat()->getEmail());
        } else {
            $env = '[Preprod] Olona Talents';
            $email->to('nirinarocheldev@gmail.com'); 
        }
        $email 
            ->from(new Address($sender, $env))
            ->subject($titre)
            ->htmlTemplate("mails/relance/profile/candidat.html.twig")
            ->context([
                'user' => $profile->getCandidat(),
                'contenu' => '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$contenu,
            ])
            ;

        try{

            $this->mailer->send($email);
            $notification = $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $profile->getCandidat(), Notification::TYPE_PROFIL, $titre, '<p>Bonjour '.$profile->getCandidat()->getPrenom().',</p>'.$contenu );
            $this->em->persist($notification);
            $this->em->flush();

        }catch(TransportExceptionInterface $transportException){

            throw $transportException;

        }
    }
}