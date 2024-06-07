<?php

namespace App\Service;

use App\Entity\CandidateProfile;
use App\Twig\AppExtension;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use setasign\Fpdi\Tcpdf\Fpdi;

class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        private string $targetDirectoryEditedOlona,
        private string $targetDirectoryEdited,
        private AppExtension $appExtension,
        private RequestStack $requestStack,
        private RouterInterface $router
    ) {
    }

    public function upload(UploadedFile $file, CandidateProfile $candidat): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->appExtension->generatePseudo($candidat);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
            $this->modifyPdfTitle($this->getTargetDirectory().'/'.$fileName, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return [$fileName, $originalFilename];
    }

    public function uploadEditedCv(UploadedFile $file, CandidateProfile $candidat): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->appExtension->generatePseudo($candidat);
        $fileName = 'CV-'.$safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectoryEditedOlona(), $fileName);
            $this->modifyPdfTitle($this->getTargetDirectoryEditedOlona().'/'.$fileName, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return [$fileName, $originalFilename];
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function getTargetDirectoryEdited(): string
    {
        return $this->targetDirectoryEdited;
    }

    public function getTargetDirectoryEditedOlona(): string
    {
        return $this->targetDirectoryEditedOlona;
    }

    public function modifyPdfTitle($filePath, $newTitle)
    {
        try {
            $pdf = new Fpdi();

            $pdf->SetTitle($newTitle);

            $pageCount = $pdf->setSourceFile($filePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $pdf->importPage($pageNo, '/MediaBox');
                $pdf->AddPage();
                $pdf->useTemplate($tplIdx, 10, 10, 200);
            }

            $pdf->Output($filePath, 'F');
        } catch (\Exception $e) {
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('error', 'Impossible de lire le titre du PDF. Veuillez vérifier la propriété du PDF.');
            $currentRequest = $this->requestStack->getCurrentRequest();
            $currentRoute = $this->router->generate($currentRequest->attributes->get('_route'), $currentRequest->attributes->get('_route_params'));
            return new RedirectResponse($currentRoute);
        }
    }
}