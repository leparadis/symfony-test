<?php

namespace App\Tests\Unit\Payment\Client;

use App\Payment\Client\Shift4PaymentProcessor;
use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\Interface\PaymentRequestInterface;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Shift4PaymentProcessorTest extends TestCase
{
    private $httpClient;
    private $parameterBag;
    private $validator;
    private $responseBuilder;
    private $processor;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->responseBuilder = $this->createMock(PaymentResponseBuilderInterface::class);

        $this->processor = new Shift4PaymentProcessor(
            $this->httpClient,
            $this->parameterBag,
            $this->validator,
            $this->responseBuilder
        );
    }

    public function testProcessPaymentSuccess()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->parameterBag->method('get')
            ->willReturnMap([
                ['shift4.api_key', 'test_api_key'],
                ['shift4.api_url', 'https://api.shift4.com'],
            ]);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $mockResponse = new MockResponse(json_encode([
            'list' => [
                [
                    'id' => 'ch_123456',
                    'created' => 1628097600,
                    'amount' => 1000,
                    'currency' => 'USD',
                    'card' => ['first6' => '411111'],
                ]
            ]
        ]), ['http_code' => 200]);

        $this->httpClient->setResponseFactory($mockResponse);

        $expectedResponse = new PaymentResponseDTO(
            'ch_123456',
            new \DateTimeImmutable('@1628097600'),
            10.00,
            'USD',
            '411111'
        );

        $this->responseBuilder->method('setTransactionId')->willReturnSelf();
        $this->responseBuilder->method('setCreatedAt')->willReturnSelf();
        $this->responseBuilder->method('setAmount')->willReturnSelf();
        $this->responseBuilder->method('setCurrency')->willReturnSelf();
        $this->responseBuilder->method('setCardBin')->willReturnSelf();
        $this->responseBuilder->method('build')->willReturn($expectedResponse);

        $result = $this->processor->processPayment($paymentRequest);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessPaymentValidationError()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([
                $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class)
            ]));

        $this->expectException(\InvalidArgumentException::class);

        $this->processor->processPayment($paymentRequest);
    }

    public function testProcessPaymentApiError()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->parameterBag->method('get')
            ->willReturnMap([
                ['shift4.api_key', 'test_api_key'],
                ['shift4.api_url', 'https://api.shift4.com'],
            ]);

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $mockResponse = new MockResponse(json_encode([
            'error' => ['message' => 'API Error']
        ]), ['http_code' => 400]);

        $this->httpClient->setResponseFactory($mockResponse);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment processing failed: HTTP 400 returned for "https://api.shift4.com/charges"');

        $this->processor->processPayment($paymentRequest);
    }
}