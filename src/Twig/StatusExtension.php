<?php

namespace App\Twig;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Transaction;
use App\Entity\Candidate\TarifCandidat;
use App\Entity\CandidateProfile;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Entreprise\JobListing;
use Twig\Extension\AbstractExtension;
use App\Entity\Entreprise\BudgetAnnonce;
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Entity\Moderateur\Metting;
use App\Entity\Prestation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StatusExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private UrlGeneratorInterface $urlGenerator,
        private ReferrerProfileRepository $referrerProfileRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('reffererStatusLabel', [$this, 'reffererStatusLabel']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('arrayInverseDevise', [$this, 'arrayInverseDevise']),
            new TwigFunction('arrayInverseCandidatTarifType', [$this, 'arrayInverseCandidatTarifType']),
            new TwigFunction('arrayInverseTarifType', [$this, 'arrayInverseTarifType']),
            new TwigFunction('satusSimulateur', [$this, 'satusSimulateur']),
            new TwigFunction('satusContrat', [$this, 'satusContrat']),
            new TwigFunction('satusEntreprise', [$this, 'satusEntreprise']),
            new TwigFunction('satusCandidate', [$this, 'satusCandidate']),
            new TwigFunction('satusMetting', [$this, 'satusMetting']),
            new TwigFunction('satusPrestation', [$this, 'satusPrestation']),
            new TwigFunction('statusOrder', [$this, 'statusOrder']),
            new TwigFunction('statusTransaction', [$this, 'statusTransaction']),
            new TwigFunction('satusJobListing', [$this, 'satusJobListing']),
            new TwigFunction('isPrestationBoosted', [$this, 'isPrestationBoosted']),
            new TwigFunction('isJobOfferBoosted', [$this, 'isJobOfferBoosted']),
            new TwigFunction('typeSimulateur', [$this, 'typeSimulateur']),
        ];
    }

    public function arrayInverseDevise(): array
    {
        return BudgetAnnonce::arrayInverseDevise();
    }

    public function arrayInverseTarifType(): array
    {
        return BudgetAnnonce::arrayInverseTarifType();
    }

    public function arrayInverseCandidatTarifType(string $typeTarif)
    {
        $type = TarifCandidat::arrayInverseTarifType()[$typeTarif] ?? '';
        
        return $type;
    }

    public function satusSimulateur(Simulateur $simulateur)
    {
        $type = $simulateur->getStatusFinance() ?? '';
        switch ($type) {
            case Simulateur::STATUS_LIBRE :
                $status = '<span class="badge text-bg-success">Simulation libre</span>';
                break;

            case Simulateur::STATUS_SEND :
                $status = '<span class="badge text-bg-warning">Demande envoyée</span>';
                break;

            case Simulateur::STATUS_CONTACT :
                $status = '<span class="badge text-bg-info">Prise de contact</span>';
                break;

            case Simulateur::STATUS_RELANCE :
                $status = '<span class="badge text-bg-primary">Relance</span>';
                break;

            case Simulateur::STATUS_CLIENT :
                $status = '<span class="badge text-bg-danger">Client</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-success">Simulation libre</span>';
                break;
        }
        
        return $status;
    }

    public function satusContrat(Contrat $contrat)
    {
        $type = $contrat->getStatus() ?? '';
        switch ($type) {
            case Contrat::STATUS_PENDING :
                $status = '<span class="badge text-bg-success">En attente</span>';
                break;

            case Contrat::STATUS_VALID :
                $status = '<span class="badge text-bg-warning">Validée</span>';
                break;

            case Contrat::STATUS_CONTACT :
                $status = '<span class="badge text-bg-info">Prise de contact</span>';
                break;

            case Contrat::STATUS_RELANCE :
                $status = '<span class="badge text-bg-primary">Relance</span>';
                break;

            case Contrat::STATUS_APPROVED :
                $status = '<span class="badge text-bg-danger">Approuvé</span>';
                break;

            case Contrat::STATUS_ARCHIVED :
                $status = '<span class="badge text-bg-danger">Résilié</span>';
                break;

            case Contrat::STATUS_SUSPENDED :
                $status = '<span class="badge text-bg-danger">Suspendu</span>';
                break;

            case Contrat::STATUS_UNFULFILLED :
                $status = '<span class="badge text-bg-danger">Non exécuté</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-success">Non exécuté</span>';
                break;
        }
        
        return $status;
    }

    public function satusMetting(Metting $metting)
    {
        $type = $metting->getStatus() ?? '';
        switch ($type) {
            case Metting::STATUS_CANCELLED :
                $status = '<span class="badge text-bg-danger">Annulé</span>';
                break;

            case Metting::STATUS_COMPLETED :
                $status = '<span class="badge text-bg-info">Terminé</span>';
                break;

            case Metting::STATUS_CONFIRMED :
                $status = '<span class="badge text-bg-success">Confirmé</span>';
                break;

            case Metting::STATUS_NOSHOW :
                $status = '<span class="badge text-bg-dark">Non présenté</span>';
                break;

            case Metting::STATUS_PENDING :
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;

            case Metting::STATUS_RESCHEDULED :
                $status = '<span class="badge text-bg-success">Valide</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;
        }
        
        return $status;
    }

    public function statusTransaction(Transaction $transaction)
    {
        $type = $transaction->getStatus() ?? '';
        switch ($type) {
            case Transaction::STATUS_PENDING :
                $status = '<span class="badge text-bg-danger">'.$this->getLabels(Transaction::STATUS_PENDING).'</span>';
                break;

            case Transaction::STATUS_COMPLETED :
                $status = '<span class="badge text-bg-info">'.$this->getLabels(Transaction::STATUS_COMPLETED).'</span>';
                break;

            case Transaction::STATUS_FAILED :
                $status = '<span class="badge text-bg-success">'.$this->getLabels(Transaction::STATUS_FAILED).'</span>';
                break;

            case Transaction::STATUS_CANCELLED :
                $status = '<span class="badge text-bg-dark">'.$this->getLabels(Transaction::STATUS_CANCELLED).'</span>';
                break;

            case Transaction::STATUS_ON_HOLD :
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_ON_HOLD).'</span>';
                break;

            case Transaction::STATUS_PROCESSING :
                $status = '<span class="badge text-bg-secondary">'.$this->getLabels(Transaction::STATUS_PROCESSING).'</span>';
                break;

            case Transaction::STATUS_AUTHORIZED :
                $status = '<span class="badge text-bg-warning">'.$this->getLabels(Transaction::STATUS_AUTHORIZED).'</span>';
                break;

            case Transaction::STATUS_REFUNDED :
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_REFUNDED).'</span>';
                break;

            case Transaction::STATUS_DISPUTED :
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_DISPUTED).'</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_PENDING).'</span>';
                break;
        }
        
        return $status;
    }

    public function statusOrder(Order $transaction)
    {
        $type = $transaction->getStatus() ?? '';
        switch ($type) {
            case Transaction::STATUS_PENDING :
                $status = '<span class="badge text-bg-danger">'.$this->getLabels(Transaction::STATUS_PENDING).'</span>';
                break;

            case Transaction::STATUS_COMPLETED :
                $status = '<span class="badge text-bg-info">'.$this->getLabels(Transaction::STATUS_COMPLETED).'</span>';
                break;

            case Transaction::STATUS_FAILED :
                $status = '<span class="badge text-bg-success">'.$this->getLabels(Transaction::STATUS_FAILED).'</span>';
                break;

            case Transaction::STATUS_CANCELLED :
                $status = '<span class="badge text-bg-danger">'.$this->getLabels(Transaction::STATUS_CANCELLED).'</span>';
                break;

            case Transaction::STATUS_ON_HOLD :
                $status = '<span class="badge text-bg-dark">'.$this->getLabels(Transaction::STATUS_ON_HOLD).'</span>';
                break;

            case Transaction::STATUS_PROCESSING :
                $status = '<span class="badge text-bg-warning">'.$this->getLabels(Transaction::STATUS_PROCESSING).'</span>';
                break;

            case Transaction::STATUS_AUTHORIZED :
                $status = '<span class="badge text-bg-info">'.$this->getLabels(Transaction::STATUS_AUTHORIZED).'</span>';
                break;

            case Transaction::STATUS_REFUNDED :
                $status = '<span class="badge text-bg-secondary">'.$this->getLabels(Transaction::STATUS_REFUNDED).'</span>';
                break;

            case Transaction::STATUS_DISPUTED :
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_DISPUTED).'</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-primary">'.$this->getLabels(Transaction::STATUS_PENDING).'</span>';
                break;
        }
        
        return $status;
    }

    private function getLabels(string $status): string
    {
        return Transaction::getLabels()[$status];
    }

    public function satusPrestation(Prestation $prestation)
    {
        $type = $prestation->getStatus() ?? '';
        switch ($type) {
            case Prestation::STATUS_VALID :
                $status = '<span class="badge rounded-pill px-2 text-bg-danger">Validée</span>';
                break;

            case Prestation::STATUS_COMPLETED :
                $status = '<span class="badge rounded-pill px-2 text-bg-info">Terminé</span>';
                break;

            case Prestation::STATUS_FEATURED :
                $status = '<span class="badge rounded-pill px-2 text-bg-success">Boostée</span>';
                break;

            case Prestation::STATUS_DELETED :
                $status = '<span class="badge rounded-pill px-2 text-bg-dark">Effacée</span>';
                break;

            case Prestation::STATUS_PENDING :
                $status = '<span class="badge rounded-pill px-2 text-bg-primary">En attente</span>';
                break;

            case Prestation::STATUS_SUSPENDED :
                $status = '<span class="badge rounded-pill px-2 text-bg-primary">Suspendue</span>';
                break;
            
            default:
                $status = '<span class="badge rounded-pill px-2 text-bg-primary">En attente</span>';
                break;
        }
        
        return $status;
    }

    public function satusJobListing(JobListing $jobListing)
    {
        $type = $jobListing->getStatus() ?? '';
        switch ($type) {
            case JobListing::STATUS_DRAFT :
                $status = '<span class="badge text-bg-warning">Brouillon</span>';
                break;

            case JobListing::STATUS_PUBLISHED :
                $status = '<span class="badge text-bg-success">Publié</span>';
                break;

            case JobListing::STATUS_FEATURED :
                $status = '<span class="badge text-bg-info">Boostée</span>';
                break;

            case JobListing::STATUS_DELETED :
                $status = '<span class="badge text-bg-secondary">Effacée</span>';
                break;

            case JobListing::STATUS_PENDING :
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;

            case JobListing::STATUS_REJECTED :
                $status = '<span class="badge text-bg-danger">Rejetée</span>';
                break;

            case JobListing::STATUS_EXPIRED :
                $status = '<span class="badge text-bg-info">Expirée</span>';
                break;

            case JobListing::STATUS_ARCHIVED :
                $status = '<span class="badge text-bg-info">Archivée</span>';
                break;

            case JobListing::STATUS_UNPUBLISHED :
                $status = '<span class="badge text-bg-danger">Non publiée</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;
        }
        
        return $status;
    }

    public function isPrestationBoosted(Prestation $prestation): string
    {
        $boost = $prestation->getBoost();
        $boostFacebook = $prestation->getBoostFacebook();
        $info = '<button class="btn btn-sm btn-danger text-uppercase fw-bold" data-bs-toggle="modal" data-bs-target="#boostPrestation'.$prestation->getId().'" data-bs-type="boost-prestation" data-bs-prestation="'.$prestation->getId().'"><i class="bi bi-rocket-takeoff me-2"></i> Booster</button>';
        if($boost instanceof Boost){
            $boostVisibility = $this->em->getRepository(BoostVisibility::class)->findBoostVisibilityByBoostAndPrestation($boost, $prestation);
            if($boostVisibility instanceof BoostVisibility && !$boostVisibility->isExpired() ){
                switch ($boost->getSlug()) {
                    case 'boost-prestation-recruiter-7' :
                    case 'boost-prestation-candidate-7' :
                        $info = '<div class="text-center"><span class="fw-semibold small">Boost 7 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span></div>';
                        break;
        
                    case 'boost-prestation-recruiter-15' :
                    case 'boost-prestation-candidate-15' :
                        $info = '<div class="text-center"><span class="fw-semibold small">Boost 15 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span></div>';
                        break;
        
                    case 'boost-prestation-recruiter-15' :
                    case 'boost-prestation-candidate-30' :
                        $info = '<div class="text-center"><span class="fw-semibold small">Boost 30 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span></div>';
                        break;
                }
            }
        }
        if($boostFacebook instanceof BoostFacebook){
            $boostVisibilityFacebook = $this->em->getRepository(BoostVisibility::class)->findBoostVisibilityByBoostFacebookAndPrestation($boostFacebook, $prestation);
            if($boostVisibilityFacebook instanceof BoostVisibility && !$boostVisibilityFacebook->isExpired()){
                $info .= '<div class="text-center"><span class="small fw-semibold"><i class="bi bi-facebook me-2"></i> Boost</span><br><span class="small fw-light"> Jusqu\'au '.$boostVisibilityFacebook->getEndDate()->format('d-m-Y \à H:i').' </span></div>';
            }
        }

        return $info;
    }

    public function isJobOfferBoosted(JobListing $jobListing)
    {
        $boost = $jobListing->getBoost();
        $boostFacebook = $jobListing->getBoostFacebook();
        $url = $this->urlGenerator->generate('app_v2_recruiter_job_listing_edit', ['jobListing' => $jobListing->getId()]);
        $info = '<button class="btn btn-sm btn-danger text-uppercase fw-bold" data-bs-toggle="modal" data-bs-target="#boostJobListing'.$jobListing->getId().'" data-bs-type="boost-annonce" data-bs-annonce="'.$jobListing->getId().'"><i class="bi bi-rocket-takeoff me-2"></i> Booster</button>';
        if($boost instanceof Boost){
            $boostVisibility = $this->em->getRepository(BoostVisibility::class)->findLatestBoostVisibilityByBoost($boost);
            if($boostVisibility instanceof BoostVisibility && !$boostVisibility->isExpired() ){
                switch ($boost->getSlug()) {
                    case 'boost-joblisting-1' :
                        $info = '<span class="fw-bold">Boost 1 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span>';
                        break;
        
                    case 'boost-joblisting-7' :
                        $info = '<span class="fw-bold">Boost 7 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span>';
                        break;
        
                    case 'boost-joblisting-15' :
                        $info = '<span class="fw-bold">Boost 15 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span>';
                        break;
                    
                    default:
                        $status = '<span class="fw-bold">Boost 1 jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($boostVisibility->getEndDate()).'</span>';
                        break;
                }
            }
        }
        if($boostFacebook instanceof BoostFacebook){
            $boostVisibilityFacebook = $this->em->getRepository(BoostVisibility::class)->findBoostVisibilityByBoostFacebookAndJobListing($boostFacebook, $jobListing);
            if($boostVisibilityFacebook instanceof BoostVisibility && !$boostVisibilityFacebook->isExpired()){
                $info .= '<div class="text-center"><span class="small fw-semibold"><i class="bi bi-facebook me-2"></i> Boost</span><br><span class="small fw-light"> Jusqu\'au '.$boostVisibilityFacebook->getEndDate()->format('d-m-Y \à H:i').' </span></div>';
            }
        }
        
        return $info;
    }

    public function satusEntreprise(EntrepriseProfile $entreprise)
    {
        $type = $entreprise->getStatus() ?? '';
        switch ($type) {
            case EntrepriseProfile::STATUS_VALID :
                $status = '<span class="badge text-bg-success">Validé</span>';
                break;

            case EntrepriseProfile::STATUS_PENDING :
                $status = '<span class="badge text-bg-info">En attente</span>';
                break;

            case EntrepriseProfile::STATUS_PREMIUM :
                $status = '<span class="badge text-bg-dark">Premium</span>';
                break;

            case EntrepriseProfile::STATUS_BANNED :
                $status = '<span class="badge text-bg-danger">Banni</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-info">En attente</span>';
                break;
        }
        
        return $status;
    }

    public function satusCandidate(CandidateProfile $entreprise)
    {
        $type = $entreprise->getStatus() ?? '';
        switch ($type) {
            case CandidateProfile::STATUS_VALID :
                $status = '<span class="badge text-bg-primary">Validé</span>';
                break;

            case CandidateProfile::STATUS_PENDING :
                $status = '<span class="badge text-bg-danger">En attente</span>';
                break;

            case CandidateProfile::STATUS_FEATURED :
                $status = '<span class="badge text-bg-dark">Mis en avant</span>';
                break;

            case CandidateProfile::STATUS_BANNISHED :
                $status = '<span class="badge text-bg-info">Banni</span>';
                break;

            case CandidateProfile::STATUS_RESERVED :
                $status = '<span class="badge text-bg-danger">Vivier</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-info">En attente</span>';
                break;
        }
        
        return $status;
    }

}