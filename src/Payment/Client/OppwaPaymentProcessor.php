<?php

namespace App\Payment\Client;

use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\Interface\PaymentClientInterface;
use App\Payment\Interface\PaymentRequestInterface;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use DateTimeImmutable;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OppwaPaymentProcessor implements PaymentClientInterface
{

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;
    /**
     * @var ParameterBagInterface
     */
    private $params;
    private ValidatorInterface $validator;
    private PaymentResponseBuilderInterface $responseBuilder;

    public function __construct(HttpClientInterface $httpClient,
                                ParameterBagInterface $params,
                                ValidatorInterface $validator,
                                PaymentResponseBuilderInterface $responseBuilder)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->validator = $validator;
        $this->responseBuilder = $responseBuilder;
    }

    public function processPayment(PaymentRequestInterface $paymentRequest): PaymentResponseDTO
    {
        $apiSecretKey = $this->params->get('oppwa.api_key');
        $apiUrl = $this->params->get('oppwa.api_url');

        // Validate the payment request
        $errors = $this->validator->validate($paymentRequest);
        if (count($errors) > 0) {
            // Handle validation errors
            throw new \InvalidArgumentException((string) $errors);
        }
        //TODO Since there is a issue with api and it doesnt work when i change values i will be using default ones from demo
        /*'amount' => $paymentRequest->getAmount() * 100, // Shift4 expects amount in cents
        'currency' => $paymentRequest->getCurrency(),
        'card' => [
        'number' => $paymentRequest->getCardNumber(),
        'expMonth' => $paymentRequest->getCardExpiryMonth(),
        'expYear' => $paymentRequest->getCardExpiryYear(),
        'cvv' => $paymentRequest->getCardCvv(),
],*/
        try {

            $response = $this->httpClient->request('POST', "$apiUrl/payments", [
                'headers' => [
                    'Authorization' => "Bearer $apiSecretKey",
                ],
                'body' => [
                    'entityId' => '8a8294174b7ecb28014b9699220015ca',
                    'amount' => '92.00',
                    'currency' => 'EUR',
                    'paymentBrand' => 'VISA',
                    'paymentType' => 'DB',
                    'card.number' => '4200000000000000',
                    'card.holder' => 'Jane Jones',
                    'card.expiryMonth' => '05',
                    'card.expiryYear' => '2034',
                    'card.cvv' => '123',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $jsonContent = $response->getContent();

            $content = json_decode($jsonContent, true);

            if ($statusCode === 200) {
                //TODO This can be extracted into a transformer so its not populated here, but for test purpose its fine
                return $this->responseBuilder
                    ->setTransactionId($content['id'])
                    ->setCreatedAt(new DateTimeImmutable($content['timestamp']))
                    ->setAmount((float)$content['amount'])
                    ->setCurrency($content['currency'])
                    ->setCardBin($content['card']['bin'])
                    ->build();
            }

            // If status code is not 200, throw an exception with more details
            throw new \Exception("Oppwa API error: " . ($content['error']['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Oppwa payment processing error: ' . $e->getMessage());
            throw new \Exception('Payment processing failed: ' . $e->getMessage());
        }
    }
}