<?php

namespace App\Tests\Integration\Payment\Client;

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
    private $parameterBagMock;
    private $validatorMock;
    private $responseBuilderMock;
    private $shift4PaymentProcessor;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->responseBuilderMock = $this->createMock(PaymentResponseBuilderInterface::class);

        $this->shift4PaymentProcessor = new Shift4PaymentProcessor(
            $this->httpClient,
            $this->parameterBagMock,
            $this->validatorMock,
            $this->responseBuilderMock
        );
    }

    public function testProcessPaymentSuccess()
    {
        $paymentRequestMock = $this->createMock(PaymentRequestInterface::class);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->parameterBagMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['shift4.api_key', 'test_api_key'],
                ['shift4.api_url', 'https://api.shift4.com'],
            ]);

        $responseData = [
            'list' => [
                [
                    'id' => 'ch_123456',
                    'created' => time(),
                    'amount' => 1000,
                    'currency' => 'USD',
                    'card' => ['first6' => '411111'],
                ]
            ]
        ];

        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode($responseData), ['http_code' => 200])
        ]);

        $expectedResponse = new PaymentResponseDTO(
            'ch_123456',
            new \DateTimeImmutable('@' . $responseData['list'][0]['created']),
            10.00,
            'USD',
            '411111'
        );

        $this->responseBuilderMock->expects($this->once())
            ->method('setTransactionId')
            ->with('ch_123456')
            ->willReturnSelf();
        $this->responseBuilderMock->expects($this->once())
            ->method('setCreatedAt')
            ->willReturnSelf();
        $this->responseBuilderMock->expects($this->once())
            ->method('setAmount')
            ->with(10.00)
            ->willReturnSelf();
        $this->responseBuilderMock->expects($this->once())
            ->method('setCurrency')
            ->with('USD')
            ->willReturnSelf();
        $this->responseBuilderMock->expects($this->once())
            ->method('setCardBin')
            ->with('411111')
            ->willReturnSelf();
        $this->responseBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($expectedResponse);

        $result = $this->shift4PaymentProcessor->processPayment($paymentRequestMock);

        $this->assertInstanceOf(PaymentResponseDTO::class, $result);
        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessPaymentValidationError()
    {
        $paymentRequestMock = $this->createMock(PaymentRequestInterface::class);

        $violationListMock = $this->createMock(ConstraintViolationList::class);
        $violationListMock->method('count')->willReturn(1);
        $violationListMock->method('__toString')->willReturn('Validation failed');

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($violationListMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->shift4PaymentProcessor->processPayment($paymentRequestMock);
    }

    public function testProcessPaymentApiError()
    {
        $paymentRequestMock = $this->createMock(PaymentRequestInterface::class);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->parameterBagMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['shift4.api_key', 'test_api_key'],
                ['shift4.api_url', 'https://api.shift4.com'],
            ]);

        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode([
                'error' => ['message' => 'Invalid request']
            ]), ['http_code' => 400])
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment processing failed: HTTP 400 returned for "https://api.shift4.com/charges".');

        $this->shift4PaymentProcessor->processPayment($paymentRequestMock);
    }
}