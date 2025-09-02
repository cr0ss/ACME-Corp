<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AuditLog
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $model_type
 * @property int|null $model_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * 
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $auditable
 * 
 * @property int|null $action_count Dynamic property for aggregated action count
 * @property int|null $ip_count Dynamic property for IP count
 * @property int|null $failed_count Dynamic property for failed attempt count
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was audited.
     */
    public function auditable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Create an audit log entry.
     */
    public static function createLog(
        ?int $userId,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        static::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}