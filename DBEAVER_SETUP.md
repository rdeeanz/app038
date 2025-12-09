# Panduan Koneksi Database dengan DBeaver

Panduan lengkap untuk mengakses database PostgreSQL project app038 menggunakan DBeaver.

## Prasyarat

1. **DBeaver** sudah terinstall di komputer Anda
   - Download: https://dbeaver.io/download/
   - Versi Community Edition (gratis) sudah cukup

2. **Docker Compose** sudah berjalan
   ```bash
   docker compose up -d postgres
   ```

3. **PostgreSQL Driver** di DBeaver (biasanya sudah terinstall otomatis)

## Informasi Koneksi Database

Berdasarkan konfigurasi project app038:

### Koneksi Utama (Primary)

| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| **Host** | `localhost` | Atau `127.0.0.1` |
| **Port** | `5432` | Atau dari env `DB_PORT` |
| **Database** | `laravel` | Atau dari env `DB_DATABASE` |
| **Username** | `postgres` | Atau dari env `DB_USERNAME` |
| **Password** | `postgres` | Atau dari env `DB_PASSWORD` |

### Koneksi Fallback (Optional)

| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| **Host** | `localhost` | Atau `127.0.0.1` |
| **Port** | `5433` | Atau dari env `DB_FALLBACK_PORT` |
| **Database** | `laravel` | Atau dari env `DB_FALLBACK_DATABASE` |
| **Username** | `postgres` | Atau dari env `DB_FALLBACK_USERNAME` |
| **Password** | `postgres` | Atau dari env `DB_FALLBACK_PASSWORD` |

> **Catatan**: Untuk mengetahui nilai aktual, cek file `.env` di root project.

## Langkah-langkah Setup DBeaver

### 1. Buka DBeaver

Jalankan aplikasi DBeaver di komputer Anda.

### 2. Buat Koneksi Baru

1. Klik **Database** → **New Database Connection** (atau tekan `Ctrl+Shift+N`)
2. Pilih **PostgreSQL** dari daftar database
3. Klik **Next**

### 3. Konfigurasi Koneksi

#### Tab "Main"

Isi informasi berikut:

```
Host:     localhost
Port:     5432
Database: laravel
Username: postgres
Password: postgres
```

**Atau jika menggunakan nilai dari .env:**

```
Host:     localhost
Port:     [nilai dari DB_PORT, default: 5432]
Database: [nilai dari DB_DATABASE, default: laravel]
Username: [nilai dari DB_USERNAME, default: postgres]
Password: [nilai dari DB_PASSWORD, default: postgres]
```

#### Tab "Driver Properties" (Optional)

Anda bisa menambahkan properties tambahan jika diperlukan:

- `connectTimeout`: `5` (dalam detik)
- `loginTimeout`: `5` (dalam detik)

#### Tab "SSH" (Tidak diperlukan untuk local)

Skip tab ini karena kita menggunakan Docker lokal.

### 4. Test Koneksi

1. Klik tombol **Test Connection** di bagian bawah
2. Jika driver belum terinstall, DBeaver akan meminta download driver
   - Klik **Download** dan tunggu proses download selesai
3. Jika berhasil, akan muncul pesan **Connected**
4. Klik **Finish** untuk menyimpan koneksi

### 5. Simpan Koneksi

1. Beri nama koneksi, misalnya: `app038 - PostgreSQL`
2. Klik **Finish**

## Verifikasi Koneksi

### 1. Buka Koneksi

1. Di panel **Database Navigator**, expand koneksi yang baru dibuat
2. Expand **Databases** → **laravel** (atau nama database Anda)
3. Expand **Schemas** → **public**
4. Expand **Tables**

### ⚠️ Jika Tables Tidak Muncul

Jika tables tidak muncul setelah koneksi berhasil, lakukan langkah berikut:

#### A. Refresh Koneksi

1. Klik kanan pada koneksi → **Refresh** (atau tekan `F5`)
2. Atau klik kanan pada **Schemas** → **public** → **Refresh**

#### B. Pastikan Database yang Benar

1. Klik kanan koneksi → **Edit Connection**
2. Tab **Main** → Pastikan **Database** adalah `laravel` (atau sesuai `.env`)
3. Klik **Test Connection** → **OK**

#### C. Pastikan Migration Sudah Dijalankan

Jika tables masih tidak muncul, pastikan migration sudah dijalankan:

```bash
# Cek status migration
php artisan migrate:status

# Jika belum, jalankan migration
php artisan migrate

# Atau fresh migration dengan seed
php artisan migrate:fresh --seed
```

#### D. Pastikan Konfigurasi Database Benar

Pastikan file `.env` menggunakan PostgreSQL, bukan SQLite:

```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

**Catatan**: Jika aplikasi sebelumnya menggunakan SQLite, tables akan ada di file `database/database.sqlite`, bukan di PostgreSQL. Anda perlu:
1. Ubah `DB_CONNECTION=sqlite` menjadi `DB_CONNECTION=pgsql` di `.env`
2. Jalankan `php artisan migrate:fresh --seed` untuk membuat tables di PostgreSQL

### 2. Cek Tables

Anda seharusnya melihat tables berikut:

- ✅ `users` - Data pengguna
- ✅ `settings` - Pengaturan website
- ✅ `roles` - Role/peran (Spatie Permission)
- ✅ `permissions` - Permission/izin (Spatie Permission)
- ✅ `model_has_roles` - Relasi user-role
- ✅ `model_has_permissions` - Relasi user-permission
- ✅ `role_has_permissions` - Relasi role-permission
- ✅ `orders` - Data pesanan
- ✅ `products` - Data produk
- ✅ `erp_syncs` - Data sinkronisasi ERP
- ✅ `sessions` - Session data
- ✅ `cache` - Cache data
- ✅ `cache_locks` - Cache locks
- ✅ `jobs` - Queue jobs
- ✅ `failed_jobs` - Failed jobs
- ✅ `migrations` - Migration history

### 3. Test Query

Klik kanan pada koneksi → **SQL Editor** → **New SQL Script**

Jalankan query berikut:

```sql
-- Cek jumlah users
SELECT COUNT(*) as total_users FROM users;

-- Cek users dengan roles
SELECT 
    u.id,
    u.name,
    u.email,
    STRING_AGG(r.name, ', ') as roles
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
GROUP BY u.id, u.name, u.email;

-- Cek settings
SELECT * FROM settings;

-- Cek roles dan permissions
SELECT 
    r.name as role_name,
    STRING_AGG(p.name, ', ') as permissions
FROM roles r
LEFT JOIN role_has_permissions rhp ON r.id = rhp.role_id
LEFT JOIN permissions p ON rhp.permission_id = p.id
GROUP BY r.id, r.name;
```

## Troubleshooting

### Error: Connection Refused

**Penyebab**: Docker container tidak berjalan atau port tidak terbuka.

**Solusi**:
```bash
# Cek status container
docker ps | grep postgres

# Jika tidak berjalan, start container
docker compose up -d postgres

# Cek port
docker port app038_postgres
```

### Error: Authentication Failed

**Penyebab**: Username atau password salah.

**Solusi**:
1. Cek file `.env` di root project
2. Pastikan `DB_USERNAME` dan `DB_PASSWORD` sesuai
3. Atau gunakan default: `postgres` / `postgres`

### Error: Database Does Not Exist

**Penyebab**: Database belum dibuat atau nama database salah.

**Solusi**:
```bash
# Cek database yang ada
docker exec -it app038_postgres psql -U postgres -l

# Buat database jika belum ada
docker exec -it app038_postgres psql -U postgres -c "CREATE DATABASE laravel;"

# Atau jalankan migration
php artisan migrate
```

### Tables Tidak Muncul di DBeaver

**Penyebab**: 
- Aplikasi menggunakan SQLite, bukan PostgreSQL
- Migration belum dijalankan ke PostgreSQL
- Koneksi perlu di-refresh

**Solusi**:

1. **Cek konfigurasi database di `.env`**:
   ```bash
   cat .env | grep "^DB_"
   ```

2. **Jika menggunakan SQLite**, ubah ke PostgreSQL:
   ```bash
   # Edit .env
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=laravel
   DB_USERNAME=postgres
   DB_PASSWORD=postgres
   ```

3. **Jalankan migration ke PostgreSQL**:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Refresh koneksi di DBeaver**:
   - Klik kanan koneksi → **Refresh** (F5)
   - Atau tutup dan buka ulang koneksi

5. **Verifikasi tables ada di database**:
   ```bash
   docker exec -it app038_postgres psql -U postgres -d laravel -c "\dt"
   ```

### Error: Driver Not Found

**Penyebab**: PostgreSQL driver belum terinstall di DBeaver.

**Solusi**:
1. Saat test connection, DBeaver akan menawarkan download driver
2. Klik **Download** dan tunggu proses selesai
3. Atau manual: **Help** → **Install New Software** → Pilih PostgreSQL driver

### Error: Connection Timeout

**Penyebab**: Host atau port salah, atau firewall memblokir.

**Solusi**:
1. Pastikan menggunakan `localhost` atau `127.0.0.1`
2. Pastikan port `5432` benar (cek dengan `docker port app038_postgres`)
3. Pastikan Docker container berjalan

## Tips & Best Practices

### 1. Simpan Password Securely

- DBeaver akan menanyakan apakah ingin menyimpan password
- Pilih **Save password** untuk kemudahan akses
- Password disimpan di DBeaver keystore (aman)

### 2. Gunakan Connection Color

- Klik kanan koneksi → **Edit Connection**
- Tab **Appearance** → Pilih warna untuk membedakan koneksi

### 3. Export/Import Connection

- **Export**: Klik kanan koneksi → **Export Connection**
- **Import**: **Database** → **Import Connection**

### 4. Backup Database

Gunakan DBeaver untuk backup:

1. Klik kanan database → **Tools** → **Backup Database**
2. Pilih lokasi file backup
3. Klik **Start**

### 5. Monitor Queries

- **View** → **SQL History** untuk melihat query history
- **View** → **Database Navigator** untuk navigasi database

## Query Berguna

### Cek Data Users

```sql
SELECT 
    u.id,
    u.name,
    u.email,
    u.created_at,
    STRING_AGG(r.name, ', ') as roles
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON mhr.role_id = r.id
GROUP BY u.id, u.name, u.email, u.created_at
ORDER BY u.created_at DESC;
```

### Cek Settings

```sql
SELECT 
    key,
    value,
    type,
    "group",
    description,
    updated_at
FROM settings
ORDER BY "group", key;
```

### Cek Roles dan Permissions

```sql
SELECT 
    r.id,
    r.name as role_name,
    COUNT(DISTINCT rhp.permission_id) as permission_count,
    STRING_AGG(p.name, ', ' ORDER BY p.name) as permissions
FROM roles r
LEFT JOIN role_has_permissions rhp ON r.id = rhp.role_id
LEFT JOIN permissions p ON rhp.permission_id = p.id
GROUP BY r.id, r.name
ORDER BY r.name;
```

### Cek User dengan Role

```sql
SELECT 
    u.id,
    u.name,
    u.email,
    r.name as role_name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON mhr.role_id = r.id
ORDER BY u.name, r.name;
```

## Koneksi Multiple Database

Jika Anda ingin mengakses database fallback juga:

1. Buat koneksi baru dengan nama berbeda (misalnya: `app038 - PostgreSQL Fallback`)
2. Gunakan port `5433` (atau dari `DB_FALLBACK_PORT`)
3. Konfigurasi lainnya sama dengan koneksi utama

## Referensi

- **DBeaver Documentation**: https://dbeaver.com/docs/
- **PostgreSQL Documentation**: https://www.postgresql.org/docs/
- **Project Documentation**: Lihat `DATABASE_SETUP.md` untuk informasi lebih lanjut tentang struktur database

## Catatan Penting

1. **Jangan commit password** ke version control
2. **Backup database** secara berkala
3. **Gunakan read-only connection** untuk production (jika memungkinkan)
4. **Hati-hati dengan DROP/TRUNCATE** - pastikan Anda tahu apa yang Anda lakukan
5. **Test query** di development sebelum menjalankan di production

