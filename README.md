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
- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) - **Complete deployment guide (Dokploy, VPS Hostinger, Kubernetes, Free Tier, dll)**
- [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) - Installation guide for dependencies
- [CONFIGURATION.md](./CONFIGURATION.md) - Configuration guide

## 🚀 Quick Deployment

**Recommended untuk VPS Hostinger:** Gunakan [Dokploy](https://dokploy.com) untuk deployment dengan web UI management, auto SSL, dan Git integration. 

**Quick Start (VPS Hostinger):**
1. SSH ke VPS: `ssh root@168.231.118.3` (atau IP VPS Anda)
2. Install Dokploy (lihat [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) Step 1)
3. Setup project di Dokploy UI: `http://168.231.118.3:3000`
4. Deploy aplikasi menggunakan `docker-compose.dokploy.yml`

**Panduan Lengkap:** Lihat [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) section **Opsi 0A: VPS Hostinger dengan Dokploy** untuk step-by-step guide lengkap.

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