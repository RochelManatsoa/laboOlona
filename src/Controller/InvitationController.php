<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Entity\Referrer\Referral;
use App\Form\NewPasswordFormType;
use App\Form\RegistrationFormType;
use App\Entity\Entreprise\JobListing;
use App\Entity\Moderateur\Invitation;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\InvitationUsedException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use App\Repository\Moderateur\InvitationRepository;
use App\Security\AppAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\UserAuthenticator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class InvitationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvitationRepository $repository,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
        private EmailVerifier $emailVerifier,
        private UserAuthenticatorInterface $userAuthenticator,
        private AppAuthenticator $appAuthenticator,
        private UserPasswordHasherInterface $userPasswordHasher
    )
    {}

    #[Route('/invitation/{uuid}', name: 'app_invitation')]
    public function index(Request $request, Invitation $invitation): Response
    {
        if($invitation->getStatus() === Invitation::STATUS_USED){
            throw new InvitationUsedException('L\'invitation a été déjà utilisé ');
        }

        $user = $invitation->getReader();
        $form = $this->createForm(NewPasswordFormType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // encode the plain password
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $invitation = $this->repository->findOneBy(['reader' => $user->getId()]);
            $invitation->setUsedAt(new DateTime());
            $invitation->setStatus(Invitation::STATUS_USED);
            $this->em->persist($user);
            $this->em->flush();
            
            // Connecter l'utilisateur après la vérification de l'e-mail
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);


            $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $user->getEmail()]);
            if($refered instanceof Referral){
                $refered->setStep(2);
                $this->em->persist($refered);
                $this->em->flush();
            }
            return $this->redirectToRoute('app_connect');
        }

        return $this->render('invitation/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/invitation/referral/{referralCode}', name: 'app_invitation_referral')]
    public function referral(Request $request, string $referralCode): Response
    {        
        // dd($referralCode);
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[4][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $referralCode)) {
            throw new InvitationUsedException('Le format du code de parrainage est invalide.');
        }
        $cooptation = $this->em->getRepository(Referral::class)->findOneBy(['referralCode' => $referralCode]);
        if(!$cooptation){
            throw new InvitationUsedException('Le jeton d\'annonce est introuvable dans la session.');
        }
        if($cooptation->getAnnonce()->getStatus() !== JobListing::STATUS_PUBLISHED){
            throw new InvitationUsedException('L\'annonce a été déjà archivée ');
        }
        $annonce = $cooptation->getAnnonce();
        $referrer = $cooptation->getReferredBy();
        $this->requestStack->getSession()->set('referralCode', $cooptation->getReferralCode());
        $this->requestStack->getSession()->set('referredEmail', $cooptation->getReferredEmail());
        $user = (new User())->setEmail($cooptation->getReferredEmail());
        $user->setDateInscription(new DateTime());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->em->persist($user);
            $this->em->flush();

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

            $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $user->getEmail()]);
            if($refered instanceof Referral){
                $refered->setStep(2);
                $this->em->persist($refered);
                $this->em->flush();
            }

            return $this->userAuthenticator->authenticateUser(
                $user,
                $this->appAuthenticator,
                $request
            );
        }


        //     return $this->redirectToRoute('app_connect');
        // }

        return $this->render('invitation/cooptation.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }
}
