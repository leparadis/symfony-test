<?php
namespace App\Payment\Builder;


use App\Payment\Dto\PaymentResponseDTO;
use App\Payment\Interface\PaymentResponseBuilderInterface;
use DateTimeImmutable;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;

class PaymentResponseBuilder implements PaymentResponseBuilderInterface
{
    private ?string $transactionId = null;
    private ?DateTimeImmutable $createdAt = null;
    private ?float $amount = null;
    private ?string $currency = null;
    private ?string $cardBin = null;
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function setTransactionId(string $transactionId): PaymentResponseBuilderInterface
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): PaymentResponseBuilderInterface
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setAmount(float $amount): PaymentResponseBuilderInterface
    {
        $this->amount = $amount;
        return $this;
    }

    public function setCurrency(string $currency): PaymentResponseBuilderInterface
    {
        $this->currency = $currency;
        return $this;
    }

    public function setCardBin(string $cardBin): PaymentResponseBuilderInterface
    {
        $this->cardBin = $cardBin;
        return $this;
    }

    public function build(): PaymentResponseDTO
    {
        $this->validateAllParametersAreSet();

        $paymentResponseDTO = new PaymentResponseDTO(
            $this->transactionId,
            $this->createdAt,
            $this->amount,
            $this->currency,
            $this->cardBin
        );

        $errors = $this->validator->validate($paymentResponseDTO);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new InvalidArgumentException(implode(', ', $errorMessages));
        }

        return $paymentResponseDTO;
    }

    private function validateAllParametersAreSet(): void
    {
        $nullParameters = [];

        if ($this->transactionId === null) $nullParameters[] = 'transactionId';
        if ($this->createdAt === null) $nullParameters[] = 'createdAt';
        if ($this->amount === null) $nullParameters[] = 'amount';
        if ($this->currency === null) $nullParameters[] = 'currency';
        if ($this->cardBin === null) $nullParameters[] = 'cardBin';

        if (!empty($nullParameters)) {
            throw new InvalidArgumentException('The following parameters are not set: ' . implode(', ', $nullParameters));
        }
    }
}