<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use App\Entity\Prestation;
use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Package;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\RequestStack;

class BoostVisibilityManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function init(Boost $boost): BoostVisibility
    {
        $visibilityBoost = new BoostVisibility();
        $visibilityBoost->setStartDate(new \DateTime());
        $visibilityBoost->setEndDate((new \DateTime())->modify('+'.$boost->getDurationDays().' days'));
        $visibilityBoost->setType($boost->getType());
        $visibilityBoost->setBoost($boost);
        $visibilityBoost->setDurationDays($boost->getDurationDays());
        $this->save($visibilityBoost);

        return $visibilityBoost;
    }

    public function initBoostvisibilityFacebook(BoostFacebook $boost): BoostVisibility
    {
        $visibilityBoost = new BoostVisibility();
        $visibilityBoost->setStartDate(new \DateTime());
        $visibilityBoost->setEndDate((new \DateTime())->modify('+'.$boost->getDurationDays().' days'));
        $visibilityBoost->setType($boost->getType());
        $visibilityBoost->setBoostFacebook($boost);
        $visibilityBoost->setDurationDays($boost->getDurationDays());
        $visibilityBoost->setUser($this->security->getUser());
        $this->save($visibilityBoost);

        return $visibilityBoost;
    }

    public function update(BoostVisibility $visibilityBoost, Boost $boost): BoostVisibility
    {
        $visibilityBoost->setStartDate(new \DateTime());
        $visibilityBoost->setEndDate((new \DateTime())->modify('+'.$boost->getDurationDays().' days'));
        $visibilityBoost->setType($boost->getType());
        $visibilityBoost->setBoost($boost);
        $visibilityBoost->setDurationDays($boost->getDurationDays());
        $this->save($visibilityBoost);

        return $visibilityBoost;
    }

    public function updateFacebook(BoostVisibility $visibilityBoost, BoostFacebook $boost): BoostVisibility
    {
        $visibilityBoost->setStartDate(new \DateTime());
        $visibilityBoost->setEndDate((new \DateTime())->modify('+'.$boost->getDurationDays().' days'));
        $visibilityBoost->setType($boost->getType());
        $visibilityBoost->setBoostFacebook($boost);
        $visibilityBoost->setDurationDays($boost->getDurationDays());
        $this->save($visibilityBoost);

        return $visibilityBoost;
    }

    public function isExpired(?BoostVisibility $boostVisibility): bool
    {
        if($boostVisibility === null){
            return true;
        }
        if ($boostVisibility->getEndDate() < new \DateTime()) {
            $prestation = $boostVisibility->getPrestation();
            if($prestation instanceof Prestation){
                $prestation->setStatus(Prestation::STATUS_VALID);
                $this->em->persist($prestation);
            }
            $candidateProfile = $boostVisibility->getCandidateProfile();
            if($candidateProfile instanceof CandidateProfile){
                $candidateProfile->setStatus(CandidateProfile::STATUS_VALID);
                $this->em->persist($candidateProfile);
            }
            $jobListing = $boostVisibility->getJobListing();
            if($jobListing instanceof JobListing){
                $jobListing->setStatus(JobListing::STATUS_PUBLISHED);
                $this->em->persist($jobListing);
            }
            $entrepriseProfile = $boostVisibility->getEntrepriseProfile();
            if($entrepriseProfile instanceof EntrepriseProfile){
                $entrepriseProfile->setStatus(EntrepriseProfile::STATUS_VALID);
                $this->em->persist($entrepriseProfile);
            }
            $this->em->flush();

            return true;
        }
        return false;
    }

    public function save(BoostVisibility $boostVisibility): void
    {
        $this->em->persist($boostVisibility);
        $this->em->flush();
    }
}