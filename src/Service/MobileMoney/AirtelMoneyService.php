<?php

namespace App\Service\MobileMoney;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AirtelMoneyService
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $apiUrl;

    public function __construct(HttpClientInterface $client, string $clientId, string $clientSecret, string $apiUrl)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiUrl = $apiUrl;
    }

    public function authenticate()
    {
        $response = $this->client->request('POST', $this->apiUrl . '/auth/oauth2/token', [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]
        ]);

        $data = $response->toArray();
        return $data['access_token'] ?? null;
    }

    public function checkBalance()
    {
        // Example on how to call a specific API endpoint
        $accessToken = $this->authenticate();
        $response = $this->client->request('GET', $this->apiUrl . '/standard/v1/users/balance', [
            'headers' => [
                'Accept' => '*/*',
                'X-Country' => 'MG',
                'X-Currency' => 'MGA',
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);

        return $response->toArray();
    }

    public function payments($payload)
    {
        // Example on how to call a specific API endpoint
        $accessToken = $this->authenticate();
        $security = $this->generateSignatureAndKey($payload);
        $response = $this->client->request('POST', $this->apiUrl . '/merchant/v2/payments/', [
            'headers' => [
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'X-Country' => 'MG',
                'X-Currency' => 'MGA',
                'Authorization' => 'Bearer ' . $accessToken,
                'x-signature' => $security['x-signature'],
                'x-key' => $security['x-key']
            ],
            'json' => $payload
        ]);

        return $response;
    }

    function generateSignatureAndKey($payload)
    {
        $rsaPublicKey = "-----BEGIN PUBLIC KEY-----\n" .
                "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCYtZNZnPdBFqUIXoSJlvjhYH5m\n" .
                "3Yq8OcK6OzQXxcE8nh7kRCwTvTpnNBIY+Dn1TQa9sq/iGuTfXgJABOzTH0Z4vT6V\n" .
                "6vB6VlF4W469iH5u+BRwpV3YXYKm4dr5633UO1XBLdAc2X4aXm51HNkZKg+D0/zG\n" .
                "OUFxwCAlYKr92kPHwQIDAQAB\n" .
                "-----END PUBLIC KEY-----";

        // Step 1: Generate random AES key and IV
        $aesKey = openssl_random_pseudo_bytes(32); // 256 bits
        $iv = openssl_random_pseudo_bytes(16); // 128 bits

        // Step 2: Base64 encode the AES key and IV
        $aesKeyBase64 = base64_encode($aesKey);
        $ivBase64 = base64_encode($iv);
        
        // Step 3: Encrypt the payload using AES key and IV
        $encryptedPayload = openssl_encrypt($payload, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);

        // Base64 encode the encrypted payload
        $encryptedPayloadBase64 = base64_encode($encryptedPayload);

        // Step 5: Concatenate the AES key and IV with a colon
        $keyIv = $aesKeyBase64 . ':' . $ivBase64;

        // Step 6: Encrypt the concatenated key:IV using the RSA public key
        openssl_public_encrypt($keyIv, $encryptedKeyIv, $rsaPublicKey, OPENSSL_PKCS1_OAEP_PADDING); // Use OAEP padding for RSA
        $encryptedKeyIvBase64 = base64_encode($encryptedKeyIv);

        // Return x-signature and x-key
        return [
            'x-signature' => $encryptedPayloadBase64,
            'x-key' => $encryptedKeyIvBase64,
        ];
    }

    // Add more methods for each API endpoint you plan to use
}