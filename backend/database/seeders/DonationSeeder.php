<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $campaigns = Campaign::where('current_amount', '>', 0)->get();

        // Common donation amounts for realistic distribution
        $donationAmounts = [10, 25, 50, 75, 100, 150, 200, 250, 300, 500, 750, 1000, 1500, 2000];

        foreach ($campaigns as $campaign) {
            $remainingAmount = $campaign->current_amount;
            $donationCount = 0;

            while ($remainingAmount > 0 && $donationCount < 100) { // Max 100 donations per campaign
                // Choose a random donation amount that doesn't exceed remaining
                $possibleAmounts = array_filter($donationAmounts, fn ($amount) => $amount <= $remainingAmount);

                if (empty($possibleAmounts)) {
                    // If no standard amounts fit, use the remaining amount
                    $amount = $remainingAmount;
                } else {
                    $amount = $possibleAmounts[array_rand($possibleAmounts)];
                }

                // Create donation record
                $donation = Donation::create([
                    'amount' => $amount,
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'payment_method' => collect(['credit_card', 'debit_card', 'paypal', 'bank_transfer'])->random(),
                    'transaction_id' => 'TXN_'.Str::upper(Str::random(10)),
                    'status' => 'completed',
                    'anonymous' => rand(1, 10) <= 2, // 20% chance of anonymous
                    'message' => rand(1, 10) <= 3 ? $this->getRandomMessage() : null, // 30% chance of message
                    'created_at' => $campaign->created_at?->addDays(rand(0, 60)) ?? now(),
                ]);

                // Create corresponding payment transaction
                PaymentTransaction::create([
                    'donation_id' => $donation->id,
                    'provider' => 'mock',
                    'external_transaction_id' => 'EXT_'.Str::upper(Str::random(12)),
                    'amount' => $amount,
                    'currency' => 'USD',
                    'status' => 'completed',
                    'response_data' => [
                        'payment_method' => $donation->payment_method,
                        'processed_at' => $donation->created_at?->toISOString() ?? now()->toISOString(),
                        'confirmation_code' => 'CONF_'.Str::upper(Str::random(8)),
                    ],
                ]);

                $remainingAmount -= $amount;
                $donationCount++;
            }

            $this->command->info("Created {$donationCount} donations for campaign: {$campaign->title}");
        }
    }

    /**
     * Get a random donation message
     */
    private function getRandomMessage(): string
    {
        $messages = [
            'Happy to support this great cause!',
            'Keep up the amazing work!',
            'This is such an important initiative.',
            'Proud to contribute to the community.',
            'Wishing you all the best with this project.',
            'Thank you for making a difference!',
            'Hope this helps reach your goal.',
            'Great work, team!',
            'Supporting from the heart.',
            'Every bit counts!',
        ];

        return $messages[array_rand($messages)];
    }
}
