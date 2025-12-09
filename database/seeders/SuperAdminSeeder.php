<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Super Admin role exists
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if (!$superAdminRole) {
            $this->command->warn('Super Admin role does not exist. Please run RolePermissionSeeder first.');
            return;
        }

        // Check if Super Admin user already exists
        $existingAdmin = User::where('email', 'admin@example.com')->first();

        if ($existingAdmin) {
            $this->command->info('Super Admin user already exists. Updating...');
            $existingAdmin->assignRole('Super Admin');
            return;
        }

        // Create Super Admin user
        // Note: User model uses 'hashed' cast, so we don't need Hash::make()
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password', // Will be automatically hashed by the 'hashed' cast
            'email_verified_at' => now(),
        ]);

        // Assign Super Admin role
        $admin->assignRole('Super Admin');

        $this->command->info('Super Admin user created successfully!');
        $this->command->warn('Email: admin@example.com');
        $this->command->warn('Password: password');
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}

