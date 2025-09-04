<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Contracts\PaymentResult;
use App\Models\Donation;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProviderInterface
{
    public function processPayment(Donation $donation, array $paymentData): PaymentResult
    {
        // Simulate processing time
        usleep(100000); // 0.1 seconds

        // Determine success based on environment and testing context
        $success = $this->shouldSucceed();

        $transactionId = 'MOCK_'.Str::upper(Str::random(12));

        if ($success) {
            return new PaymentResult(
                success: true,
                transactionId: $transactionId,
                externalTransactionId: $transactionId,
                responseData: [
                    'provider' => 'mock',
                    'amount' => $donation->amount,
                    'currency' => 'USD',
                    'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
                    'processed_at' => now()->toISOString(),
                    'confirmation_code' => 'CONF_'.Str::upper(Str::random(8)),
                ]
            );
        }

        return new PaymentResult(
            success: false,
            transactionId: $transactionId,
            errorMessage: 'Mock payment failed for demonstration purposes',
            responseData: [
                'provider' => 'mock',
                'amount' => $donation->amount,
                'error_code' => 'MOCK_ERROR_'.rand(1000, 9999),
                'failed_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Determine if the payment should succeed based on environment
     */
    private function shouldSucceed(): bool
    {
        // Check multiple ways to detect testing environment
        $isTesting = app()->environment('testing') ||
                    app()->environment('test') ||
                    app()->runningUnitTests() ||
                    defined('PHPUNIT_COMPOSER_INSTALL') ||
                    class_exists('Tests\TestCase');

        // In testing environment, always succeed for reliability
        if ($isTesting) {
            return true;
        }

        // In non-testing environment, simulate 90% success rate for demo
        return rand(1, 100) <= 90;
    }

    public function refundPayment(Donation $donation): PaymentResult
    {
        // Mock refund - always succeeds for demo
        $transactionId = 'REFUND_'.Str::upper(Str::random(12));

        return new PaymentResult(
            success: true,
            transactionId: $transactionId,
            externalTransactionId: $transactionId,
            responseData: [
                'provider' => 'mock',
                'original_amount' => $donation->amount,
                'refunded_amount' => $donation->amount,
                'refunded_at' => now()->toISOString(),
                'refund_id' => 'REF_'.Str::upper(Str::random(8)),
            ]
        );
    }

    public function getProviderName(): string
    {
        return 'mock';
    }

    public function validatePaymentData(array $paymentData): bool
    {
        // Basic validation for mock provider
        return isset($paymentData['payment_method'])
            && in_array($paymentData['payment_method'], ['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'mock']);
    }

    public function handleWebhook(array $webhookData): ?PaymentResult
    {
        // Mock webhook handling
        if (! isset($webhookData['event_type']) || ! isset($webhookData['transaction_id'])) {
            return null;
        }

        return new PaymentResult(
            success: $webhookData['event_type'] === 'payment.completed',
            transactionId: $webhookData['transaction_id'],
            externalTransactionId: $webhookData['external_id'] ?? null,
            responseData: $webhookData
        );
    }
}
