<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $employee_id
 * @property string|null $department
 * @property string|null $role
 * @property bool $is_admin
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Campaign> $campaigns
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Donation> $donations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $campaigns_count
 * @property-read int|null $donations_count
 * @property-read int|null $audit_logs_count
 * 
 * @property float|null $total_donated Dynamic property for aggregated donation amount
 * @property int|null $donation_count Dynamic property for donation count
 * @property int|null $campaign_count Dynamic property for campaign count
 * @property mixed $recent_activity Dynamic property for recent audit logs
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'department',
        'role',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get campaigns created by this user.
     */
    public function campaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get donations made by this user.
     */
    public function donations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get audit logs for this user.
     */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
