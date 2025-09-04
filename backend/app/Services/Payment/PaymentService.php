<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Contracts\PaymentResult;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /** @var array<string, \App\Contracts\PaymentProviderInterface> */
    private array $providers = [];

    private ?PaymentProviderInterface $currentProvider = null;

    public function __construct()
    {
        $this->registerDefaultProviders();
    }

    /**
     * Register a payment provider.
     */
    public function registerProvider(string $name, PaymentProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Set the active payment provider.
     */
    public function setProvider(string $providerName): self
    {
        if (! isset($this->providers[$providerName])) {
            throw new InvalidArgumentException("Payment provider '{$providerName}' not found");
        }

        $this->currentProvider = $this->providers[$providerName];

        return $this;
    }

    /**
     * Get the current payment provider.
     */
    public function getCurrentProvider(): ?PaymentProviderInterface
    {
        return $this->currentProvider;
    }

    /**
     * Get available payment providers.
     *
     * @return array<int, string>
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Process a payment for a donation.
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function processPayment(Donation $donation, array $paymentData, ?string $providerName = null): PaymentResult
    {
        if ($providerName) {
            $this->setProvider($providerName);
        }

        if (! $this->currentProvider instanceof \App\Contracts\PaymentProviderInterface) {
            throw new InvalidArgumentException('No payment provider set');
        }

        // Validate payment data
        if (! $this->currentProvider->validatePaymentData($paymentData)) {
            return new PaymentResult(
                success: false,
                transactionId: 'INVALID_'.time(),
                errorMessage: 'Invalid payment data provided'
            );
        }

        return DB::transaction(function () use ($donation, $paymentData): \App\Contracts\PaymentResult {
            // Process payment with the provider
            if (! $this->currentProvider instanceof \App\Contracts\PaymentProviderInterface) {
                throw new InvalidArgumentException('No payment provider set');
            }

            $result = $this->currentProvider->processPayment($donation, $paymentData);

            // Update or create payment transaction record
            $transaction = PaymentTransaction::updateOrCreate(
                ['donation_id' => $donation->id],
                [
                    'provider' => $this->currentProvider->getProviderName(),
                    'external_transaction_id' => $result->getExternalTransactionId() ?? $result->getTransactionId(),
                    'amount' => $donation->amount,
                    'currency' => 'USD',
                    'status' => $result->isSuccess() ? 'completed' : 'failed',
                    'response_data' => $result->getResponseData(),
                ]
            );

            // Update donation status
            $donation->update([
                'status' => $result->isSuccess() ? 'completed' : 'failed',
                'transaction_id' => $result->getTransactionId(),
            ]);

            // If successful, update campaign amount
            if ($result->isSuccess()) {
                $campaign = $donation->campaign;
                $campaign->increment('current_amount', $donation->amount);

                // Check if campaign target is reached
                if ($campaign->current_amount >= $campaign->target_amount) {
                    $campaign->update(['status' => 'completed']);
                }
            }

            return $result;
        });
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(Donation $donation, ?string $providerName = null): PaymentResult
    {
        if ($donation->status !== 'completed') {
            return new PaymentResult(
                success: false,
                transactionId: 'REFUND_'.time(),
                errorMessage: 'Can only refund completed donations'
            );
        }

        if ($providerName) {
            $this->setProvider($providerName);
        } elseif ($donation->paymentTransaction) {
            $this->setProvider($donation->paymentTransaction->provider);
        }

        if (! $this->currentProvider instanceof \App\Contracts\PaymentProviderInterface) {
            throw new InvalidArgumentException('No payment provider set for refund');
        }

        return DB::transaction(function () use ($donation): \App\Contracts\PaymentResult {
            if (! $this->currentProvider instanceof \App\Contracts\PaymentProviderInterface) {
                throw new InvalidArgumentException('No payment provider set for refund');
            }

            $result = $this->currentProvider->refundPayment($donation);

            if ($result->isSuccess()) {
                // Update donation status
                $donation->update(['status' => 'refunded']);

                // Update payment transaction
                if ($donation->paymentTransaction) {
                    $donation->paymentTransaction->update([
                        'status' => 'refunded',
                        'response_data' => array_merge(
                            $donation->paymentTransaction->response_data ?? [],
                            ['refund_data' => $result->getResponseData()]
                        ),
                    ]);
                }

                // Update campaign amount
                $campaign = $donation->campaign;
                $campaign->decrement('current_amount', $donation->amount);

                // If campaign was completed due to target reached, reopen it
                if ($campaign->status === 'completed' && $campaign->current_amount < $campaign->target_amount) {
                    $campaign->update(['status' => 'active']);
                }
            }

            return $result;
        });
    }

    /**
     * Handle webhook from payment provider.
     *
     * @param  array<string, mixed>  $webhookData
     */
    public function handleWebhook(string $providerName, array $webhookData): ?PaymentResult
    {
        $this->setProvider($providerName);

        if (! $this->currentProvider instanceof \App\Contracts\PaymentProviderInterface) {
            throw new InvalidArgumentException('No payment provider set for webhook');
        }

        return $this->currentProvider->handleWebhook($webhookData);
    }

    /**
     * Register default payment providers.
     */
    private function registerDefaultProviders(): void
    {
        // Register mock provider (always available for testing)
        $this->registerProvider('mock', new MockPaymentProvider);

        // Register Stripe if configured
        if (config('services.stripe.secret')) {
            $this->registerProvider('stripe', new StripePaymentProvider);
        }

        // Register PayPal if configured
        if (config('services.paypal.client_id')) {
            $this->registerProvider('paypal', new PayPalPaymentProvider);
        }

        // Set default provider
        $defaultProvider = config('payment.default_provider', 'mock');
        if (isset($this->providers[$defaultProvider])) {
            $this->setProvider($defaultProvider);
        }
    }
}
