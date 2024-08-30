<?php

namespace App\Payment;

use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\Interface\PaymentClientBuilderInterface;
use App\Payment\Interface\PaymentRequestInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private PaymentClientBuilderInterface $paymentClientBuilder;
    private LoggerInterface $logger;

    public function __construct(
        PaymentClientBuilderInterface $paymentClientBuilder,
        LoggerInterface $logger
    ) {
        $this->paymentClientBuilder = $paymentClientBuilder;
        $this->logger = $logger;
    }

    public function processPayment(string $providerName, PaymentRequestInterface $paymentRequest): PaymentResponseDTO
    {
        $this->logger->info('Starting payment process', [
            'provider' => $providerName,
            'amount' => $paymentRequest->getAmount(),
            'currency' => $paymentRequest->getCurrency(),
            'customerId' => $paymentRequest->getCustomerId()
        ]);

        try {
            $paymentClient = $this->paymentClientBuilder->build($providerName);

            $response = $paymentClient->processPayment($paymentRequest);

            $this->logger->info('Payment processed successfully', [
                'transactionId' => $response->getTransactionId(),
                'amount' => $response->getAmount(),
                'currency' => $response->getCurrency()
            ]);

            return $response;
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Invalid payment client requested', [
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', [
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Payment processing failed: ' . $e->getMessage(), 0, $e);
        }
    }
}