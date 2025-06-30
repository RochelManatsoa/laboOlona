<?php

namespace App\WhiteLabel\Controller\Client1\Crud;

use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use App\WhiteLabel\Form\Client1\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\Finance\Employe;
use App\WhiteLabel\Manager\Client1\ProfileManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/user')]
class UserController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface,
        private ProfileManager $profileManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_user_index', methods: ['GET'])]
    public function index(
        Request $request,
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = true;
        $users = $this->entityManager->getRepository(User::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId);

        return $this->render('white_label/client1/admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_white_label_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roles = $user->getRoles();
            $redirectRoute = null;
            $redirectParams = [];

            if (in_array('ROLE_CANDIDAT', $roles, true)) {
                $user->setType(User::ACCOUNT_CANDIDAT);
                $candidate = $this->profileManager->createCandidat($user);
                $this->entityManager->persist($candidate);
                $redirectRoute = 'app_white_label_candidat_profile_edit';
                $redirectParams = ['id' => $candidate->getId()];
            } elseif (in_array('ROLE_ANNOUNCER', $roles, true)) {
                $user->setType(User::ACCOUNT_ENTREPRISE);
                $company = $this->profileManager->createCompany($user);
                $this->entityManager->persist($company);
                $redirectRoute = 'app_white_label_entreprise_profile_edit';
                $redirectParams = ['id' => $company->getId()];
            } elseif (in_array('ROLE_RECRUITER', $roles, true)) {
                $user->setType(User::ACCOUNT_EMPLOYE);
                $employe = new Employe();
                $employe->setUser($user);
                $this->entityManager->persist($employe);
                $redirectRoute = 'app_white_label_employe_profile_edit';
                $redirectParams = ['id' => $employe->getId()];
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($redirectRoute) {
                return $this->redirectToRoute($redirectRoute, $redirectParams, Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_white_label_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('white_label/client1/admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user,
    ): Response
    {
        $form = $this->createForm(UserType::class, $user, ['isEdit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roles = $user->getRoles();
            $redirectRoute = null;
            $redirectParams = [];

            if (in_array('ROLE_CANDIDAT', $roles, true)) {
                if (!$user->getCandidateProfile()) {
                    $candidate = $this->profileManager->createCandidat($user);
                    $this->entityManager->persist($candidate);
                } else {
                    $candidate = $user->getCandidateProfile();
                }
                $user->setType(User::ACCOUNT_CANDIDAT);
                $redirectRoute = 'app_white_label_candidat_profile_edit';
                $redirectParams = ['id' => $candidate->getId()];
            } elseif (in_array('ROLE_ANNOUNCER', $roles, true)) {
                if (!$user->getEntrepriseProfile()) {
                    $company = $this->profileManager->createCompany($user);
                    $this->entityManager->persist($company);
                } else {
                    $company = $user->getEntrepriseProfile();
                }
                $user->setType(User::ACCOUNT_ENTREPRISE);
                $redirectRoute = 'app_white_label_entreprise_profile_edit';
                $redirectParams = ['id' => $company->getId()];
            } elseif (in_array('ROLE_RECRUITER', $roles, true)) {
                if (!$user->getEmploye()) {
                    $employe = new Employe();
                    $employe->setUser($user);
                    $this->entityManager->persist($employe);
                } else {
                    $employe = $user->getEmploye();
                }
                $user->setType(User::ACCOUNT_EMPLOYE);
                $redirectRoute = 'app_white_label_employe_profile_edit';
                $redirectParams = ['id' => $employe->getId()];
            }

            $this->entityManager->flush();

            if ($redirectRoute) {
                return $this->redirectToRoute($redirectRoute, $redirectParams, Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_white_label_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_user_delete', methods: ['POST'])]
    public function delete(Request $request, EntrepriseProfile $entrepriseProfile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entrepriseProfile->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entrepriseProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
