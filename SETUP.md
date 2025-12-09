# Laravel 11 LTS Project Setup Guide

This guide will help you set up a Laravel 11 LTS project with Inertia.js, Svelte, Vite, TailwindCSS, Breeze authentication, Sanctum, and Spatie Laravel Permission.

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18.x and npm
- MySQL/PostgreSQL/SQLite

## Step 1: Create Laravel Project

```bash
composer create-project laravel/laravel:^11.0 app038
cd app038
```

## Step 2: Install Composer Dependencies

```bash
# Install Laravel Breeze (authentication scaffolding)
composer require laravel/breeze --dev

# Install Inertia.js Laravel adapter
composer require inertiajs/inertia-laravel

# Install Laravel Sanctum (API authentication)
composer require laravel/sanctum

# Install Spatie Laravel Permission (role & permission management)
composer require spatie/laravel-permission
```

## Step 3: Install Breeze with Inertia Svelte Stack

```bash
php artisan breeze:install svelte
```

This command will:
- Install and configure Inertia.js
- Set up Svelte components
- Configure Vite
- Install TailwindCSS
- Create authentication views and controllers

## Step 4: Publish Package Configurations

```bash
# Publish Sanctum configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Spatie Permission configuration
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## Step 5: Install NPM Dependencies

```bash
npm install
```

This will install all dependencies including:
- `@inertiajs/svelte` - Inertia.js Svelte adapter
- `svelte` - Svelte framework
- `@vitejs/plugin-svelte` - Vite Svelte plugin
- `tailwindcss` - TailwindCSS framework
- `autoprefixer` - CSS autoprefixer
- `postcss` - PostCSS processor

## Step 6: Run Database Migrations

```bash
# Run Sanctum migrations
php artisan migrate

# Run Spatie Permission migrations (included in migrate)
# The package migrations are automatically loaded
```

## Step 7: Build Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

## Step 8: Start Development Server

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (if not using --host flag)
npm run dev
```

## Additional Setup Steps

### Configure User Model for Spatie Permission

Add the `HasRoles` trait to your `User` model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    // ... rest of the model
}
```

### Configure Sanctum for SPA

Update `config/sanctum.php` to include your frontend domain:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

### Seed Initial Roles and Permissions

Create a seeder to set up initial roles and permissions:

```bash
php artisan make:seeder RolePermissionSeeder
```

## Project Structure

```
app038/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   └── ProfileController.php
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   └── Requests/
│   ├── Models/
│   │   └── User.php
│   └── Providers/
├── bootstrap/
├── config/
│   ├── permission.php (Spatie)
│   └── sanctum.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── js/
│   │   ├── app.js (Inertia setup)
│   │   ├── Pages/
│   │   │   ├── Dashboard.svelte
│   │   │   ├── Welcome.svelte
│   │   │   └── Auth/
│   │   └── Layouts/
│   │       └── AppLayout.svelte
│   └── css/
│       └── app.css
├── routes/
│   ├── web.php
│   └── auth.php (Breeze)
├── storage/
├── tests/
├── .env
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
└── postcss.config.js
```

## Environment Variables

See `.env.example` for required environment variables. Key variables include:

- `APP_NAME`
- `APP_URL`
- `DB_*` (Database configuration)
- `SANCTUM_STATEFUL_DOMAINS` (for SPA authentication)

## Next Steps

1. Configure your database in `.env`
2. Run migrations: `php artisan migrate`
3. Create initial roles and permissions seeder
4. Set up middleware for role/permission checks
5. Configure Sanctum for your frontend domain
6. Start building your application!

