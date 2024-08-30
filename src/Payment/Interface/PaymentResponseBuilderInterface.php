<?php

namespace App\Payment\Interface;

use App\Payment\Dto\PaymentResponseDTO;
use DateTimeImmutable;

interface PaymentResponseBuilderInterface
{
    public function setTransactionId(string $transactionId): self;
    public function setCreatedAt(DateTimeImmutable $createdAt): self;
    public function setAmount(float $amount): self;
    public function setCurrency(string $currency): self;
    public function setCardBin(string $cardBin): self;
    public function build(): PaymentResponseDTO;
}