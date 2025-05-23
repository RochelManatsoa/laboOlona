<?php

namespace App\Service;

use PayPalHttp\HttpException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PaymentService
{
    private $client;

    public function __construct(string $clientId, string $clientSecret, string $mode)
    {
        $environment = $mode === 'sandbox' 
            ? new SandboxEnvironment($clientId, $clientSecret)
            : new ProductionEnvironment($clientId, $clientSecret);
            
        $this->client = new PayPalHttpClient($environment);
    }

    public function createPayment(array $paymentData)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "value" => $paymentData['total'],
                    "currency_code" => $paymentData['currency']
                ],
                "description" => $paymentData['description']
            ]],
            "application_context" => [
                "return_url" => $paymentData['returnUrl'],
                "cancel_url" => $paymentData['cancelUrl']
            ]
        ];

        try {
            $response = $this->client->execute($request);
            return $response;
        } catch (HttpException $ex) {
            // Handle PayPal exceptions
            throw new \Exception($ex->getMessage());
        } catch (\Exception $ex) {
            // Handle other exceptions
            throw new \Exception($ex->getMessage());
        }
    }

    public function executePayment($token)
    {
        // Exemple de capture d'une commande PayPal
        $request = new OrdersCaptureRequest($token);
        
        try {
            $response = $this->client->execute($request);
            return $response->result; // Retourne le résultat de la capture
        } catch (HttpException $ex) {
            // Gérez les erreurs pendant la capture
            throw new \Exception($ex->statusCode.': '.$ex->getMessage());
        }
    }
}