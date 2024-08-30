<?php

namespace Tests\App\Payment\Client;

use App\Payment\Client\OppwaPaymentProcessor;
use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\Interface\PaymentRequestInterface;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class OppwaPaymentProcessorTest extends TestCase
{
    private OppwaPaymentProcessor $processor;
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private ValidatorInterface $validator;
    private PaymentResponseBuilderInterface $responseBuilder;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->responseBuilder = $this->createMock(PaymentResponseBuilderInterface::class);

        $this->processor = new OppwaPaymentProcessor(
            $this->httpClient,
            $this->params,
            $this->validator,
            $this->responseBuilder
        );
    }

    public function testProcessPaymentSuccess()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseDTO = $this->createMock(PaymentResponseDTO::class);
        $violationList = $this->createMock(ConstraintViolationListInterface::class);

        $this->params->method('get')
            ->willReturnMap([
                ['oppwa.api_key', null, 'test_api_key'],
                ['oppwa.api_url', null, 'https://test.oppwa.com/v1'],
            ]);

        $violationList->method('count')->willReturn(0);
        $this->validator->method('validate')->willReturn($violationList);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode([
            'id' => 'test_transaction_id',
            'timestamp' => '2023-01-01T12:00:00Z',
            'amount' => '92.00',
            'currency' => 'EUR',
            'card' => ['bin' => '420000'],
        ]));

        $this->httpClient->method('request')->willReturn($response);

        $this->responseBuilder->method('setTransactionId')->willReturnSelf();
        $this->responseBuilder->method('setCreatedAt')->willReturnSelf();
        $this->responseBuilder->method('setAmount')->willReturnSelf();
        $this->responseBuilder->method('setCurrency')->willReturnSelf();
        $this->responseBuilder->method('setCardBin')->willReturnSelf();
        $this->responseBuilder->method('build')->willReturn($responseDTO);

        $result = $this->processor->processPayment($paymentRequest);

        $this->assertInstanceOf(PaymentResponseDTO::class, $result);
    }

    public function testProcessPaymentValidationError()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');
        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->processor->processPayment($paymentRequest);
    }

    public function testProcessPaymentApiError()
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $violationList = $this->createMock(ConstraintViolationListInterface::class);

        $this->params->method('get')
            ->willReturnMap([
                ['oppwa.api_key', null, 'test_api_key'],
                ['oppwa.api_url', null, 'https://test.oppwa.com/v1'],
            ]);

        $violationList->method('count')->willReturn(0);
        $this->validator->method('validate')->willReturn($violationList);

        $response->method('getStatusCode')->willReturn(400);
        $response->method('getContent')->willReturn(json_encode([
            'error' => ['message' => 'API Error'],
        ]));

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment processing failed: Oppwa API error: API Error');

        $this->processor->processPayment($paymentRequest);
    }
}