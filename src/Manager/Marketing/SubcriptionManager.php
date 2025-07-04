<?php

namespace App\Manager\Marketing;

use App\Service\PdfService;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Subcription;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubcriptionManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security,
        private PdfService $pdfService,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    public function init(): Subcription
    {
        $subcription = new Subcription();

        return $subcription;
    }

    public function initEntreprisePro(EntrepriseProfile $entrepriseProfile): Subcription
    {
        $subcription = $this->init();
        $subcription->setEntreprise($entrepriseProfile);
        $subcription->setType(Subcription::TYPE_ENTREPRISE);
        $subcription->setActive(true);
        $subcription->setDuration(1);
        $subcription->setStartDate(new \DateTime());
        $subcription->setEndDate((clone $subcription->getStartDate())->modify('+1 month'));
        $this->save($subcription);

        return $subcription;
    }

    public function initCandidatPro(CandidateProfile $candidatProfile): Subcription
    {
        $subcription = $this->init();
        $subcription->setCandidat($candidatProfile);
        $subcription->setType(Subcription::TYPE_CANDIDAT);
        $subcription->setActive(true);
        $subcription->setDuration(1);
        $subcription->setStartDate(new \DateTime());
        $subcription->setEndDate((clone $subcription->getStartDate())->modify('+1 month'));
        $this->save($subcription);

        return $subcription;
    }

    public function save(Subcription $subcription)
    {
        $this->em->persist($subcription);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Subcription $subcription */
        $subcription = $form->getData();
        $this->save($subcription);

        return $subcription;
    }

    
    public function generateContract(Subcription $subscription)
    {
        $profile = $subscription->getEntreprise() ? $subscription->getEntreprise() : $subscription->getCandidat();
        $user = $subscription->getEntreprise() ? $subscription->getEntreprise()->getEntreprise() : $subscription->getCandidat()->getCandidat();
		$folder = $subscription->getGeneratedDocsPath();
        $file = $subscription->getGeneratedContractPathFile();
        // create directory
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        $snappy = $this->pdfService->createPdf();
        $html = $this->twig->render("pdf/contrat.pdf.twig", [
            'subscription' => $subscription, 
            'user' => $user,
            'profile' => $profile,
            'entreprise' => $subscription->getEntreprise() ? true : false,
            'package' => $subscription->getPackage()->getSlug(),
            'pathToWeb' => $this->urlGeneratorInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);

        $output = $snappy->getOutputFromHtml($html);
        
        $filefinal = file_put_contents($file, $output);
        
        return $file;
	}
}