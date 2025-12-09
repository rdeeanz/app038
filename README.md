# Laravel 11 LTS with Inertia.js, Svelte, Breeze, Sanctum, and Spatie Permission

A modern Laravel 11 LTS application setup with Inertia.js, Svelte, Vite, TailwindCSS, Laravel Breeze authentication, Laravel Sanctum for API authentication, and Spatie Laravel Permission for role-based access control.

## 🚀 Features

- **Laravel 11 LTS** - Latest long-term support version
- **Inertia.js** - Modern monolith approach with Svelte
- **Svelte** - Fast, lightweight frontend framework
- **Vite** - Next-generation frontend tooling
- **TailwindCSS** - Utility-first CSS framework
- **Laravel Breeze** - Simple authentication scaffolding
- **Laravel Sanctum** - API token authentication
- **Spatie Laravel Permission** - Role and permission management

## 📋 Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x and npm
- MySQL/PostgreSQL/SQLite

## 🛠️ Installation

See [SETUP.md](./SETUP.md) for detailed installation instructions or [QUICK_START.md](./QUICK_START.md) for a quick reference.

### Quick Install

```bash
# Install dependencies
composer install
npm install

# Install Breeze with Svelte
php artisan breeze:install svelte

# Publish configurations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder

# Start development servers
php artisan serve
npm run dev
```

## 📁 Project Structure

See [DIRECTORY_STRUCTURE.md](./DIRECTORY_STRUCTURE.md) for a complete overview of the project structure.

## 🔐 Authentication & Authorization

### Authentication
- **Laravel Breeze** provides authentication scaffolding
- **Laravel Sanctum** handles API token authentication
- Session-based authentication for web routes

### Authorization
- **Spatie Laravel Permission** manages roles and permissions
- Super Admin role automatically granted all permissions
- Middleware for role and permission checks

## 📚 Documentation

- [SETUP.md](./SETUP.md) - Complete setup guide
- [QUICK_START.md](./QUICK_START.md) - Quick reference guide
- [DIRECTORY_STRUCTURE.md](./DIRECTORY_STRUCTURE.md) - Project structure

## 🧪 Testing

```bash
php artisan test
```

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📖 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)
- [Svelte Documentation](https://svelte.dev)
- [Laravel Breeze Documentation](https://laravel.com/docs/breeze)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)