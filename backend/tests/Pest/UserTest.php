<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('user can be created with required fields', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->id)->not->toBeNull();
});

test('user can be updated', function () {
    $user = User::factory()->create();
    
    $user->update([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    expect($user->fresh()->name)->toBe('Jane Smith');
    expect($user->fresh()->email)->toBe('jane@example.com');
});

test('user email must be unique', function () {
    User::factory()->create(['email' => 'test@example.com']);
    
    expect(fn() => User::factory()->create(['email' => 'test@example.com']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('user has required attributes', function () {
    $user = User::factory()->create();
    
    expect($user)->toHaveKeys(['id', 'name', 'email', 'created_at', 'updated_at']);
});

test('user email is properly formatted', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    expect($user->email)->toMatch('/^[^@]+@[^@]+\.[^@]+$/');
});

test('user can have admin privileges', function () {
    $adminUser = User::factory()->create(['is_admin' => true]);
    $regularUser = User::factory()->create(['is_admin' => false]);
    
    expect($adminUser->is_admin)->toBeTrue();
    expect($regularUser->is_admin)->toBeFalse();
});

test('user can have employee details', function () {
    $user = User::factory()->create([
        'employee_id' => 'EMP123',
        'department' => 'Engineering',
        'role' => 'developer',
    ]);
    
    expect($user->employee_id)->toBe('EMP123');
    expect($user->department)->toBe('Engineering');
    expect($user->role)->toBe('developer');
});
