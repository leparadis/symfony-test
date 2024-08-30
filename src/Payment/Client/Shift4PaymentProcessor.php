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

class Shift4PaymentProcessor implements PaymentClientInterface
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
        $apiSecretKey = $this->params->get('shift4.api_key');
        $apiUrl = $this->params->get('shift4.api_url');

        // Validate the payment request
        $errors = $this->validator->validate($paymentRequest);
        if (count($errors) > 0) {
            // Handle validation errors
            throw new \InvalidArgumentException((string) $errors);
        }
            //TODO Since there is a issue with api and chage i use a get for demonstration purposes.
            /*'amount' => $paymentRequest->getAmount() * 100, // Shift4 expects amount in cents
            'currency' => $paymentRequest->getCurrency(),
            'card' => [
            'number' => $paymentRequest->getCardNumber(),
            'expMonth' => $paymentRequest->getCardExpiryMonth(),
            'expYear' => $paymentRequest->getCardExpiryYear(),
            'cvv' => $paymentRequest->getCardCvv(),
],
            'capture' => false, // Set to false if you want to authorize only*/

        try {

            $response = $this->httpClient->request('GET', $apiUrl . '/charges', [
                'auth_basic' => [$apiSecretKey, ''],
            ]);

            $statusCode = $response->getStatusCode();
            $jsonContent = $response->getContent();

            $content = json_decode($jsonContent, true);
            $firstTransaction = $content['list'][0];

            if ($statusCode === 200) {
                //TODO This can be extracted into a transformer so its not populated here, but for test purpose its fine
                return $this->responseBuilder
                    ->setTransactionId($firstTransaction['id'])
                    ->setCreatedAt(new DateTimeImmutable('@' . $firstTransaction['created']))
                    ->setAmount($firstTransaction['amount'] / 100)
                    ->setCurrency($firstTransaction['currency'])
                    ->setCardBin($firstTransaction['card']['first6'])
                    ->build();
            }

            // If status code is not 200, throw an exception with more details
            throw new \Exception("Shift4 API error: " . ($content['error']['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Shift4 payment processing error: ' . $e->getMessage());
            throw new \Exception('Payment processing failed: ' . $e->getMessage());
        }
    }
}