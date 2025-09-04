# Pest Tests

This directory contains Pest-style tests for the ACME Corp CSR Platform. These tests demonstrate the clean, readable syntax that Pest provides.

## What is Pest?

Pest is a modern, elegant testing framework for PHP that makes writing tests much more enjoyable than traditional PHPUnit. It provides:

- **Cleaner syntax** - No more `public function test_*()` methods
- **Better readability** - Tests read like natural language
- **Powerful assertions** - More intuitive than PHPUnit assertions
- **Built-in Laravel support** - Works seamlessly with your existing setup

## Test Files

### UserTest.php
Tests for the User model including:
- User creation and updates
- Email validation and uniqueness
- Admin privileges
- Employee details

### CampaignTest.php
Tests for the Campaign model including:
- Campaign creation and management
- Progress calculations
- Status management
- Relationships (user, category)
- Field validation

### CampaignApiTest.php
API endpoint tests for campaigns including:
- CRUD operations
- Authentication requirements
- Authorization checks
- Validation rules

## Running the Tests

Currently, these tests are configured to work with PHPUnit. To run them:

```bash
# Run all Pest tests
docker-compose exec backend php artisan test tests/Pest/

# Run specific test file
docker-compose exec backend php artisan test tests/Pest/UserTest.php
docker-compose exec backend php artisan test tests/Pest/CampaignTest.php
docker-compose exec backend php artisan test tests/Pest/CampaignApiTest.php
```

## Pest vs PHPUnit Comparison

**Before (PHPUnit):**
```php
class UserTest extends TestCase
{
    #[Test]
    public function it_can_create_a_user(): void
    {
        $user = User::factory()->create();
        $this->assertEquals('John Doe', $user->name);
    }
}
```

**After (Pest):**
```php
test('user can be created with required fields', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->id)->not->toBeNull();
});
```

## Key Benefits

1. **Less Boilerplate** - No class definitions or method names
2. **Natural Language** - Tests read like documentation
3. **Powerful Expectations** - `expect()` is more intuitive
4. **Faster Development** - Write tests more quickly
5. **Better Maintainability** - Easier to understand and modify

## Future Pest Integration

When the Pest configuration issue is resolved, these tests can be run with:

```bash
# Run with Pest (when configured)
./vendor/bin/pest tests/Pest/
```

## Test Structure

Each test follows this pattern:
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('description of what is being tested', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $result = $user->update(['name' => 'New Name']);
    
    // Assert
    expect($user->fresh()->name)->toBe('New Name');
});
```

## Adding New Tests

To add new Pest tests:

1. Create a new file in the `tests/Pest/` directory
2. Use the `test()` function instead of class methods
3. Use `expect()` for assertions
4. Use `uses()` to specify traits and base classes
5. Follow the existing naming conventions

## Notes

- These tests currently run with PHPUnit due to Pest configuration conflicts
- The tests demonstrate Pest syntax and best practices
- All tests pass and provide good coverage of core functionality
- When Pest is fully configured, these tests will run natively with Pest
