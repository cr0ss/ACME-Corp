<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Donation
 *
 * @property int $id
 * @property float $amount
 * @property int $campaign_id
 * @property int $user_id
 * @property string $payment_method
 * @property string|null $transaction_id
 * @property string $status
 * @property bool $anonymous
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Campaign $campaign
 * @property-read \App\Models\PaymentTransaction|null $paymentTransaction
 */
class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'campaign_id',
        'user_id',
        'payment_method',
        'transaction_id',
        'status',
        'anonymous',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'anonymous' => 'boolean',
        ];
    }

    /**
     * Get the user who made this donation.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign this donation belongs to.
     */
    public function campaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the payment transaction for this donation.
     */
    public function paymentTransaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}