<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Form\Facebook\RegistrationFormType;
use App\Security\AppAuthenticator;
use App\Entity\Facebook\ContestEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Manager\Facebook\ContestEntryManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\Facebook\ContestEntryUserFormType;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Facebook\ContestEntryAcceptFormType;
use App\Form\Facebook\ContestEntryProfileFormType;
use App\Manager\BusinessModel\CreditManager;
use App\Service\Mailer\MailerService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

#[Route('/concours/facebook')]
class ConcoursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private Security $security,
        private UserService $userService,
        private ContestEntryManager $contestEntryManager,
        private CreditManager $creditManager,
    ) {}

    #[Route('/', name: 'app_concours_facebook')]
    public function contest(Request $request): Response
    {
        return $this->render('concours/concours.html.twig', []);
    }

    #[Route('/etape-1', name: 'app_concours_etape_1', options: ['sitemap' => true])]
    public function stepOne(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response
    {
        return $this->redirectToRoute('app_concours_facebook');
        $this->requestStack->getSession()->set('fromPath', 'app_concours_etape_2');
        $user = new User();
        $user->setDateInscription(new \DateTime());
        $user->setType(User::ACCOUNT_CANDIDAT);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $this->em->persist($user);
            $this->em->flush();    
            $this->creditManager->ajouterCreditsBienvenue($user, 200);
    
            $response = $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
    
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $responseData = [
                    'status' => 'success',
                    'id' => 'newUser',
                    'message' => 'Connexion réussie, bienvenue ' . $user->getPrenom(),
                ];
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('turbo_stream/success_message.html.twig', $responseData);
            }
        
            return $response;        
        }else {
            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('concours/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }
        return $this->render('concours/step_one.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/etape-2', name: 'app_concours_etape_2')]
    public function stepTwo(Request $request): Response
    {
        return $this->redirectToRoute('app_concours_facebook');
        if (!$this->checkUser()) {
            return $this->redirectToRoute('app_concours_etape_1');
        }

        /** @var  User $user */
        $user = $this->userService->getCurrentUser();
        if ($user->getEntrepriseProfile() instanceof EntrepriseProfile) {
            return $this->redirectToRoute('app_concours_error');
        }

        $contestEntryId = $this->requestStack->getCurrentRequest()->get('contestEntry', null);

        $contestEntry = $contestEntryId
            ? $this->em->getRepository(ContestEntry::class)->find($contestEntryId)
            : $this->em->getRepository(ContestEntry::class)->findLastByUser($this->security->getUser());

        if (!$contestEntry instanceof ContestEntry) {
            $contestEntry = $this->contestEntryManager->init($this->security->getUser());
        }
        if ($contestEntry->getStatus() === ContestEntry::STATUS_VALIDATED) {
            return $this->redirectToRoute('app_concours_confirmation');
        }
        $form = $this->createForm(ContestEntryAcceptFormType::class, $contestEntry);
        if ($this->processForm($form, $request, 2)) {
            return $this->redirectToRoute('app_concours_etape_3');
        }

        return $this->render('concours/step_two.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/etape-3', name: 'app_concours_etape_3')]
    public function stepThree(Request $request): Response
    {
        return $this->redirectToRoute('app_concours_facebook');
        if (!$contestEntry = $this->getContestEntryOrRedirect()) {
            return $contestEntry;
        }
        if(!$contestEntry instanceof ContestEntry){
            return $this->redirectToRoute('app_concours_etape_1');
        }
        $form = $this->createForm(ContestEntryUserFormType::class, $contestEntry);
        if ($this->processForm($form, $request, 3)) {
            return $this->redirectToRoute('app_concours_etape_4');
        }

        return $this->render('concours/step_three.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/etape-4', name: 'app_concours_etape_4')]
    public function stepFour(
        Request $request,
        FileUploader $fileUploader,
        ProfileManager $profileManager
    ): Response {
        if (!$contestEntry = $this->getContestEntryOrRedirect()) {
            return $contestEntry;
        }
        if(!$contestEntry instanceof ContestEntry){
            return $this->redirectToRoute('app_concours_etape_1');
        }

        /** @var  User $user */
        $user = $this->userService->getCurrentUser();
        if(!$user instanceof User){
            return $this->redirectToRoute('app_concours_etape_1');
        }
        if ($user->getCandidateProfile() instanceof CandidateProfile) {
            $contestEntry->setCandidateProfile($user->getCandidateProfile());
        }

        $form = $this->createForm(ContestEntryProfileFormType::class, $contestEntry);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contestEntry->setStep(4);
            $this->updateCandidateProfile($form, $contestEntry, $fileUploader, $profileManager);
            $this->em->flush();
            return $this->redirectToRoute('app_concours_etape_5');
        }

        return $this->render('concours/step_four.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/etape-5', name: 'app_concours_etape_5')]
    public function stepFive(
        Request $request, 
        MailerService $mailerService,
        UrlGeneratorInterface $urlGenerator,
    ): Response
    {
        return $this->redirectToRoute('app_concours_facebook');
        if (!$contestEntry = $this->getContestEntryOrRedirect()) {
            return $contestEntry;
        }
        if(!$contestEntry instanceof ContestEntry){
            return $this->redirectToRoute('app_concours_etape_1');
        }

        /** @var  User $user */
        $user = $this->userService->getCurrentUser();
        if(!$user instanceof User){
            return $this->redirectToRoute('app_concours_etape_1');
        }
        if ($user->getCandidateProfile() instanceof CandidateProfile) {
            $contestEntry->setCandidateProfile($user->getCandidateProfile());
        }

        $form = $this->createForm(ContestEntryUserFormType::class, $contestEntry);
        if ($this->processForm($form, $request, 5)) {
            if(!$contestEntry->isEmailSend()){
                // send email
                $mailerService->send(
                    $user->getEmail(),
                    "Votre participation au concours Olona Talents est confirmée !",
                    "concours.mail.twig",
                    [
                        'user' => $user,
                        'dashboard_url' => $urlGenerator->generate(
                            'app_connect',
                            [], 
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ]
                );
                $contestEntry->setEmailSend(true);
                $this->contestEntryManager->save($contestEntry);
            }

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('concours/_confirmation.stream.html.twig');
            }
            return $this->redirectToRoute('app_concours_confirmation');
        }

        return $this->render('concours/step_five.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/confirmation', name: 'app_concours_confirmation')]
    public function confirmation(): Response
    {
        return $this->redirectToRoute('app_concours_facebook');
        return $this->render('concours/confirmation.html.twig');
    }

    #[Route('/error', name: 'app_concours_error')]
    public function error(): Response
    {
        return $this->render('concours/error.html.twig');
    }

    // --------- Private Helper Methods ---------

    private function checkUser(): bool
    {
        return $this->security->getUser() !== null;
    }

    private function getContestEntryOrRedirect()
    {
        /** @var  User $user */
        $user = $this->userService->getCurrentUser();
        $contestEntryId = $this->requestStack->getCurrentRequest()->get('contestEntry', null);

        if (!$user) {
            return $this->redirectToRoute('app_concours_etape_1');
        }

        $contestEntry = $contestEntryId
            ? $this->em->getRepository(ContestEntry::class)->find($contestEntryId)
            : $this->em->getRepository(ContestEntry::class)->findLastByUser($user);

        if (!$contestEntry instanceof ContestEntry) {
            return $this->redirectToRoute('app_concours_etape_2');
        }

        return $contestEntry;
    }

    private function processForm($form, Request $request, int $step): bool
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contestEntry = $form->getData();
            $contestEntry->setStep($step);
            $this->em->persist($contestEntry);
            $this->em->flush();
            $this->requestStack->getSession()->set('contestEntryId', $contestEntry->getId());
            return true;
        }
        return false;
    }

    private function updateCandidateProfile($form, ContestEntry $contestEntry, FileUploader $fileUploader, ProfileManager $profileManager)
    {
        /** @var  User $user */
        $user = $this->userService->getCurrentUser();
        $cvFile = $form->get('candidateProfile')->get('cv')->getData();
        if ($cvFile) {
            $fileName = $fileUploader->upload($cvFile, $contestEntry->getCandidateProfile());
            $contestEntry->getCandidateProfile()->setCv($fileName[0]);
            $profileManager->saveCV($fileName, $contestEntry->getCandidateProfile());
            $contestEntry->setCvSumbitted(true);
        }
        $user->setCandidateProfile($contestEntry->getCandidateProfile());
        $this->em->persist($user);
    }
}