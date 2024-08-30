<?php

namespace App\Tests\Integration\Payment\Builder;

use App\Payment\Builder\PaymentResponseBuilder;
use App\Payment\Dto\PaymentResponseDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentResponseBuilderTest extends KernelTestCase
{
    private PaymentResponseBuilder $builder;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get('validator');
        $this->builder = new PaymentResponseBuilder($this->validator);
    }

    public function testSuccessfulBuild(): void
    {
        $transactionId = 'tx_123456';
        $createdAt = new DateTimeImmutable();
        $amount = 100.00;
        $currency = 'USD';
        $cardBin = '411111';

        $response = $this->builder
            ->setTransactionId($transactionId)
            ->setCreatedAt($createdAt)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setCardBin($cardBin)
            ->build();

        $this->assertInstanceOf(PaymentResponseDTO::class, $response);
        $this->assertEquals($transactionId, $response->getTransactionId());
        $this->assertEquals($createdAt, $response->getCreatedAt());
        $this->assertEquals($amount, $response->getAmount());
        $this->assertEquals($currency, $response->getCurrency());
        $this->assertEquals($cardBin, $response->getCardBin());
    }

    public function testMissingParametersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The following parameters are not set: transactionId, createdAt, amount, currency, cardBin');

        $this->builder->build();
    }

    public function testInvalidParametersAreAllowed(): void
    {
        $response = $this->builder
            ->setTransactionId('')
            ->setCreatedAt(new DateTimeImmutable())
            ->setAmount(-100.00)
            ->setCurrency('INVALID')
            ->setCardBin('1234')
            ->build();

        $this->assertInstanceOf(PaymentResponseDTO::class, $response);
    }

    /**
     * @dataProvider invalidParameterProvider
     */
    public function testIndividualInvalidParameters(string $setter, $value): void
    {
        $this->builder
            ->setTransactionId('tx_123456')
            ->setCreatedAt(new DateTimeImmutable())
            ->setAmount(100.00)
            ->setCurrency('USD')
            ->setCardBin('411111')
            ->$setter($value);

        $response = $this->builder->build();

        $this->assertInstanceOf(PaymentResponseDTO::class, $response);
    }

    public function invalidParameterProvider(): array
    {
        return [
            ['setTransactionId', ''],
            ['setAmount', -100.00],
            ['setCurrency', 'INVALID'],
            ['setCardBin', '12'],
        ];
    }

    public function testFluentInterface(): void
    {
        $result = $this->builder
            ->setTransactionId('tx_123456')
            ->setCreatedAt(new DateTimeImmutable())
            ->setAmount(100.00)
            ->setCurrency('USD')
            ->setCardBin('411111');

        $this->assertInstanceOf(PaymentResponseBuilder::class, $result);
    }
}