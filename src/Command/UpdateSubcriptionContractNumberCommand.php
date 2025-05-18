<?php

namespace App\Command;

use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Subcription;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Entity\Finance\Devise;
use App\Manager\BusinessModel\TransactionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-subcription-contract-number',
    description: 'Génère un contractNumber pour les Subcriptions qui n’en ont pas encore.',
)]
class UpdateSubcriptionContractNumberCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionManager $transactionManager,
    ){
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subcriptionRepository = $this->entityManager->getRepository(Subcription::class);
        $transactionRepository = $this->entityManager->getRepository(Transaction::class);
        $package = $this->entityManager->getRepository(Package::class)->findOneBy(['slug' => 'abonnement']);
        $typeTransaction = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['slug' => 'paiement-cash']);
        $currency = $this->entityManager->getRepository(Devise::class)->findOneBy([
            'slug' => 'ariary'
        ]);

        $subs = $subcriptionRepository->findBy(['contractNumber' => '']);

        if (empty($subs)) {
            $output->writeln('<info>Aucune subscription à mettre à jour.</info>');
            return Command::SUCCESS;
        }

        foreach ($subs as $sub) {
            $entreprise = $sub->getEntreprise();
            $transaction = $transactionRepository->findOneBy(['user' => $entreprise->getEntreprise(), 'package' => $package]);
            
            if($transaction === null){
                $transaction = new Transaction();
                $transaction->setTransactionDate(new \DateTime());
                $transaction->setUser($entreprise->getEntreprise());
                $transaction->setPackage($package);
                $transaction->setStatus(Transaction::STATUS_COMPLETED);
                $transaction->setTypeTransaction($typeTransaction);
                $transaction->setAmount($package->getPrice());
                $order = new Order();
                $order->setCreatedAt(new \DateTime());
                $order->setPaymentMethod($typeTransaction);
                $order->setCustomer($entreprise->getEntreprise());
                $order->setPackage($package);
                $order->setStatus(Order::STATUS_COMPLETED);
                $order->setCurrency($currency);
                $order->setTotalAmount($package->getPrice());
                $transaction->setCommand($order);
                $this->entityManager->persist($order);
                $this->entityManager->persist($transaction);
            }
            $this->transactionManager->createInvoice($transaction);
            $sub->setPackage($package);
            $sub->setContractNumber(uniqid('sub_', true));
            $this->entityManager->persist($sub);
        }
        $this->entityManager->flush();

        $output->writeln('<info>' . count($subs) . ' contractNumber(s) mis à jour avec succès.</info>');

        return Command::SUCCESS;
    }
}
