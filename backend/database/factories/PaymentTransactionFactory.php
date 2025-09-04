<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['mock', 'stripe', 'paypal'];
        $provider = $this->faker->randomElement($providers);

        return [
            'donation_id' => Donation::factory(),
            'provider' => $provider,
            'external_transaction_id' => $this->generateExternalTransactionId($provider),
            'amount' => $this->faker->randomFloat(2, 5, 1000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'cancelled', 'refunded']),
            'response_data' => $this->generateResponseData($provider),
        ];
    }

    /**
     * Generate external transaction ID based on provider.
     */
    private function generateExternalTransactionId(string $provider): string
    {
        return match ($provider) {
            'stripe' => 'ch_'.$this->faker->bothify('??????????????????'),
            'paypal' => 'PAY-'.$this->faker->bothify('?????????????????'),
            'mock' => 'MOCK_'.$this->faker->bothify('????????????'),
            default => 'TXN_'.$this->faker->bothify('????????????????'),
        };
    }

    /**
     * Generate response data based on provider.
     *
     * @return array<string, mixed>
     */
    private function generateResponseData(string $provider): array
    {
        return match ($provider) {
            'stripe' => [
                'id' => 'ch_'.$this->faker->bothify('??????????????????????'),
                'object' => 'charge',
                'amount' => $this->faker->numberBetween(500, 100000),
                'currency' => 'usd',
                'description' => 'CSR Platform Donation',
                'status' => 'succeeded',
            ],
            'paypal' => [
                'id' => 'PAY-'.$this->faker->bothify('?????????????????'),
                'state' => 'approved',
                'cart' => $this->faker->bothify('?????????????????'),
                'payer' => [
                    'payment_method' => 'paypal',
                    'status' => 'VERIFIED',
                ],
            ],
            'mock' => [
                'mock_transaction_id' => 'MOCK_'.$this->faker->bothify('????????????'),
                'processed_at' => now()->toISOString(),
                'mock_status' => 'success',
            ],
            default => [],
        };
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the transaction is for Stripe.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'stripe',
            'external_transaction_id' => 'ch_'.$this->faker->bothify('??????????????????????'),
            'response_data' => $this->generateResponseData('stripe'),
        ]);
    }

    /**
     * Indicate that the transaction is for PayPal.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'paypal',
            'external_transaction_id' => 'PAY-'.$this->faker->bothify('?????????????????'),
            'response_data' => $this->generateResponseData('paypal'),
        ]);
    }

    /**
     * Indicate that the transaction is for mock provider.
     */
    public function mock(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'mock',
            'external_transaction_id' => 'MOCK_'.$this->faker->bothify('????????????'),
            'response_data' => $this->generateResponseData('mock'),
        ]);
    }
}
