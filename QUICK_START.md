# Quick Start Guide

## Installation Commands

### 1. Install Composer Dependencies

```bash
composer install
composer require laravel/breeze --dev
composer require inertiajs/inertia-laravel
composer require laravel/sanctum
composer require spatie/laravel-permission
```

### 2. Install Breeze with Svelte Stack

```bash
php artisan breeze:install svelte
```

### 3. Publish Configurations

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 4. Install NPM Dependencies

```bash
npm install
```

### 5. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials and other settings.

### 6. Run Migrations

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### 7. Start Development Servers

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
npm run dev
```

Visit `http://localhost:8000` in your browser.

## Key Configuration Files

- **Sanctum**: `config/sanctum.php` - Configure stateful domains for SPA
- **Permission**: `config/permission.php` - Spatie Permission settings
- **Vite**: `vite.config.js` - Frontend build configuration
- **Tailwind**: `tailwind.config.js` - CSS framework configuration

## Usage Examples

### Using Roles and Permissions

```php
// Assign role to user
$user->assignRole('admin');

// Give permission to user
$user->givePermissionTo('edit articles');

// Check role
if ($user->hasRole('admin')) {
    // User is admin
}

// Check permission
if ($user->can('edit articles')) {
    // User can edit articles
}
```

### Protecting Routes with Roles

```php
// In routes/web.php
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware(['permission:edit articles'])->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);
});
```

### Using Sanctum for API Authentication

```php
// Create token
$token = $user->createToken('api-token', ['server:update'])->plainTextToken;

// In API routes (routes/api.php)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

## Troubleshooting

### Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Assets

```bash
npm run build
```

### Reset Database

```bash
php artisan migrate:fresh --seed
```

