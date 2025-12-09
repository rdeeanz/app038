# Setup Commands Reference

Complete list of commands to set up the Laravel 11 LTS project with all integrations.

## Composer Commands

```bash
# Create new Laravel 11 project
composer create-project laravel/laravel:^11.0 app038
cd app038

# Install all required packages
composer require laravel/breeze --dev
composer require inertiajs/inertia-laravel
composer require laravel/sanctum
composer require spatie/laravel-permission
```

## Artisan Commands

```bash
# Install Breeze with Svelte stack
php artisan breeze:install svelte

# Publish package configurations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed initial roles and permissions
php artisan db:seed --class=RolePermissionSeeder

# Clear all caches (if needed)
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## NPM Commands

```bash
# Install all npm dependencies
npm install

# Development server (with hot reload)
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

## Development Server Commands

```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Start Vite development server
npm run dev

# Or use Vite with host flag (for external access)
npm run dev -- --host
```

## Database Commands

```bash
# Fresh migration (drops all tables and re-runs migrations)
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Show migration status
php artisan migrate:status
```

## Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter TestClassName

# Run with coverage
php artisan test --coverage
```

## Production Commands

```bash
# Optimize for production
php artisan optimize

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Build assets for production
npm run build
```

## Spatie Permission Commands

```bash
# Clear permission cache
php artisan permission:cache-reset

# Create a role
php artisan tinker
>>> $role = Spatie\Permission\Models\Role::create(['name' => 'admin']);

# Create a permission
>>> $permission = Spatie\Permission\Models\Permission::create(['name' => 'edit articles']);

# Assign role to user
>>> $user = App\Models\User::find(1);
>>> $user->assignRole('admin');

# Give permission to user
>>> $user->givePermissionTo('edit articles');
```

## Sanctum Commands

```bash
# Create API token (via tinker)
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = $user->createToken('api-token', ['server:update'])->plainTextToken;
>>> echo $token;

# Revoke all tokens for user
>>> $user->tokens()->delete();
```

## Complete Setup Script

Here's a complete setup script you can run:

```bash
#!/bin/bash

# Install Composer dependencies
composer install
composer require laravel/breeze --dev
composer require inertiajs/inertia-laravel
composer require laravel/sanctum
composer require spatie/laravel-permission

# Install Breeze
php artisan breeze:install svelte

# Publish configurations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Setup environment
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Install NPM dependencies
npm install

# Run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolePermissionSeeder

echo "Setup complete! Run 'php artisan serve' and 'npm run dev' to start development."
```

Save this as `setup.sh`, make it executable (`chmod +x setup.sh`), and run it.

