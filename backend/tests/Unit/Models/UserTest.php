<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_required_fields(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'employee_id' => 'EMP001',
            'department' => 'IT',
            'role' => 'employee',
        ];

        $user = User::create($userData);
        $user->refresh(); // Load default values from database

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('EMP001', $user->employee_id);
        $this->assertEquals('IT', $user->department);
        $this->assertEquals('employee', $user->role);
        $this->assertFalse($user->is_admin);
    }

    public function test_admin_user_can_be_created(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'employee_id' => 'EMP002',
            'department' => 'Management',
            'role' => 'admin',
            'is_admin' => true,
        ]);

        $this->assertTrue($user->is_admin);
        $this->assertEquals('admin', $user->role);
    }

    public function test_user_has_many_campaigns(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->campaigns->contains($campaign));
        $this->assertInstanceOf(Campaign::class, $user->campaigns->first());
    }

    public function test_user_has_many_donations(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $donation = Donation::factory()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
        ]);

        $this->assertTrue($user->donations->contains($donation));
        $this->assertInstanceOf(Donation::class, $user->donations->first());
    }

    public function test_user_has_many_audit_logs(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test_action',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->assertTrue($user->auditLogs->contains($auditLog));
        $this->assertInstanceOf(AuditLog::class, $user->auditLogs->first());
    }

    public function test_password_is_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext',
            'employee_id' => 'EMP003',
            'department' => 'Test',
            'role' => 'employee',
        ]);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plaintext', $user->password));
    }

    public function test_is_admin_cast_to_boolean(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'employee_id' => 'EMP004',
            'department' => 'Test',
            'role' => 'employee',
            'is_admin' => '1', // String input
        ]);

        $this->assertIsBool($user->is_admin);
        $this->assertTrue($user->is_admin);
    }
}
