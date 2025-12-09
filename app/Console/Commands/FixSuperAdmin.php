<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FixSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Super Admin user - ensure user exists with correct password and role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking Super Admin user...');

        // Ensure Super Admin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->info('Super Admin role exists.');

        // Check if user exists
        $user = User::where('email', 'admin@example.com')->first();

        if (!$user) {
            $this->warn('Super Admin user not found. Creating...');
            // Note: User model uses 'hashed' cast, so we don't need Hash::make()
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => 'password', // Will be automatically hashed by the 'hashed' cast
                'email_verified_at' => now(),
            ]);
            $this->info('Super Admin user created.');
        } else {
            $this->info('Super Admin user found.');
        }

        // Reset password to ensure it's correct
        // Note: User model uses 'hashed' cast, so we don't need Hash::make()
        $user->password = 'password'; // Will be automatically hashed by the 'hashed' cast
        $user->save();
        $this->info('Password reset to: password');

        // Ensure role is assigned
        if (!$user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
            $this->info('Super Admin role assigned.');
        } else {
            $this->info('Super Admin role already assigned.');
        }

        // Verify password
        if (Hash::check('password', $user->password)) {
            $this->info('✓ Password verification successful!');
        } else {
            $this->error('✗ Password verification failed!');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Super Admin credentials:');
        $this->line('  Email: admin@example.com');
        $this->line('  Password: password');
        $this->newLine();
        $this->warn('⚠️  Please change the password after first login!');

        return Command::SUCCESS;
    }
}
