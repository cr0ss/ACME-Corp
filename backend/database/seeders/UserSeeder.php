<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'employee_id' => 'ACME001',
            'department' => 'IT',
            'role' => 'admin',
            'is_admin' => true,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create some employee users
        $employees = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@acme.com',
                'employee_id' => 'ACME002',
                'department' => 'Marketing',
                'role' => 'employee',
                'is_admin' => false,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@acme.com',
                'employee_id' => 'ACME003',
                'department' => 'HR',
                'role' => 'employee',
                'is_admin' => false,
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@acme.com',
                'employee_id' => 'ACME004',
                'department' => 'Finance',
                'role' => 'employee',
                'is_admin' => false,
            ],
        ];

        foreach ($employees as $employee) {
            User::create([
                ...$employee,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Generate additional 76 users using factory (to reach 80 total: 1 admin + 3 manual + 76 factory)
        User::factory(76)->create();
    }
}