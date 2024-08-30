<?php

namespace App\Tests\Controller;

use App\Controller\PaymentController;
use App\Payment\Dto\PaymentRequestDTO;
use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\PaymentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Container;

class PaymentControllerTest extends TestCase
{
    private PaymentController $controller;
    private PaymentService $paymentService;
    private Container $container;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->container = $this->createMock(Container::class);

        $this->controller = new PaymentController($this->paymentService);
        $this->controller->setContainer($this->container);
    }

    public function testProcessPaymentSuccess()
    {
        $requestData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
            'customerId' => 'test-customer'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $mockResponse = new PaymentResponseDTO(
            'txn123',
            new \DateTimeImmutable(),
            100.00,
            'USD',
            '411111'
        );

        $this->paymentService->expects($this->once())
            ->method('processPayment')
            ->willReturn($mockResponse);

        $response = $this->controller->processPayment($request, 'shift4');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Payment processed successfully', $content['message']);
        $this->assertEquals('txn123', $content['transactionId']);
        $this->assertEquals(100.00, $content['amount']);
        $this->assertEquals('USD', $content['currency']);
        $this->assertEquals('411111', $content['cardBin']);
        $this->assertArrayHasKey('createdAt', $content);
    }

    public function testProcessPaymentInvalidProvider()
    {
        $request = new Request();
        $response = $this->controller->processPayment($request, 'invalid_provider');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid provider. Use either "oppwa" or "shift4".', $content['error']);
    }

    public function testProcessPaymentInvalidData()
    {
        $requestData = [
            'amount' => 100.00,
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));
        $response = $this->controller->processPayment($request, 'shift4');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid request data.', $content['error']);
    }

    public function testProcessPaymentServiceException()
    {
        $requestData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $this->paymentService->expects($this->once())
            ->method('processPayment')
            ->willThrowException(new \Exception('Payment processing failed'));

        $response = $this->controller->processPayment($request, 'shift4');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Payment processing failed: Payment processing failed', $content['error']);
    }
}