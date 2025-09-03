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


