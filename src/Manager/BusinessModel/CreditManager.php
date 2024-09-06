<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use App\Entity\Notification;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Credit;
use App\Manager\NotificationManager;
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
        private NotificationManager $notificationManager,
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

    public function restablishCredits(User $user, int $creditsToAdd): array
    {
        $credit = $user->getCredit();

        if (!$credit) {
            return ['error' => 'Aucun crédit trouvé pour cet utilisateur.'];
        }

        $currentCredits = $credit->getTotal();

        $newCredits = $currentCredits + $creditsToAdd;
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

    public function notifyTransaction(Transaction $transaction): Notification
    {
        $status = $transaction->getStatus();
        $titre = "";
        $message = "";
        switch ($status) {
            case Transaction::STATUS_FAILED :
                $titre = 'Échec de votre transaction';
                $message = "
                Nous tenons à vous informer que votre dernière transaction a malheureusement échoué. Nous comprenons que cela peut être frustrant et sommes là pour vous aider à résoudre ce problème.<br><br>
                Détails de la transaction :<br>
                Numéro de transaction : {$transaction->getReference()}<br>
                Montant : {$transaction->getAmount()} Ar<br>
                Date : {$transaction->getTransactionDate()->format('d/m/Y à H:i')}<br>
                Crédits : {$transaction->getCreditsAdded()} <br><br>
                Si vous avez des questions ou si vous avez besoin d'assistance supplémentaire, n'hésitez pas à contacter notre support client.<br><br>
                ";
                break;

            case Transaction::STATUS_AUTHORIZED :
                $titre = 'Confirmation de validation de votre transaction';
                $message = "
                Nous sommes heureux de vous informer que votre transaction a été validée avec succès. Vous pouvez désormais profiter de tous les services associés sans restriction.<br><br>
                Détails de la transaction :<br>
                Numéro de transaction : {$transaction->getReference()}<br>
                Montant : {$transaction->getAmount()} Ar<br>
                Date : {$transaction->getTransactionDate()->format('d/m/Y à H:i')}<br>
                Crédits : {$transaction->getCreditsAdded()} <br><br>
                Si vous avez des questions ou si vous avez besoin d'assistance supplémentaire, n'hésitez pas à contacter notre support client.<br><br>
                ";
                break;
            
            case Transaction::STATUS_COMPLETED :
                $titre = "Confirmation de complétion de votre transaction";
                $message = "
                Nous sommes heureux de vous informer que le traitement de votre transaction est maintenant terminé. Toutes les vérifications nécessaires ont été effectuées avec succès.<br><br>
                Détails de la transaction :<br>
                Numéro de transaction : {$transaction->getReference()}<br>
                Montant : {$transaction->getAmount()} Ar<br>
                Date : {$transaction->getTransactionDate()->format('d/m/Y à H:i')}<br>
                Crédits : {$transaction->getCreditsAdded()} <br><br>
                Nous vous remercions de votre patience tout au long de ce processus et sommes ravis de continuer à vous servir avec efficacité. N'hésitez pas à nous contacter si vous avez des questions ou besoin d'assistance supplémentaire.<br><br>
                ";
                break;
            
            default:
                $titre = "Vérification des références de votre transaction en cours";
                $message = "
                Nous vous informons que votre transaction est actuellement en phase de vérification. Nous examinons les multiples références associées à votre dossier pour garantir la conformité et la sécurité des processus.<br><br>
                Détails de la transaction :<br>
                Numéro de transaction : {$transaction->getReference()}<br>
                Montant : {$transaction->getAmount()} Ar<br>
                Date : {$transaction->getTransactionDate()->format('d/m/Y à H:i')}<br>
                Crédits : {$transaction->getCreditsAdded()} <br><br>
                Nous vous tiendrons informé de l'avancement de cette vérification et de la suite des opérations. Votre patience et votre compréhension sont appréciées pendant que nous complétons ces vérifications essentielles.<br><br>
                ";
                break;
        }
        
        return $this->notificationManager->createNotification(
            $this->security->getUser(), 
            $transaction->getUser(), 
            Notification::TYPE_MESSAGE,
            $titre,
            $message
        );
    }
}