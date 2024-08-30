<?php

namespace App\Payment\Dto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentResponseDTO
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    private string $transactionId;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("\DateTimeImmutable")
     */
    private DateTimeImmutable $createdAt;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("float")
     * @Assert\PositiveOrZero()
     */
    private float $amount;

    /**
     * @Assert\NotBlank()
     * @Assert\Currency()
     */
    private string $currency;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(exactly=6)
     * @Assert\Regex(
     *     pattern="/^\d{6}$/",
     *     message="Card BIN must be exactly 6 digits."
     * )
     */
    private string $cardBin;

    public function __construct(
        string $transactionId,
        DateTimeImmutable $createdAt,
        float $amount,
        string $currency,
        string $cardBin
    ) {
        $this->transactionId = $transactionId;
        $this->createdAt = $createdAt;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->cardBin = $cardBin;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCardBin(): string
    {
        return $this->cardBin;
    }
}