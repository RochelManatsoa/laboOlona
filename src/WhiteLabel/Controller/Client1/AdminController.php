<?php

namespace App\WhiteLabel\Controller\Client1;

use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\WhiteLabel\Form\Client1\CsvUploadType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Manager\Client1\CVThequeManager;
use App\WhiteLabel\Manager\Client1\CsvUploadManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    #[Route('/', name: 'app_white_label_client1_admin')]
    public function index(): Response
    {
        $users = $this->entityManager->getRepository(User::class)->countAllUsers();
        return $this->render('white_label/client1/admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/annonces', name: 'app_white_label_client1_admin_toutes_les_annonces')]
    public function jobOffers(): Response
    {
        return $this->render('white_label/client1/admin/annonces.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/candidatures', name: 'app_white_label_client1_admin_toutes_les_candidatures')]
    public function applications(): Response
    {
        return $this->render('white_label/client1/admin/candidatures.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/csv-upload', name: 'app_white_label_client1_admin_csv_upload')]
    public function upload(Request $request, CsvUploadManager $csvUploadManager): Response
    {
        $form = $this->createForm(CsvUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csv_file')->getData();

            if ($csvFile) {
                $originalFilename = pathinfo($csvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $csvUploadManager->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$csvFile->guessExtension();

                try {
                    $csvFile->move(
                        $this->getParameter('csv_directory'),
                        $newFilename
                    );

                    $filePath = $this->getParameter('csv_directory').'/'.$newFilename;
                    $csvUploadManager->importCsvToDatabase($filePath);

                    $this->addFlash('success', 'CSV file uploaded successfully and data imported.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                }
            }

            return $this->redirectToRoute('app_white_label_client1_admin_cvtheque');
        }

        return $this->render('white_label/client1/admin/csv_upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/configuration', name: 'app_white_label_client1_admin_configuration')]
    public function configuration(): Response
    {
        return $this->render('white_label/client1/admin/configuration.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/password', name: 'app_white_label_client1_admin_configuration_password')]
    public function password(): Response
    {
        return $this->render('white_label/client1/admin/password.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/assistance', name: 'app_white_label_client1_admin_assistance')]
    public function assistance(): Response
    {
        return $this->render('white_label/client1/admin/assistance.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
