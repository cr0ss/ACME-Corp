<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Campaign
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property float $target_amount
 * @property float $current_amount
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $status
 * @property int $category_id
 * @property int $user_id
 * @property bool $featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\CampaignCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Donation> $donations
 * @property-read int|null $donations_count
 * @property-read float $progress_percentage
 * @property-read bool $is_active
 */
class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'target_amount',
        'current_amount',
        'start_date',
        'end_date',
        'status',
        'category_id',
        'user_id',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'featured' => 'boolean',
        ];
    }

    /**
     * Get the user who created this campaign.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category this campaign belongs to.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class, 'category_id');
    }

    /**
     * Get donations for this campaign.
     */
    public function donations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the progress percentage of the campaign.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0.0;
        }
        
        return round(($this->current_amount / $this->target_amount) * 100, 2);
    }

    /**
     * Check if the campaign goal has been reached.
     */
    public function getIsGoalReachedAttribute(): bool
    {
        return bccomp($this->current_amount, $this->target_amount, 2) >= 0;
    }

    /**
     * Update campaign status based on current progress and dates.
     */
    public function updateStatus(): void
    {
        $now = now();
        
        // Check if campaign should be active
        if ($this->start_date <= $now && $this->end_date >= $now) {
            if ($this->status !== 'active') {
                $this->update(['status' => 'active']);
            }
        }
        // Check if campaign has ended
        elseif ($this->end_date < $now) {
            if ($this->is_goal_reached && $this->status !== 'completed') {
                $this->update(['status' => 'completed']);
            } elseif (!$this->is_goal_reached && $this->status !== 'ended') {
                $this->update(['status' => 'ended']);
            }
        }
    }



    /**
     * Check if campaign is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }
}