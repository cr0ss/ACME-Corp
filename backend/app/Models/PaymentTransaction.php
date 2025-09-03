<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PaymentTransaction
 *
 * @property int $id
 * @property int $donation_id
 * @property string $provider
 * @property string $provider_transaction_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property array<string, mixed>|null $request_data
 * @property array<string, mixed>|null $response_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Donation $donation
 */
class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'provider',
        'external_transaction_id',
        'amount',
        'currency',
        'status',
        'response_data',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'response_data' => 'array',
        ];
    }

    /**
     * Get the donation this transaction belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Donation, $this>
     */
    public function donation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}