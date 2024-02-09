<?php

namespace App\Twig;

use App\Entity\User;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\ReferrerProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\JobListing;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReferrerExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private ReferrerProfileRepository $referrerProfileRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_label', [$this, 'statusLabel']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('stepCooptation', [$this, 'stepCooptation']),
            new TwigFunction('generateCoopteurPseudo', [$this, 'generateCoopteurPseudo']),
            new TwigFunction('getReferrerById', [$this, 'getReferrerById']),
            new TwigFunction('getPrimeByAnnonce', [$this, 'getPrimeByAnnonce']),
            new TwigFunction('getTotalePrime', [$this, 'getTotalePrime']),
            new TwigFunction('checkEmailCandidat', [$this, 'checkEmailCandidat']),
            new TwigFunction('getReferrerByEmail', [$this, 'getReferrerByEmail']),
        ];
    }

    public function stepCooptation(int $step): string 
    {
        $style = '<span class="badge text-bg-danger">Création compte</span>';
        switch ($step) {
            case '2':
                $style = '<span class="badge text-bg-primary">Validation compte</span>';
                break;
            
            case '3':
                $style = '<span class="badge text-bg-dark">Envoi candidature</span>';
                break;

            case '4':
                $style = '<span class="badge text-bg-info">Candidature envoyée</span>';
                break;

            case '5':
                $style = '<span class="badge text-bg-success">Rendez-vous</span>';
                break;

            case '6':
                $style = '<span class="badge text-bg-warning">Accépté</span>';
                break;

            default:
                $style = '<span class="badge text-bg-danger">Création compte</span>';
                break;
        }
        return $style;
    }

    public function generateCoopteurPseudo(ReferrerProfile $referrerProfile)
    {
        $letters = 'CO';
        $paddedId = sprintf('%04d', $referrerProfile->getId());

        return $letters . $paddedId;
    }

    public function getReferrerById(int $id): ReferrerProfile
    {
        return $this->referrerProfileRepository->find($id);
    }

    public function getPrimeByAnnonce(JobListing $jobListing): float
    {
        $rewards = 0;
        $prime = $rewards = $jobListing->getPrime();
        if($prime === null){
            $rewards = $jobListing->getSalaire() * 0.1 / $jobListing->getNombrePoste();
        }

        return $rewards;
    }

    public function getTotalePrime(array $referrals): float
    {
        $rewards = 0;
        foreach ($referrals as $value) {
            $rewards += $value->getRewards();
        }

        return $rewards;
    }

    public function checkEmailCandidat(string $email): string
    {
        $value = '';
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            $value = '
            <br>
            <span class="text-dark">
            '.$user->getNom().'  
            '.$user->getPrenom().'
            </span>
            ';
        }

        return $value;
    }

    public function getReferrerByEmail(string $email): string
    {
        $value = '';
        $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $email]);
        if ($refered) {
            $url = $this->urlGenerator->generate('app_dashboard_moderateur_cooptation_view', ['referralCode' => $refered->getReferralCode()], UrlGeneratorInterface::ABSOLUTE_URL);
            $value = '
            <a href="'.$url.' " class="badge bg-danger" target=_blank> Cooptation #
            '.$refered->getId().'  
            </a>
            ';
        }

        return $value;
    }

}