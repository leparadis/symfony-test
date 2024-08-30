<?php
namespace App\Payment\Interface;

interface PaymentClientBuilderInterface
{
    public function build(string $providerName): PaymentClientInterface;
}