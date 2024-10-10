<?php

namespace App\Manager\BusinessModel;

use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Service\PdfService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security,
        private PdfService $pdfService,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private TransactionManager $transactionManager
    ){}

    public function init(): Order
    {
        $order = new Order();
        $order->setCustomer($this->security->getUser());

        return $order;
    }

    public function save(Order $order)
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Order $order */
        $order = $form->getData();
        $this->save($order);

        return $order;
    }
    
    public function generateFacture(Order $order)
    {
		$this->checkIfTransactionSuccess($order);
		$folder = $order->getGeneratedFacturePath();
        $file = $order->getGeneratedFacturePathFile();
        // create directory
        if (!is_dir($folder)) mkdir($folder, 0777, true);
		$scanFolder = scandir($folder);
        if (!in_array("facture.pdf", $scanFolder)) { 
            $snappy = $this->pdfService->createPdf();
            $html = $this->twig->render("v2/dashboard/payment/facture.pdf.twig", [
                'commande' => $order, 
                'pathToWeb' => $this->urlGeneratorInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

            $output = $snappy->getOutputFromHtml($html);
            
            $filefinal = file_put_contents($file, $output);
        }
        
        return $file;
	}

    public function checkIfTransactionSuccess(Order $order): bool
    {
        $transaction = $this->transactionManager->findTransactionSuccessByCommand($order);
        if (!$transaction instanceof Transaction) {
			return false;
		}
        $invoice = $order->getInvoice();
        if(!$invoice instanceof Invoice){
            $this->transactionManager->createInvoice($transaction);
        }
		$order->setTransaction($transaction);
		$this->save($order);

		return true;
    }
}