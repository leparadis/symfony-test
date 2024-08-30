<?php

namespace App\Controller;

use App\Payment\Dto\PaymentRequestDTO;
use App\Payment\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[Route('/app/example/{provider}', name: 'app_process_payment', methods: ['POST'])]
    public function processPayment(Request $request, string $provider): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!in_array($provider, ['oppwa', 'shift4'])) {
            return $this->json(['error' => 'Invalid provider. Use either "oppwa" or "shift4".'], 400);
        }

        if (!$this->validateRequestData($data)) {
            return $this->json(['error' => 'Invalid request data.'], 400);
        }

        $paymentRequest = new PaymentRequestDTO(
            (float) $data['amount'],
            $data['currency'],
            $data['customerId'] ?? 'api-customer',
            $data['cardNumber'],
            $data['cardExpMonth'],
            $data['cardExpYear'],
            $data['cardCvv']
        );

        try {
            $response = $this->paymentService->processPayment($provider, $paymentRequest);

            return $this->json([
                'message' => 'Payment processed successfully',
                'transactionId' => $response->getTransactionId(),
                'amount' => $response->getAmount(),
                'currency' => $response->getCurrency(),
                'createdAt' => $response->getCreatedAt()->format('Y-m-d H:i:s'),
                'cardBin' => $response->getCardBin()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
        }
    }

    private function validateRequestData(array $data): bool
    {
        $requiredFields = ['amount', 'currency', 'cardNumber', 'cardExpYear', 'cardExpMonth', 'cardCvv'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        return true;
    }
}