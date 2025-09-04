<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(10)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'employee_id',
                        'department',
                        'role',
                        'is_admin',
                        'total_donated',
                        'donation_count',
                        'campaign_count',
                        'created_at',
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

        $this->assertGreaterThanOrEqual(11, $response->json('total')); // 10 created + 1 admin
    }

    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create([
            'name' => 'UniqueTestUser',
            'email' => 'unique.test@example.com',
            'employee_id' => 'UNIQUE123',
            'department' => 'UniqueTestDept',
        ]);
        // Create other users with specific data that won't match our search
        User::factory()->count(5)->create([
            'name' => 'Other User',
            'department' => 'OtherDept',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users?search=UniqueTest');

        $response->assertOk();
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals($targetUser->id, $data[0]['id']);
    }

    public function test_admin_can_filter_users_by_department(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create(['department' => 'Engineering']);
        User::factory()->count(2)->create(['department' => 'Marketing']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users?department=Engineering');

        $response->assertOk();
        $data = $response->json('data');
        
        $this->assertCount(3, $data);
        foreach ($data as $user) {
            $this->assertEquals('Engineering', $user['department']);
        }
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $userData = [
            'name' => 'New Employee',
            'email' => 'new.employee@acme.com',
            'employee_id' => 'EMP999',
            'department' => 'HR',
            'role' => 'HR Specialist',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', $userData);

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => 'New Employee',
                'email' => 'new.employee@acme.com',
                'employee_id' => 'EMP999',
                'department' => 'HR',
                'role' => 'HR Specialist',
                'is_admin' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new.employee@acme.com',
            'employee_id' => 'EMP999',
        ]);
    }

    public function test_admin_can_view_user_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'employee_id',
                'department',
                'role',
                'is_admin',
                'donations',
                'campaigns',
                'total_donated',
                'recent_activity',
                'created_at',
            ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'name' => 'Original Name',
            'department' => 'Original Department',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'department' => 'Updated Department',
            'role' => 'Updated Role',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$user->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'department' => 'Updated Department',
                'role' => 'Updated Role',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'department' => 'Updated Department',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_last_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$admin->id}");

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Cannot delete the last admin user',
            ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin1, 'sanctum')
            ->deleteJson("/api/admin/users/{$admin1->id}");

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Cannot delete your own account',
            ]);
    }

    public function test_admin_can_bulk_update_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(3)->create(['department' => 'Old Department']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-update', [
                'user_ids' => $users->pluck('id')->toArray(),
                'action' => 'change_department',
                'value' => 'New Department',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'updated_users',
                'total_updated',
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'department' => 'New Department',
            ]);
        }
    }

    public function test_admin_can_make_users_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-update', [
                'user_ids' => [$user->id],
                'action' => 'make_admin',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_get_user_statistics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(5)->create(['department' => 'Engineering']);
        User::factory()->count(3)->create(['department' => 'Marketing']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'overview' => [
                    'total_users',
                    'admin_users',
                    'active_users',
                    'new_users_this_month',
                ],
                'departments',
                'roles',
                'registration_trend',
            ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(9, $data['overview']['total_users']); // 8 created + 1 admin
    }

    public function test_admin_can_export_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/export/users?format=csv');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'employee_id',
                        'department',
                        'role',
                        'is_admin',
                        'total_donated',
                        'donations_count',
                        'created_at',
                    ],
                ],
                'format',
                'filename',
                'count',
            ]);

        $this->assertGreaterThanOrEqual(6, $response->json('count'));
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create();

        $endpoints = [
            ['method' => 'get', 'url' => '/api/admin/users'],
            ['method' => 'post', 'url' => '/api/admin/users'],
            ['method' => 'get', 'url' => "/api/admin/users/{$targetUser->id}"],
            ['method' => 'put', 'url' => "/api/admin/users/{$targetUser->id}"],
            ['method' => 'delete', 'url' => "/api/admin/users/{$targetUser->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($user, 'sanctum');
            
            switch ($endpoint['method']) {
                case 'get':
                    $response = $response->getJson($endpoint['url']);
                    break;
                case 'post':
                    $response = $response->postJson($endpoint['url'], []);
                    break;
                case 'put':
                    $response = $response->putJson($endpoint['url'], []);
                    break;
                case 'delete':
                    $response = $response->deleteJson($endpoint['url']);
                    break;
            }

            $response->assertForbidden();
        }
    }

    public function test_user_creation_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Test missing required fields
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name', 'email', 'employee_id', 'department', 'role', 'password'
            ]);

        // Test duplicate email
        $existingUser = User::factory()->create();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name' => 'Test User',
                'email' => $existingUser->email, // Duplicate
                'employee_id' => 'EMP999',
                'department' => 'Test',
                'role' => 'Test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_hashed_on_creation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'employee_id' => 'EMP999',
            'department' => 'Test',
            'role' => 'Test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', $userData);

        $response->assertCreated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotEquals('password123', $user->password);
    }
}
