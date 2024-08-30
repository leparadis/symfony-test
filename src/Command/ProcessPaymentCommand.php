<?php

namespace App\Command;

use App\Payment\Dto\PaymentRequestDTO;
use App\Payment\PaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-payment',
    description: 'Creates one time purchase for the specified provider.',
)]
class ProcessPaymentCommand extends Command
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to charge')
            ->addArgument('currency', InputArgument::REQUIRED, 'Currency in which to charge')
            ->addArgument('cardNumber', InputArgument::REQUIRED, 'Card number')
            ->addArgument('cardExpYear', InputArgument::REQUIRED, 'Card expiration year')
            ->addArgument('cardExpMonth', InputArgument::REQUIRED, 'Card expiration month')
            ->addArgument('cardCvv', InputArgument::REQUIRED, 'Card CVV')
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Payment provider (shift4 or oppwa)', 'shift4')
            ->addOption('customerId', 'c', InputOption::VALUE_OPTIONAL, 'Customer ID', 'cli-customer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $amount = $input->getArgument('amount');
        $currency = $input->getArgument('currency');
        $cardNumber = $input->getArgument('cardNumber');
        $cardExpYear = $input->getArgument('cardExpYear');
        $cardExpMonth = $input->getArgument('cardExpMonth');
        $cardCvv = $input->getArgument('cardCvv');
        $provider = $input->getOption('provider');
        $customerId = $input->getOption('customerId');

        $paymentRequest = new PaymentRequestDTO(
            (float) $amount,
            $currency,
            $customerId,
            $cardNumber,
            $cardExpMonth,
            $cardExpYear,
            $cardCvv
        );

        try {
            $response = $this->paymentService->processPayment($provider, $paymentRequest);

            $io->success([
                'Payment processed successfully',
                'Transaction ID: ' . $response->getTransactionId(),
                'Amount: ' . $response->getAmount() . ' ' . $response->getCurrency(),
                'Created At: ' . $response->getCreatedAt()->format('Y-m-d H:i:s'),
                'Card BIN: ' . $response->getCardBin()
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Payment processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}