<?php
namespace App\Payment\Interface;

interface PaymentRequestInterface
{
    public function getAmount(): float;
    public function getCurrency(): string;
    public function getCustomerId(): string;
    public function getCardNumber(): string;
    public function getCardExpiryMonth(): string;
    public function getCardExpiryYear(): string;
    public function getCardCvv(): string;
}
