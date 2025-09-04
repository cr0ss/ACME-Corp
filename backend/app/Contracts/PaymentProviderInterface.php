<?php

namespace App\Contracts;

use App\Models\Donation;

interface PaymentProviderInterface
{
    /**
     * Process a payment for the given donation.
     *
     * @param  array<string, mixed>  $paymentData
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
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function validatePaymentData(array $paymentData): bool;

    /**
     * Handle webhook notifications from the payment provider.
     *
     * @param  array<string, mixed>  $webhookData
     */
    public function handleWebhook(array $webhookData): ?PaymentResult;
}
