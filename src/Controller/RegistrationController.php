<?php

namespace App\Controller;

use App\Entity\Referrer\Referral;
use DateTime;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\AppAuthenticator;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationController extends AbstractController
{

    public function __construct(
        private EmailVerifier $emailVerifier,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $user->setDateInscription(new DateTime());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('support@olona-talents.com', 'Olona Talents'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre inscription sur olona-talents.com')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['user' => $user])
            );
            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_email_sending');

            // return $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository, TokenStorageInterface $tokenStorage): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // Connecter l'utilisateur après la vérification de l'e-mail
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse email a été bien vérifiée.');
        $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $user->getEmail()]);
        if($refered instanceof Referral){
            $refered->setStep(2);
            $this->em->persist($refered);
            $this->em->flush();
        }
        
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/email/sending', name: 'app_email_sending')]
    public function emailSending(): Response
    {        
        return $this->render('registration/email-sending.html.twig', []);
    }
}
