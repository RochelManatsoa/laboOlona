<?php

namespace App\Controller\Finance;

use App\Entity\Finance\Avantage;
use App\Entity\Finance\Employe;
use App\Entity\User;
use App\Form\Finance\EmployeType;
use App\Manager\Finance\EmployeManager;
use App\Repository\Finance\EmployeRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/finance/employe')]
class EmployeController extends AbstractController
{
    public function __construct(
        private EmployeManager $employeManager,
        private EntityManagerInterface $em,
        private EmployeRepository $employeRepository,
        private UserRepository $userRepository,
        private PaginatorInterface $paginatorInterface,
    ){        
    }

    #[Route('/', name: 'app_finance_employe')]
    public function index(Request $request): Response
    {
        return $this->render('finance/employe/index.html.twig', [
            'employes' => $this->paginatorInterface->paginate(
                $this->employeRepository->findAll([
                    'id' => 'DESC'
                ]),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/new', name: 'app_finance_employe_new')]
    public function new(Request $request): Response
    {
        $employe = $this->employeManager->init();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $employe = $form->getData();
            $user = $employe->getUser();
            $checkUser = $this->userRepository->findOneBy([
                'email' => $user->getEmail()
            ]);
            if($checkUser instanceof User){
                $currentRoles = $checkUser->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $employe->setUser($checkUser);
            }else{
                $currentRoles = $user->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $user->setDateInscription(new DateTime())->setRoles(['ROLE_EMPLOYE']);
            }
            $this->em->persist($employe);
            $this->em->flush();
            $this->addFlash('success', 'Employé ajouté');

            return $this->redirectToRoute('app_finance_employe_view', ['id' => $employe->getId()]);
        }

        return $this->render('finance/employe/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_finance_employe_edit')]
    public function edit(Request $request, Employe $employe): Response
    {
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // dd($form->getData()->getUser());
            $user = $form->getData()->getUser();
            $currentRoles = $user->getRoles();
            if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                $currentRoles[] = 'ROLE_EMPLOYE'; 
            }
            $user->setDateInscription(new DateTime())->setRoles($currentRoles);
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Modification effectuée');

            return $this->redirectToRoute('app_finance_employe_view', ['id' => $employe->getId()]);
        }

        return $this->render('finance/employe/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_finance_employe_view')]
    public function view(Request $request, Employe $employe): Response
    {
        if(!$employe->getAvantage() instanceof Avantage){
            $employe->setAvantage(new Avantage());
            $this->em->persist($employe);
            $this->em->flush();
        }
        
        return $this->render('finance/employe/view.html.twig', [
            'employe' => $employe,
        ]);
    }
}
