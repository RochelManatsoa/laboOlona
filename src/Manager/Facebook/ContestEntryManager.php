<?php

namespace App\Manager\Facebook;

use App\Entity\User;
use Twig\Environment as Twig;
use App\Entity\Facebook\Contest;
use Symfony\Component\Form\Form;
use App\Entity\Facebook\ContestEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContestEntryManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    public function init(User $user): ContestEntry
    {
        $contestEntry = new ContestEntry();
        $contestEntry->setUser($user);
        $contest = $this->em->getRepository(Contest::class)->findLastByUser();
        if ($contest) {
            $contestEntry->setContest($contest);
        }

        return $contestEntry;
    }

    public function save(ContestEntry $contestEntry)
    {
        $this->em->persist($contestEntry);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var ContestEntry $contestEntry */
        $contract = $form->getData();
        $this->save($contract);

        return $contract;
    }
}