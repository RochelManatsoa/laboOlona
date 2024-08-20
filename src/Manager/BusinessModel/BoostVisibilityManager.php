<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Package;
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

        return $visibilityBoost;
    }

    public function isExpired(BoostVisibility $boostVisibility): bool
    {
        if ($boostVisibility->getEndDate() < new \DateTime()) {
            return true;
        }
        return false;
    }
}