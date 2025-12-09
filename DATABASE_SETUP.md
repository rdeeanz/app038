# Database Setup Guide

Panduan ini menjelaskan cara menyiapkan database dan memastikan semua data tersimpan dengan benar.

## Struktur Database

Aplikasi ini menggunakan database berikut:

### Tables

1. **users** - Menyimpan data pengguna
   - id, name, email, password, email_verified_at, remember_token, timestamps

2. **settings** - Menyimpan pengaturan website
   - id, key (unique), value, type, description, group, timestamps

3. **roles** - Menyimpan role/peran pengguna (Spatie Permission)
   - id, name, guard_name, timestamps

4. **permissions** - Menyimpan permission/izin (Spatie Permission)
   - id, name, guard_name, timestamps

5. **model_has_roles** - Relasi user dengan role
   - role_id, model_type, model_id

6. **model_has_permissions** - Relasi user dengan permission
   - permission_id, model_type, model_id

7. **role_has_permissions** - Relasi role dengan permission
   - permission_id, role_id

8. **orders** - Menyimpan data pesanan
9. **products** - Menyimpan data produk
10. **erp_syncs** - Menyimpan data sinkronisasi ERP

## Setup Database

### 1. Jalankan Migration

```bash
# Jalankan semua migration
php artisan migrate

# Atau dengan force (untuk production)
php artisan migrate --force
```

### 2. Jalankan Seeder

```bash
# Jalankan semua seeder
php artisan db:seed

# Atau jalankan seeder spesifik
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=SettingsSeeder
```

### 3. Fresh Migration (Development Only)

```bash
# Hapus semua table dan buat ulang + seed
php artisan migrate:fresh --seed
```

## Data yang Tersimpan

### Users
- **Super Admin** (default)
  - Email: `admin@example.com`
  - Password: `password`
  - Role: Super Admin
  - ⚠️ **PENTING**: Ganti password setelah login pertama!

### Roles
- **Super Admin** - Memiliki semua permission
- **User** - Permission: view users
- **Editor** - Permissions: view users, create users, edit users

### Permissions
- User Management: view users, create users, edit users, delete users
- Role Management: view roles, create roles, edit roles, delete roles
- ERP Integration: erp-integration.view, erp-integration.sync, erp-integration.manage
- Sales: sales.view, sales.create, sales.update, sales.manage
- Inventory: inventory.view, inventory.create, inventory.update, inventory.manage
- Monitoring: monitoring.view
- Website: website.settings.view, website.settings.edit, website.settings.manage, website.configuration.view, website.configuration.edit

### Settings (Website)
- `app_name` - Nama aplikasi
- `timezone` - Timezone aplikasi
- `locale` - Bahasa/locale aplikasi

## Verifikasi Data

### Cek Users
```bash
php artisan tinker
>>> User::with('roles')->get();
>>> User::count();
```

### Cek Roles & Permissions
```bash
php artisan tinker
>>> Role::with('permissions')->get();
>>> Permission::count();
```

### Cek Settings
```bash
php artisan tinker
>>> App\Models\Setting::all();
>>> App\Models\Setting::get('app_name');
```

## Menyimpan Data Baru

### Menyimpan User Baru
- Melalui UI: Settings → User Settings → Create User
- Melalui Tinker:
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
]);
$user->assignRole('User');
```

### Menyimpan Settings Baru
- Melalui UI: Settings → Website Settings → Save Settings
- Melalui Tinker:
```php
App\Models\Setting::set('app_name', 'My App', 'string', 'website', 'Application name');
```

### Menyimpan Role Baru
```php
$role = Role::create(['name' => 'Manager']);
$role->givePermissionTo(['sales.view', 'sales.create']);
```

## Backup Database

### PostgreSQL
```bash
# Backup
pg_dump -U postgres -d app038 > backup.sql

# Restore
psql -U postgres -d app038 < backup.sql
```

### MySQL
```bash
# Backup
mysqldump -u root -p app038 > backup.sql

# Restore
mysql -u root -p app038 < backup.sql
```

## Troubleshooting

### Migration Error
```bash
# Reset migration
php artisan migrate:reset

# Refresh migration
php artisan migrate:refresh --seed
```

### Permission Cache
```bash
# Clear permission cache
php artisan permission:cache-reset
```

### Database Connection
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

## Catatan Penting

1. **Password Hashing**: Semua password di-hash otomatis menggunakan Laravel's Hash facade
2. **Role Assignment**: Gunakan `assignRole()` untuk assign role ke user
3. **Permission Check**: Gunakan `can()` atau `hasPermissionTo()` untuk check permission
4. **Settings**: Semua settings disimpan di database, bukan di config file
5. **Timestamps**: Semua table memiliki `created_at` dan `updated_at`

