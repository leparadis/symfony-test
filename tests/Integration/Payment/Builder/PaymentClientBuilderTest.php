<?php

namespace App\Tests\Integration\Payment\Builder;

use App\Payment\Builder\PaymentClientBuilder;
use App\Payment\Client\Shift4PaymentProcessor;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentClientBuilderTest extends TestCase
{
    private PaymentClientBuilder $paymentClientBuilder;
    private MockHttpClient $httpClient;
    private ParameterBagInterface $params;
    private ValidatorInterface $validator;
    private PaymentResponseBuilderInterface $responseBuilder;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(MockHttpClient::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->responseBuilder = $this->createMock(PaymentResponseBuilderInterface::class);

        $this->paymentClientBuilder = new PaymentClientBuilder(
            $this->httpClient,
            $this->params,
            $this->validator,
            $this->responseBuilder
        );
    }

    public function testBuildShift4PaymentProcessor(): void
    {
        $paymentClient = $this->paymentClientBuilder->build('shift4');

        $this->assertInstanceOf(Shift4PaymentProcessor::class, $paymentClient);
    }

    public function testBuildUnsupportedProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment provider: unsupported');

        $this->paymentClientBuilder->build('unsupported');
    }
}