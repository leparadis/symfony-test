<?php
namespace App\Payment\Interface;

use App\Payment\Dto\PaymentResponseDTO;

interface PaymentClientInterface
{
    public function processPayment(PaymentRequestInterface $paymentRequest): PaymentResponseDTO;
}