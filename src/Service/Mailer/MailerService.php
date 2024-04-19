<?php 

namespace App\Service\Mailer;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MailerService 
{
    private $env;
    public function __construct(
        private MailerInterface $mailer,
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
}