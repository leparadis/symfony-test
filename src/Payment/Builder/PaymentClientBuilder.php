<?php

namespace App\Payment\Builder;

use App\Payment\Client\OppwaPaymentProcessor;
use App\Payment\Client\Shift4PaymentProcessor;
use App\Payment\Interface\PaymentClientBuilderInterface;
use App\Payment\Interface\PaymentClientInterface;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentClientBuilder implements PaymentClientBuilderInterface
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private ValidatorInterface $validator;
    private PaymentResponseBuilderInterface $responseBuilder;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        ValidatorInterface $validator,
        PaymentResponseBuilderInterface $responseBuilder
    ) {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->validator = $validator;
        $this->responseBuilder = $responseBuilder;
    }

    public function build(string $providerName): PaymentClientInterface
    {
        switch (strtolower($providerName)) {
            case 'shift4':
                return new Shift4PaymentProcessor(
                    $this->httpClient,
                    $this->params,
                    $this->validator,
                    $this->responseBuilder
                );
            case 'oppwa':
                return new OppwaPaymentProcessor(
                    $this->httpClient,
                    $this->params,
                    $this->validator,
                    $this->responseBuilder
                );
            // Add more cases for other payment providers as needed
            default:
                throw new \InvalidArgumentException("Unsupported payment provider: $providerName");
        }
    }
}