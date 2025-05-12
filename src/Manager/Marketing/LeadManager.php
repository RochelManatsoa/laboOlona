<?php

namespace App\Manager\Marketing;

use App\Service\PdfService;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Invoice;
use App\Entity\EntrepriseProfile;
use App\Entity\Marketing\Lead;
use App\Entity\Marketing\Source;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeadManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security,
        private PdfService $pdfService,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    public function init(): Lead
    {
        $lead = new Lead();

        return $lead;
    }

    public function initEntreprise(EntrepriseProfile $entrepriseProfile): Lead
    {
        $sourceEntreprise = $this->em->getRepository(Source::class)->findOneBy(['slug' => 'entreprise']);
        $lead = new Lead();
        $lead->setSource($sourceEntreprise);
        $lead->setComment('Validation entreprise - '.$entrepriseProfile->getDescription());
        $lead->setFullName($entrepriseProfile->getEntreprise());
        $lead->setEmail($entrepriseProfile->getEntreprise()->getEmail());
        $lead->setPhone($entrepriseProfile->getEntreprise()->getTelephone());
        $lead->setUser($entrepriseProfile->getEntreprise());
        $this->save($lead);

        return $lead;
    }

    public function save(Lead $lead)
    {
        $this->em->persist($lead);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Lead $lead */
        $lead = $form->getData();
        $this->save($lead);

        return $lead;
    }
}