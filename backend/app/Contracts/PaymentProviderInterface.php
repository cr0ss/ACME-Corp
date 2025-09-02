<?php

namespace App\Contracts;

use App\Models\Donation;

interface PaymentProviderInterface
{
    /**
     * Process a payment for the given donation.
     */
    public function processPayment(Donation $donation, array $paymentData): PaymentResult;

    /**
     * Refund a payment for the given donation.
     */
    public function refundPayment(Donation $donation): PaymentResult;

    /**
     * Get the provider name.
     */
    public function getProviderName(): string;

    /**
     * Validate payment data.
     */
    public function validatePaymentData(array $paymentData): bool;

    /**
     * Handle webhook notifications from the payment provider.
     */
    public function handleWebhook(array $webhookData): ?PaymentResult;
}

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly ?string $externalTransactionId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $responseData = []
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getExternalTransactionId(): ?string
    {
        return $this->externalTransactionId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
