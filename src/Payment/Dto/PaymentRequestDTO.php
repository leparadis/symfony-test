<?php
namespace App\Payment\Dto;

use App\Payment\Interface\PaymentRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentRequestDTO implements PaymentRequestInterface
{
    /**
     * @Assert\NotBlank()
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
     */
    private string $customerId;

    /**
     * @Assert\NotBlank()
     * @Assert\CreditCardNumber()
     */
    private string $cardNumber;

    /**
     * @Assert\NotBlank()
     * @Assert\Range(min=1, max=12)
     * @Assert\Length(exactly=2)
     */
    private string $cardExpiryMonth;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(exactly=4)
     * @Assert\GreaterThanOrEqual(
     *     value = "now",
     *     message = "The expiry date must be in the future"
     * )
     */
    private string $cardExpiryYear;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=4)
     */
    private string $cardCvv;

    public function __construct(
        float $amount,
        string $currency,
        string $customerId,
        string $cardNumber,
        string $cardExpiryMonth,
        string $cardExpiryYear,
        string $cardCvv
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->customerId = $customerId;
        $this->cardNumber = $cardNumber;
        $this->cardExpiryMonth = $cardExpiryMonth;
        $this->cardExpiryYear = $cardExpiryYear;
        $this->cardCvv = $cardCvv;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    public function getCardExpiryMonth(): string
    {
        return $this->cardExpiryMonth;
    }

    public function getCardExpiryYear(): string
    {
        return $this->cardExpiryYear;
    }

    public function getCardCvv(): string
    {
        return $this->cardCvv;
    }
}