<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Subcription;
use App\Entity\BusinessModel\Transaction;
use Symfony\Bundle\SecurityBundle\Security;
use App\Manager\Marketing\SubcriptionManager;
use Symfony\Component\HttpFoundation\RequestStack;

class TransactionManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private SubcriptionManager $subcriptionManager,
        private Security $security
    ){}

    public function init(): Transaction
    {
        $transaction = new Transaction();
        $transaction->setTransactionDate(new \DateTime());
        $transaction->setStatus(Transaction::STATUS_PENDING);
        $transaction->setUser($this->security->getUser());

        return $transaction;
    }

    public function save(Transaction $transaction)
    {
        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Transaction $transaction */
        $transaction = $form->getData();
        $package = $transaction->getPackage();
        // $transaction->setAmount($package->getPrice());
        $transaction->setCreditsAdded($package->getCredit());
        $this->save($transaction);

        return $transaction;
    }

    public function validateTransaction(int $transactionId): bool
    {
        $transactionRepository = $this->em->getRepository(Transaction::class);
        $transaction = $transactionRepository->find($transactionId);

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

        $transaction->setTransactionDetails('Transaction validée');
        $this->em->persist($transaction);
        $this->em->flush();

        return true;
    }

    public function findTransactionSuccessByCommand(Order $order): ?Transaction
    {
        $transaction = $order->getTransaction();
        if($transaction->getStatus() === Transaction::STATUS_AUTHORIZED || $transaction->getStatus() === Transaction::STATUS_COMPLETED){
            $invoice = $order->getInvoice();
            if(!$invoice instanceof Invoice){
                $this->createInvoice($transaction);
            }
            return $transaction;
        }

        return null;
    }

    public function createInvoice(Transaction $transaction) :void
    {
        $order = $transaction->getCommand();
        $invoice = $order->getInvoice();
        if(!$invoice instanceof Invoice){
            $invoice = new Invoice();
            $invoice->setCreatedAt($transaction->getTransactionDate());
        }
        $invoice->setAdress($order->getCustomer()->getAdress());
        $invoice->setPostalCode($order->getCustomer()->getPostalCode());
        $invoice->setCity($order->getCustomer()->getCity());
        $invoice->setName($order->getCustomer()->getNom());
        $invoice->setFirstName($order->getCustomer()->getPrenom());
        $invoice->setCommande($order);
        if($transaction->getPackage()->getType() === 'ABONNEMENT'){
            if ($transaction->getUser()->getType() === User::ACCOUNT_CANDIDAT) {
                $profile = $transaction->getUser()->getCandidateProfile();
                $subcription = $this->em->getRepository(Subcription::class)->findOneBy(['candidat' => $profile]);
                if(!$subcription instanceof Subcription){
                    $subcription = $this->subcriptionManager->initCandidatPro($profile);
                }
                $invoice->setSubcription($subcription);
            }else{
                $profile = $transaction->getUser()->getEntrepriseProfile();
                $subcription = $this->em->getRepository(Subcription::class)->findOneBy(['entreprise' => $profile]);
                if(!$subcription instanceof Subcription){
                    $subcription = $this->subcriptionManager->initEntreprisePro($profile);
                }
                $invoice->setSubcription($subcription);
            }
            $subcription->setDuration($subcription->getDuration() + 1);
            $subcription->setRelance(0);
            $subcription->setStartDate(new \DateTime());
            $subcription->setEndDate((clone $subcription->getStartDate())->modify('+1 month'));
            $subcription->addInvoice($invoice);
            $subcription->setActive(true);
            $profile->setIsPremium(true);
            $this->em->persist($subcription);
            $this->em->persist($profile);
        }

        $this->em->persist($invoice);
        $this->em->flush();
    }
}