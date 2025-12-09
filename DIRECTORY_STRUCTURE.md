# Project Directory Structure

This document outlines the complete directory structure for the Laravel 11 LTS project with Inertia.js, Svelte, Breeze, Sanctum, and Spatie Permission.

```
app038/
├── app/
│   ├── Console/
│   │   └── Kernel.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   └── ProfileController.php
│   │   ├── Middleware/
│   │   │   ├── HandleInertiaRequests.php          # Inertia middleware
│   │   │   └── RedirectIfAuthenticated.php
│   │   └── Requests/
│   │       └── Auth/
│   ├── Models/
│   │   └── User.php                                # HasRoles trait added
│   └── Providers/
│       ├── AppServiceProvider.php                  # Gate::before for Super Admin
│       ├── AuthServiceProvider.php
│       └── EventServiceProvider.php
│
├── bootstrap/
│   ├── app.php                                     # Middleware configuration
│   ├── cache/
│   └── providers.php
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── permission.php                              # Spatie Permission config
│   ├── sanctum.php                                 # Sanctum configuration
│   └── ...
│
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2019_08_19_000000_create_failed_jobs_table.php
│   │   ├── 2019_12_14_000001_create_personal_access_tokens_table.php  # Sanctum
│   │   └── 2024_01_01_000000_create_permission_tables.php              # Spatie
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── RolePermissionSeeder.php                # Initial roles/permissions
│   └── .gitignore
│
├── public/
│   ├── index.php
│   └── .htaccess
│
├── resources/
│   ├── css/
│   │   └── app.css                                 # TailwindCSS imports
│   ├── js/
│   │   ├── app.js                                  # Inertia app initialization
│   │   ├── bootstrap.js                            # Axios setup
│   │   ├── Layouts/
│   │   │   └── AppLayout.svelte                    # Main layout component
│   │   └── Pages/
│   │       ├── Auth/
│   │       │   ├── Login.svelte
│   │       │   ├── Register.svelte
│   │       │   ├── ForgotPassword.svelte
│   │       │   └── ResetPassword.svelte
│   │       ├── Dashboard.svelte
│   │       ├── Profile/
│   │       │   └── Edit.svelte
│   │       └── Welcome.svelte
│   └── views/
│       └── app.blade.php                          # Inertia root template
│
├── routes/
│   ├── auth.php                                    # Breeze auth routes
│   ├── console.php
│   └── web.php
│
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│
├── tests/
│   ├── Feature/
│   │   └── ExampleTest.php
│   └── Unit/
│       └── ExampleTest.php
│
├── .env
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── postcss.config.js                               # PostCSS configuration
├── tailwind.config.js                              # TailwindCSS configuration
├── vite.config.js                                  # Vite configuration
├── README.md
├── SETUP.md
└── DIRECTORY_STRUCTURE.md
```

## Key Files Explained

### Configuration Files

- **`config/sanctum.php`**: Sanctum configuration for SPA and API authentication
- **`config/permission.php`**: Spatie Permission package configuration
- **`bootstrap/app.php`**: Application bootstrap with middleware configuration

### Frontend Files

- **`resources/js/app.js`**: Inertia.js app initialization with Svelte
- **`resources/js/bootstrap.js`**: Axios configuration
- **`resources/views/app.blade.php`**: Root Blade template for Inertia
- **`resources/js/Layouts/AppLayout.svelte`**: Main layout component
- **`resources/js/Pages/`**: Svelte page components

### Backend Files

- **`app/Models/User.php`**: User model with `HasRoles` trait
- **`app/Http/Middleware/HandleInertiaRequests.php`**: Inertia middleware
- **`app/Providers/AppServiceProvider.php`**: Super Admin gate configuration
- **`database/seeders/RolePermissionSeeder.php`**: Initial roles and permissions

### Build Configuration

- **`vite.config.js`**: Vite configuration with Svelte plugin
- **`tailwind.config.js`**: TailwindCSS configuration
- **`postcss.config.js`**: PostCSS configuration for TailwindCSS

## Database Tables

After running migrations, the following tables will be created:

- `users` - User accounts
- `personal_access_tokens` - Sanctum API tokens
- `roles` - Spatie roles
- `permissions` - Spatie permissions
- `model_has_roles` - User-role relationships
- `model_has_permissions` - User-permission relationships
- `role_has_permissions` - Role-permission relationships

