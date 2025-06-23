<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\Mailer\MailerService;
use App\WhiteLabel\Form\Client1\AssistanceType;
use App\WhiteLabel\Entity\Client1\ContactForm;
use App\WhiteLabel\Entity\Client1\User;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use App\WhiteLabel\Form\Client1\Profile\Candidat\Edit\EditCandidateProfile;
use App\WhiteLabel\Form\Client1\EditEntrepriseType;
use App\WhiteLabel\Form\Client1\Finance\EmployeType;
use App\Service\FileUploader;
use App\WhiteLabel\Manager\Client1\ProfileManager;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\ChangePasswordFormType;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private FileUploader $fileUploader,
        private ProfileManager $profileManager,
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_client1_user')]
    public function index(ChartBuilderInterface $chartBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $params = [];

        if ($user && $user->getType() === User::ACCOUNT_CANDIDAT) {
            $candidate = $user->getCandidateProfile();
            $completion = $candidate->getProfileCompletion();
            $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
            $chart->setData([
                'labels' => ['Complété', 'Restant'],
                'datasets' => [[
                    'data' => [$completion, 100 - $completion],
                    'backgroundColor' => ['#F0B621', '#EDEDED'],
                    'borderWidth' => 0,
                ]],
            ]);
            $chart->setOptions([
                'cutout' => '70%',
                'rotation' => -90,
                'circumference' => 180,
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => ['enabled' => false],
                ],
            ]);

            $params['candidat'] = $candidate;
            $params['completion'] = $completion;
            $params['chart'] = $chart;
        } elseif ($user && $user->getType() === User::ACCOUNT_ENTREPRISE) {
            $params['entreprise'] = $user->getEntrepriseProfile();
        }

        return $this->render('white_label/client1/user/index.html.twig', $params);
    }
    
    #[Route('/mes-infos', name: 'app_white_label_client1_user_profile')]
    public function profile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $params = [];

        if ($user->getType() === User::ACCOUNT_CANDIDAT) {
            $candidate = $user->getCandidateProfile();
            if (!$candidate) {
                $candidate = new CandidateProfile();
                $candidate->setCandidat($user);
                $this->entityManager->persist($candidate);
            }
            $form = $this->createForm(EditCandidateProfile::class, $candidate);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $cvFile = $form->get('cv')->getData();
                if ($cvFile) {
                    $fileName = $this->fileUploader->upload($cvFile, $candidate);
                    $candidate->setCv($fileName[0]);
                    $this->profileManager->saveCV($fileName, $candidate);
                }
                $this->entityManager->flush();
                $this->addFlash('success', 'Informations enregistrées');
            }
            $params['form'] = $form->createView();
            $params['type'] = 'candidat';
        }

        if ($user->getType() === User::ACCOUNT_ENTREPRISE) {
            $entreprise = $user->getEntrepriseProfile();
            if (!$entreprise) {
                $entreprise = new EntrepriseProfile();
                $entreprise->setEntreprise($user);
                $this->entityManager->persist($entreprise);
            }
            $form = $this->createForm(EditEntrepriseType::class, $entreprise);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->flush();
                $this->addFlash('success', 'Informations enregistrées');
            }
            $params['form'] = $form->createView();
            $params['type'] = 'entreprise';
        }

        if ($user->getEmploye()) {
            $employeForm = $this->createForm(EmployeType::class, $user->getEmploye());
            $employeForm->handleRequest($request);
            if ($employeForm->isSubmitted() && $employeForm->isValid()) {
                $this->entityManager->flush();
                $this->addFlash('success', 'Informations enregistrées');
            }
            $params['employeForm'] = $employeForm->createView();
        }

        return $this->render('white_label/client1/user/profile.html.twig', $params);
    }

    #[Route('/recrutement', name: 'app_white_label_client1_user_missions')]
    public function missions(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);        
        return $this->render('white_label/client1/user/missions.html.twig', [
            'joblistings' => $this->entityManager->getRepository(JobListing::class)->paginateJobListings($paginatorInterface, JobListing::STATUS_PUBLISHED, $page, $size),
        ]);
    }

    #[Route('/modifier-mot-de-passe', name: 'app_white_label_client1_user_password')]
    public function password(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashed = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashed);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_client1_user_profile');
        }

        return $this->render('white_label/client1/user/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/candidatures', name: 'app_white_label_client1_user_candidatures')]
    public function candidatures(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $params = [];

        if ($user->getType() === User::ACCOUNT_CANDIDAT && $user->getCandidateProfile()) {
            $candidate = $user->getCandidateProfile();
            $repo = $this->entityManager->getRepository(Applications::class);
            $page = $request->query->getInt('page', 1);

            $params['pendings'] = $paginator->paginate(
                $repo->findBy(['candidat' => $candidate, 'status' => Applications::STATUS_PENDING], ['id' => 'DESC']),
                $page,
                10
            );
            $params['rendezvous'] = $paginator->paginate(
                $repo->findBy(['candidat' => $candidate, 'status' => Applications::STATUS_METTING], ['id' => 'DESC']),
                $page,
                10
            );
            $params['accepteds'] = $paginator->paginate(
                $repo->findBy(['candidat' => $candidate, 'status' => Applications::STATUS_ACCEPTED], ['id' => 'DESC']),
                $page,
                10
            );
            $params['refuseds'] = $paginator->paginate(
                $repo->findBy(['candidat' => $candidate, 'status' => Applications::STATUS_REJECTED], ['id' => 'DESC']),
                $page,
                10
            );
            $params['archiveds'] = $paginator->paginate(
                $repo->findBy(['candidat' => $candidate, 'status' => Applications::STATUS_ARCHIVED], ['id' => 'DESC']),
                $page,
                10
            );
        }

        return $this->render('white_label/client1/user/candidatures.html.twig', $params);
    }

    #[Route('/assistance', name: 'app_white_label_client1_user_assistance')]
    public function assistance(Request $request, MailerService $mailerService): Response
    {
        $contactForm = new ContactForm();
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(AssistanceType::class, $contactForm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $this->entityManager->persist($contactForm);
            $this->entityManager->flush();

            $mailerService->sendMultiple(
                ["contact@olona-talents.com", "support@olona-talents.com", "olonaprod@gmail.com"],
                "Nouvelle entrée sur le formulaire de contact",
                "contact.html.twig",
                [
                    'user' => $contactForm,
                ]
            );
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');
        }

        return $this->render('white_label/client1/user/assistance.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
