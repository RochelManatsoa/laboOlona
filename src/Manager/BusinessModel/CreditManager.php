<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CreditManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function init(): Credit
    {
        $credit = new Credit();
        $credit->setUpdatedAt(new \DateTime());
        $credit->setTotal(200);

        return $credit;
    }

    public function save(Credit $credit)
    {
        $this->em->persist($credit);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $credit = $form->getData();
        $this->save($credit);

        return $credit;
    }

    public function adjustCredits(User $user, int $creditsToDeduct): array
    {
        $credit = $user->getCredit();

        if (!$credit) {
            return ['error' => 'Aucun crédit trouvé pour cet utilisateur.'];
        }

        $currentCredits = $credit->getTotal();
        if ($currentCredits < $creditsToDeduct) {
            return ['error' => 'Crédits insuffisants.'];
        }

        $newCredits = $currentCredits - $creditsToDeduct;
        $credit->setTotal($newCredits);
        $this->em->persist($credit);
        $this->em->flush();

        return ['success' => $newCredits];
    }

    public function ajouterCreditsBienvenue(User $user, int $welcomeCredits)
    {
        $credit = $user->getCredit();

        if (!$credit) {
            $credit = $this->init();
            $credit->setUser($user);
        }

        $credit->setTotal($credit->getTotal() + $welcomeCredits);
        $credit->setExpireAt((new \DateTime())->modify('+60 days'));
        $this->em->persist($credit);
        $this->em->flush();
    }

    public function handleCreditPackagePurchase(User $user, int $packageId, bool $isRecruiter): bool
    {
        $creditPackageRepository = $this->em->getRepository(Package::class);
        $creditPackage = $creditPackageRepository->find($packageId);

        if (!$creditPackage) {
            return false;
        }

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount($creditPackage->getPrice());
        $transaction->setCreditsAdded($creditPackage->getCredits());
        $transaction->setTransactionDate(new \DateTime());
        $transaction->setDetails('Achat du pack de ' . $creditPackage->getCredits() . ' crédits');
        // $transaction->setTransactionType('purchase');
        
        $this->em->persist($transaction);
        
        $credit = $user->getCredit();

        if (!$credit) {
            $credit = $this->init();
            $credit->setUser($user);
        }

        $credit->setTotal($credit->getTotal() + $creditPackage->getCredits());
        $this->em->persist($credit);
        $this->em->flush();

        return true;
    }

    public function buyCredits(User $user, int $packageId): bool
    {
        $userType = $user->getType();
        $creditPackageRepository = $this->em->getRepository(Package::class);
        $creditPackage = $creditPackageRepository->find($packageId);

        if (!$creditPackage) {
            return false;
        }

        return $this->handleCreditPackagePurchase($user, $packageId, $userType === 'recruiter');
    }

    public function validateTransaction(Transaction $transaction): bool
    {
        if (!$transaction) {
            return false;
        }

        $user = $transaction->getUser();
        $creditsToAdd = $transaction->getCreditsAdded();

        $credit = $user->getCredit();

        if (!$credit) {
            $credit = $this->init();
            $credit->setUser($user);
        }

        $credit->setTotal($credit->getTotal() + $creditsToAdd);
        $this->em->persist($credit);

        $this->em->persist($transaction);
        $this->em->flush();

        return true;
    }
}