<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Form\Moderateur\CsvUploadType;
use App\Repository\CandidateProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/dashboard/moderateur/csv/upload')]
class CsvUploadController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private CandidateProfileRepository $candidateProfileRepository,
    ) {}

    #[Route('/', name: 'app_dashboard_moderateur_csv_upload')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(CsvUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csv_file')->getData();

            if ($csvFile) {
                $originalFilename = pathinfo($csvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->sluggerInterface->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$csvFile->guessExtension();

                try {
                    $csvFile->move(
                        $this->getParameter('csv_directory'),
                        $newFilename
                    );

                    $filePath = $this->getParameter('csv_directory').'/'.$newFilename;
                    $this->importCsvToDatabase($filePath, $this->em);

                    $this->addFlash('success', 'CSV file uploaded successfully and data imported.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                }
            }

            return $this->redirectToRoute('app_dashboard_moderateur_csv_upload');
        }

        return $this->render('dashboard/moderateur/csv_upload/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function importCsvToDatabase(string $filePath, EntityManagerInterface $entityManager)
    {
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Lecture de l'en-tête du fichier CSV
            $header = fgetcsv($handle, 1000, ',');

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Assurez-vous que chaque ligne contient le nombre attendu de colonnes
                if (count($data) < 3) {
                    continue; // Skip lines that don't have enough columns
                }
                // Transform the textual data into HTML
                $resultResume = $this->extractProfessionalSummary($data[2]);
                $resultFreeHtml = $this->transformToHtml($data[2]);
                $resultPremiumHtml = $this->transformToHtml($data[1]);

                // Supposons que le premier champ est l'ID de l'entité
                $entity = $this->candidateProfileRepository->find((int)$data[0]);

                if ($entity instanceof CandidateProfile) {
                    // Mettre à jour les champs de l'entité
                    $entity->setResultFree($resultFreeHtml)->setResultPremium($resultPremiumHtml)->setTesseractResult($resultResume);
                    $entityManager->persist($entity);
                } 
            }
            fclose($handle);

            // Flush the changes to the database
            $entityManager->flush();
        }
    }

    private function transformToHtml(string $text): string
    {
        // Convert the text to HTML
        $html = '<div>';

        // Split sections based on headers
        $sections = preg_split('/(\r\n|\r|\n){2,}/', $text);
        foreach ($sections as $section) {
            // Split each section into lines
            $lines = preg_split('/(\r\n|\r|\n)/', $section);

            if (count($lines) > 1) {
                // First line as header
                $html .= '<h3>' . htmlspecialchars(array_shift($lines)) . '</h3>';
                // Remaining lines as list items
                $html .= '<ul>';
                foreach ($lines as $line) {
                    // Remove leading dash and space if present
                    $line = preg_replace('/^\s*-\s*/', '', $line);
                    $html .= '<li>' . htmlspecialchars($line) . '</li>';
                }
                $html .= '</ul>';
            } else {
                // Single line section
                $html .= '<p>' . htmlspecialchars($section) . '</p>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    private function extractProfessionalSummary(string $text): string
    {
        // Match the text after "Résumé Professionnel"
        $pattern = '/Résumé Professionnel\s*(.*)/s';
        preg_match($pattern, $text, $matches);

        // If a match is found, clean up the result
        if (isset($matches[1])) {
            // Extract the text after "Résumé Professionnel"
            $summary = trim($matches[1]);

            // Remove the first character if it is a colon ':'
            if (isset($summary[0]) && $summary[0] === ':') {
                $summary = substr($summary, 1);
            }

            // Remove all dashes '-'
            $summary = str_replace('-', '', $summary);

            // Split the text into words and get the first 100 words
            $words = preg_split('/\s+/', $summary);
            $first100Words = array_slice($words, 0, 30);

            // Join the first 100 words back into a string
            $summary = implode(' ', $first100Words);

            return $summary;
        }

        // Return an empty string if no match is found
        return '';
    }

}
