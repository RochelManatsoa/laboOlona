<?php

namespace App\WhiteLabel\Service\Client1;

use App\WhiteLabel\Entity\Client1\CandidateProfile;
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
            // Handle exception if something happens during file upload
        }

        return [$fileName, $originalFilename];
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function modifyPdfTitle(string $filePath, string $newTitle)
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
            $currentRoute = $this->router->generate(
                $currentRequest->attributes->get('_route'),
                $currentRequest->attributes->get('_route_params')
            );
            return new RedirectResponse($currentRoute);
        }
    }
}
