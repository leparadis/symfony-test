<?php

namespace Tests\App\Command;

use App\Command\ProcessPaymentCommand;
use App\Payment\Dto\PaymentRequestDTO;
use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\PaymentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class ProcessPaymentCommandTest extends TestCase
{
    private PaymentService $paymentService;
    private ProcessPaymentCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->command = new ProcessPaymentCommand($this->paymentService);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessfulPayment()
    {
        $responseDTO = new PaymentResponseDTO(
            'trans123',
            new \DateTimeImmutable('2023-01-01 12:00:00'),
            100.00,
            'USD',
            '123456'
        );

        $this->paymentService
            ->expects($this->once())
            ->method('processPayment')
            ->willReturn($responseDTO);

        $exitCode = $this->commandTester->execute([
            'amount' => '100.00',
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
            '--provider' => 'shift4',
            '--customerId' => 'test-customer'
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Payment processed successfully', $output);
        $this->assertStringContainsString('Transaction ID: trans123', $output);
        $this->assertStringContainsString('Amount: 100 USD', $output);
        $this->assertStringContainsString('Created At: 2023-01-01 12:00:00', $output);
        $this->assertStringContainsString('Card BIN: 123456', $output);
    }

    public function testExecuteFailedPayment()
    {
        $this->paymentService
            ->expects($this->once())
            ->method('processPayment')
            ->willThrowException(new \Exception('Payment failed'));

        $exitCode = $this->commandTester->execute([
            'amount' => '100.00',
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertEquals(1, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Payment processing failed: Payment failed', $output);
    }

    public function testExecuteWithDifferentProvider()
    {
        $responseDTO = new PaymentResponseDTO(
            'trans456',
            new \DateTimeImmutable('2023-01-01 12:00:00'),
            200.00,
            'EUR',
            '654321'
        );

        $this->paymentService
            ->expects($this->once())
            ->method('processPayment')
            ->with('oppwa', $this->isInstanceOf(PaymentRequestDTO::class))
            ->willReturn($responseDTO);

        $exitCode = $this->commandTester->execute([
            'amount' => '200.00',
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
            '--provider' => 'oppwa',
        ]);

        $this->assertEquals(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Payment processed successfully', $output);
        $this->assertStringContainsString('Transaction ID: trans456', $output);
        $this->assertStringContainsString('Amount: 200 EUR', $output);
    }
}