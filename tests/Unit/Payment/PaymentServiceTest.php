<?php

namespace App\Tests\Payment;

use PHPUnit\Framework\TestCase;
use App\Payment\PaymentService;
use App\Payment\Interface\PaymentClientBuilderInterface;
use App\Payment\Interface\PaymentClientInterface;
use App\Payment\Interface\PaymentRequestInterface;
use App\Payment\Dto\PaymentResponseDTO;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

class PaymentServiceTest extends TestCase
{
    private $paymentClientBuilder;
    private $logger;
    private $paymentService;
    private $paymentClient;

    protected function setUp(): void
    {
        $this->paymentClientBuilder = $this->createMock(PaymentClientBuilderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->paymentClient = $this->createMock(PaymentClientInterface::class);
        $this->paymentService = new PaymentService($this->paymentClientBuilder, $this->logger);
    }

    public function testProcessPaymentSuccess()
    {
        $providerName = 'test_provider';
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentClient = $this->createMock(PaymentClientInterface::class);
        $expectedResponse = new PaymentResponseDTO(
            'transaction123',
            new \DateTimeImmutable(),
            100.00,
            'USD',
            'success'
        );

        $paymentRequest->method('getAmount')->willReturn(100.00);
        $paymentRequest->method('getCurrency')->willReturn('USD');
        $paymentRequest->method('getCustomerId')->willReturn('customer123');

        $this->paymentClientBuilder->expects($this->once())
            ->method('build')
            ->with($providerName)
            ->willReturn($paymentClient);

        $paymentClient->expects($this->once())
            ->method('processPayment')
            ->with($paymentRequest)
            ->willReturn($expectedResponse);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'Starting payment process',
                    [
                        'provider' => $providerName,
                        'amount' => 100.00,
                        'currency' => 'USD',
                        'customerId' => 'customer123'
                    ]
                ],
                [
                    'Payment processed successfully',
                    [
                        'transactionId' => 'transaction123',
                        'amount' => 100.00,
                        'currency' => 'USD'
                    ]
                ]
            );

        $result = $this->paymentService->processPayment($providerName, $paymentRequest);

        $this->assertInstanceOf(PaymentResponseDTO::class, $result);
        $this->assertEquals('transaction123', $result->getTransactionId());
        $this->assertEquals(100.00, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function testProcessPaymentInvalidProvider()
    {
        $providerName = 'invalid_provider';
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->paymentClientBuilder->expects($this->once())
            ->method('build')
            ->with($providerName)
            ->willThrowException(new InvalidArgumentException('Invalid provider'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid payment client requested', $this->anything());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provider');

        $this->paymentService->processPayment($providerName, $paymentRequest);
    }

    public function testProcessPaymentFailure()
    {
        $providerName = 'test_provider';
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->paymentClientBuilder->expects($this->once())
            ->method('build')
            ->with($providerName)
            ->willReturn($this->paymentClient);

        $this->paymentClient->expects($this->once())
            ->method('processPayment')
            ->with($paymentRequest)
            ->willThrowException(new \Exception('Payment failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Payment processing failed', $this->anything());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment processing failed: Payment failed');

        $this->paymentService->processPayment($providerName, $paymentRequest);
    }
}