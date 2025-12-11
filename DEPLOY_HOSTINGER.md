# 🚀 Deployment Manual ke VPS Hostinger (Tanpa Dokploy)

Panduan lengkap untuk mendeploy aplikasi App038 ke VPS Hostinger secara manual menggunakan Docker Compose, Nginx reverse proxy, dan Let's Encrypt SSL.

> **📌 Informasi VPS Anda:** VPS Hostinger (IP: `168.231.118.3`, Hostname: `srv1162366.hstgr.cloud`) sudah terverifikasi. Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk, Ubuntu 24.04 LTS - **Sangat cukup untuk production!**

---

## 🔴 STATUS DEPLOYMENT SAAT INI (Update: 10 December 2025, 18:00 UTC)

### 📊 Status VPS Hostinger:
| Item | Value |
|------|-------|
| IP Address | `168.231.118.3` |
| Hostname | `srv1162366.hstgr.cloud` |
| Domain | `vibeapps.cloud` |
| OS | Ubuntu 24.04 LTS |
| Resources | 2 CPUs, 8GB RAM, 100GB Disk |
| State | ✅ Running |

### 📦 Status Container:
| Container | Status | Port | Keterangan |
|-----------|--------|------|------------|
| app038_laravel | ✅ Up (healthy) | 8080:80 | Backend Laravel (PHP-FPM + Nginx) |
| app038_postgres | ✅ Up (healthy) | 5432 | PostgreSQL Database |
| app038_redis | ✅ Up (healthy) | 6379 | Redis Cache |
| app038_rabbitmq | ✅ Up (healthy) | 5672 | RabbitMQ Queue |

### 📋 Technical Specifications:

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | 8.2+ | Required: `^8.2` (from composer.json), Docker: `php:8.2-fpm-alpine` |
| **Laravel** | 11.0 LTS | Framework version: `^11.0` |
| **Node.js** | 20.x | Recommended for Vite 5.x |
| **npm** | 9.x+ | Package manager |
| **PostgreSQL** | 15 | Database (via Docker: `postgres:15-alpine`) |
| **Redis** | 7 | Cache & Session (via Docker: `redis:7-alpine`) |
| **RabbitMQ** | 3 | Message Queue (via Docker: `rabbitmq:3-management-alpine`) |
| **Docker** | 20.10+ | Container runtime |
| **Docker Compose** | 2.0+ | Container orchestration |
| **Nginx** | Latest | Web server (host as reverse proxy) |
| **Ubuntu** | 24.04 LTS | Operating system |

### 📋 PHP Extensions Required:

Dari `composer.json` dan `docker/php/Dockerfile`, aplikasi memerlukan PHP extensions berikut:

- `pdo` - Database abstraction
- `pdo_pgsql` - PostgreSQL driver
- `pgsql` - PostgreSQL extension
- `zip` - Archive handling
- `mbstring` - Multibyte string handling
- `exif` - Image metadata
- `pcntl` - Process control
- `bcmath` - Arbitrary precision mathematics
- `intl` - Internationalization
- `opcache` - OPcache for performance

**Note:** Semua extensions ini sudah terinstall di Docker container (`docker/php/Dockerfile`).

### 🔍 Arsitektur Aplikasi (PENTING!)

**Aplikasi ini menggunakan Laravel + Inertia.js + Svelte**, bukan standalone Svelte SPA.

Artinya:
- **Laravel** = serve semua (HTML + API + Assets)
- **Svelte** = hanya component UI yang di-render oleh Laravel via Inertia.js
- **Vite build** = menghasilkan assets ke `public/build/` (bukan standalone SPA)
- **Svelte container TIDAK diperlukan** untuk production

```
Arsitektur Benar:
Internet → Nginx (Host:80) → Laravel Container (8080:80) → PostgreSQL/Redis/RabbitMQ
```

### ✅ Masalah yang Sudah Diperbaiki:

1. ✅ **`.dockerignore` exclude `public/build`** - **SUDAH DIPERBAIKI** - Assets Vite sekarang ter-include dalam Docker build
2. ✅ **Inertia SSR enabled** - **SUDAH DIPERBAIKI** - `config/inertia.php` sudah ditambahkan dengan `ssr.enabled = false` by default
3. ✅ **Database Connection** - Sudah terhubung (`DB OK`)
4. ✅ **Migrations** - Sudah dijalankan
5. ✅ **Vite Build** - Sudah berhasil di host (`public/build/` ada)
6. ✅ **`.env.example`** - **SUDAH DIBUAT** - Template environment variables lengkap

### 🚨 LANGKAH SELANJUTNYA (WAJIB DILAKUKAN):

**Status saat ini:** Semua fix sudah di-commit dan push ke repository. **Langkah selanjutnya adalah setup environment variables dan rebuild container di VPS dengan fix yang sudah ada.**

**📋 Quick Start untuk Ubuntu 24.04:**

```bash
# ========================================
# COMPLETE DEPLOYMENT SCRIPT - Ubuntu 24.04
# ========================================
cd /var/www/app038

# Step 1: Pull latest changes
git pull origin main

# Step 2: Setup .env file
if [ -f .env.example ]; then
    cp .env.example .env
else
    echo "⚠️ .env.example not found, creating .env from template"
    # Create .env (see Step 4.1 for full template)
fi

# Step 3: Generate secure passwords
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
RABBITMQ_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Update .env
sed -i "s/DB_PASSWORD=$/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i "s/REDIS_PASSWORD=$/REDIS_PASSWORD=$REDIS_PASSWORD/" .env
sed -i "s/RABBITMQ_PASSWORD=$/RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD/" .env

# Step 4: Generate APP_KEY
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env

# Step 5: Update APP_URL (ganti dengan domain atau IP Anda)
read -p "Enter domain (or press Enter for IP): " DOMAIN
if [ -z "$DOMAIN" ]; then
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=http://168.231.118.3|" .env
else
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=https://$DOMAIN|" .env
fi

# Step 6: Install Node.js 20.x (for Vite build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Step 7: Build Vite assets
npm install
npm run build

# Step 8: Stop and rebuild containers
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml build --no-cache laravel
docker-compose -f docker-compose.prod.yml up -d

# Step 9: Wait for containers
echo "⏳ Waiting 30 seconds for containers to start..."
sleep 30

# Step 10: Setup Laravel
docker exec app038_laravel php artisan key:generate --force
docker exec app038_laravel php artisan migrate --force
docker exec app038_laravel php artisan config:clear
docker exec app038_laravel php artisan cache:clear
docker exec app038_laravel php artisan config:cache
docker exec app038_laravel php artisan route:cache

# Step 11: Verify
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038
curl -s http://localhost:8080/health
echo "✅ Test: http://168.231.118.3"
```

**Jalankan perintah berikut di VPS Hostinger:**

**Jalankan perintah berikut di VPS Hostinger:**

```bash
# ========================================
# COMPLETE FIX SCRIPT
# ========================================
cd /var/www/app038

# Step 1: Pull latest changes (includes .dockerignore fix & config/inertia.php)
git pull origin main

# Step 2: Build Vite assets on host
npm install
npm run build

# Step 3: Stop containers
docker-compose -f docker-compose.prod.yml down

# Step 4: Rebuild Laravel container (with Vite assets included)
docker-compose -f docker-compose.prod.yml build --no-cache laravel

# Step 5: Start containers
docker-compose -f docker-compose.prod.yml up -d

# Step 6: Wait for containers
echo "⏳ Waiting 30 seconds..."
sleep 30

# Step 7: Check container status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038

# Step 8: Verify Vite assets in container
docker exec app038_laravel ls -la /app/public/build/

# Step 9: Clear and optimize cache
docker exec app038_laravel php artisan config:clear
docker exec app038_laravel php artisan cache:clear
docker exec app038_laravel php artisan view:clear
docker exec app038_laravel php artisan config:cache
docker exec app038_laravel php artisan route:cache

# Step 10: Test
curl -s http://localhost:8080/health
curl -I http://localhost:8080/
curl -I http://localhost/

echo "✅ Test di browser: http://168.231.118.3"
```

### 📋 Quick Fix Script (Copy-Paste Semua):

```bash
cd /var/www/app038 && \
git pull origin main && \
npm install && \
npm run build && \
docker-compose -f docker-compose.prod.yml down && \
docker-compose -f docker-compose.prod.yml build --no-cache laravel && \
docker-compose -f docker-compose.prod.yml up -d && \
echo "⏳ Waiting 30 seconds..." && \
sleep 30 && \
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038 && \
docker exec app038_laravel ls -la /app/public/build/ && \
docker exec app038_laravel php artisan config:clear && \
docker exec app038_laravel php artisan cache:clear && \
docker exec app038_laravel php artisan config:cache && \
docker exec app038_laravel php artisan route:cache && \
curl -s http://localhost:8080/health && \
echo "" && \
curl -I http://localhost:8080/ && \
echo "✅ Test: http://168.231.118.3"
```

### ✅ Setelah Fix Berhasil:

Website akan bisa diakses di:
- **Via IP:** http://168.231.118.3
- **Via Domain:** http://vibeapps.cloud (jika DNS sudah pointing)

### 🔐 Setup SSL (Setelah Website Berjalan):

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL Certificate
sudo certbot --nginx -d vibeapps.cloud -d www.vibeapps.cloud

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Prasyarat](#prasyarat)
3. [Langkah-langkah Deployment](#langkah-langkah-deployment)
4. [Post-Deployment](#post-deployment)
5. [Monitoring & Maintenance](#monitoring--maintenance)
6. [Troubleshooting](#troubleshooting)
7. [Checklist Deployment](#checklist-deployment)

---

## Overview

### Arsitektur Deployment

> **⚠️ PENTING:** Aplikasi ini menggunakan **Laravel + Inertia.js + Svelte** (Monolith), 
> bukan standalone Svelte SPA. Laravel serve semua: HTML, API, dan Assets.

```
┌─────────────────────────────────────────────────────────────┐
│                    Internet / Users                          │
│              http://168.231.118.3 / vibeapps.cloud           │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Nginx Reverse Proxy (Host)                      │
│              Port 80 → proxy_pass localhost:8080             │
│              SSL via Let's Encrypt (Optional)                │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel Container (app038_laravel)              │
│              PHP-FPM + Nginx (Internal)                      │
│              Port: 8080:80                                   │
│                                                              │
│              Serves:                                         │
│              • HTML (via Inertia.js + Svelte components)     │
│              • API Endpoints (/api/*)                        │
│              • Static Assets (/build/*)                      │
└───────────────────────┬─────────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  PostgreSQL  │ │    Redis     │ │  RabbitMQ    │
│  (Database)  │ │   (Cache)    │ │   (Queue)    │
│  Port: 5432  │ │  Port: 6379  │ │  Port: 5672  │
└──────────────┘ └──────────────┘ └──────────────┘
```

### Teknologi Stack

- **Backend:** Laravel 11 LTS (PHP 8.2+)
- **Frontend:** Svelte 4 + Inertia.js (rendered by Laravel)
- **Build Tool:** Vite (assets di `public/build/`)
- **Database:** PostgreSQL 15
- **Cache/Session:** Redis 7
- **Queue:** RabbitMQ 3
- **Web Server:** Nginx (host as reverse proxy, container for PHP-FPM)
- **SSL:** Let's Encrypt (Certbot)
- **Container:** Docker & Docker Compose

---

## Prasyarat

### ✅ Informasi VPS Hostinger Anda (Sudah Terverifikasi)

- **✅ Status VPS:** Running (Active)
- **✅ IP Address:** `168.231.118.3`
- **✅ Hostname:** `srv1162366.hstgr.cloud`
- **✅ Plan:** KVM 2
- **✅ CPUs:** 2 cores
- **✅ RAM:** 8GB (8192 MB) - **Sangat cukup untuk production!**
- **✅ Disk:** 100GB (102400 MB) - **Cukup untuk aplikasi + data**
- **✅ OS:** Ubuntu 24.04 LTS
- **✅ IPv4:** 168.231.118.3
- **✅ IPv6:** 2a02:4780:59:6452::1
- **✅ State:** unlocked (siap untuk deployment)

### Requirements

1. **VPS Hostinger aktif** dengan:
   - Ubuntu 22.04 LTS atau 24.04 LTS ✅ (Anda punya Ubuntu 24.04)
   - Minimum: 2GB RAM, 2 CPU cores, 40GB storage ✅ (Anda punya lebih)
   - Root access atau sudo access
   - IP address publik ✅ (168.231.118.3)

2. **Domain name** (opsional tapi recommended):
   - Domain sudah terdaftar
   - Akses ke DNS management
   - A record bisa di-set ke IP VPS: `168.231.118.3`

3. **Tools yang akan diinstall:**
   - Docker & Docker Compose
   - Nginx (reverse proxy)
   - Certbot (Let's Encrypt SSL)
   - Git

---

## Langkah-langkah Deployment

### Step 0: Verifikasi & Persiapan VPS

**0.1. Connect ke VPS via SSH**

```bash
# Fix SSH key issue jika perlu
ssh-keygen -R 168.231.118.3

# Connect ke VPS
ssh root@168.231.118.3

# Verifikasi system
whoami        # Harus return: root
hostname      # Harus return: srv1162366.hstgr.cloud
uname -a      # Harus show: Ubuntu 24.04
free -h       # Harus show: ~8GB RAM
df -h         # Harus show: ~100GB disk
```

**0.2. Update System**

```bash
# Update package list
sudo apt update

# Upgrade system (opsional, bisa skip jika tidak perlu)
sudo apt upgrade -y

# Install basic tools
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    htop \
    nano
```

**0.3. Verifikasi Port yang Tersedia**

```bash
# Check port yang sedang digunakan
sudo netstat -tulpn | grep -E ':(80|443|22)'
# Atau menggunakan ss
sudo ss -tulpn | grep -E ':(80|443|22)'

# Pastikan port 80 dan 443 tidak digunakan
# Jika ada service yang menggunakan, stop terlebih dahulu
```

---

### Step 1: Install Docker & Docker Compose

**1.1. Install Docker**

```bash
# Install Docker menggunakan official script
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add current user to docker group (jika tidak menggunakan root)
# sudo usermod -aG docker $USER

# Start Docker service
sudo systemctl start docker
sudo systemctl enable docker

# Verify installation
docker --version
# Expected: Docker version 24.x or higher
```

**1.2. Install Docker Compose**

```bash
# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker-compose --version
# Expected: Docker Compose version v2.x or higher
```

**1.3. Test Docker Installation**

```bash
# Test Docker
sudo docker run hello-world

# Check Docker service
sudo systemctl status docker
```

---

### Step 2: Setup Firewall (UFW)

**2.1. Install & Configure UFW**

```bash
# Install UFW jika belum ada
sudo apt install ufw -y

# Allow SSH (PENTING! jangan skip ini)
sudo ufw allow 22/tcp

# Allow HTTP dan HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw --force enable

# Check status
sudo ufw status verbose
```

**Expected Output:**
```
Status: active
To                         Action      From
--                         ------      ----
22/tcp                     ALLOW       Anywhere
80/tcp                     ALLOW       Anywhere
443/tcp                    ALLOW       Anywhere
```

---

### Step 3: Clone Repository

**3.1. Clone Project**

**PENTING:** GitHub tidak lagi mendukung password authentication. Gunakan salah satu metode berikut:

**Opsi A: Menggunakan Personal Access Token (PAT) - Recommended untuk HTTPS**

```bash
# Buat directory untuk aplikasi
sudo mkdir -p /var/www
cd /var/www

# Clone repository dengan Personal Access Token
# Format: git clone https://TOKEN@github.com/USERNAME/REPO.git
git clone https://YOUR_PERSONAL_ACCESS_TOKEN@github.com/rdeeanz/app038.git
cd app038

# Atau clone dulu, lalu set credential helper
git clone https://github.com/rdeeanz/app038.git
cd app038
# Saat diminta username: masukkan username GitHub Anda
# Saat diminta password: masukkan Personal Access Token (bukan password)
```

**Cara membuat Personal Access Token (PAT):**
1. Login ke GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Beri nama token (contoh: "VPS Hostinger Deployment")
4. Pilih scopes: minimal `repo` (untuk private repo) atau `public_repo` (untuk public repo)
5. Generate token
6. **Copy token segera** (hanya ditampilkan sekali)
7. Gunakan token sebagai password saat git clone atau git pull

**Opsi B: Menggunakan SSH Key (Lebih Aman & Recommended untuk Production)**

```bash
# Generate SSH key di VPS (jika belum ada)
ssh-keygen -t ed25519 -C "vps-hostinger-deployment"
# Tekan Enter untuk default location (/root/.ssh/id_ed25519)
# Tekan Enter untuk no passphrase (atau set passphrase untuk lebih aman)

# Tampilkan public key
cat ~/.ssh/id_ed25519.pub
# Copy output (mulai dari ssh-ed25519 sampai email)

# Add SSH key ke GitHub:
# 1. Login ke GitHub → Settings → SSH and GPG keys
# 2. Click "New SSH key"
# 3. Title: "VPS Hostinger"
# 4. Key: paste public key yang sudah di-copy
# 5. Click "Add SSH key"

# Test SSH connection
ssh -T git@github.com
# Expected: "Hi rdeeanz! You've successfully authenticated..."

# Clone repository menggunakan SSH
cd /var/www
git clone git@github.com:rdeeanz/app038.git
cd app038
```

**Opsi C: Clone Public Repository (Jika repository public)**

```bash
# Jika repository public, bisa clone langsung tanpa authentication
cd /var/www
git clone https://github.com/rdeeanz/app038.git
cd app038
```

**Troubleshooting Authentication:**

```bash
# Jika sudah pernah mencoba clone dan gagal, clear credential cache
git credential-cache exit

# Atau unset credential helper
git config --global --unset credential.helper

# Atau gunakan credential helper untuk menyimpan token
git config --global credential.helper store
# Lalu clone lagi, masukkan token saat diminta password
```

**3.2. Verify Repository**

```bash
# Check files
ls -la

# Verify docker-compose.prod.yml exists
ls -la docker-compose.prod.yml

# Verify Dockerfiles exist
ls -la docker/php/Dockerfile
ls -la docker/svelte/Dockerfile
```

---

### Step 4: Setup Environment Variables

**4.1. Create .env File**

```bash
# Check apakah .env.example ada
if [ -f .env.example ]; then
    echo "✅ .env.example found, copying to .env"
    cp .env.example .env
    echo "✅ .env file created from .env.example"
else
    echo "⚠️ .env.example not found, creating .env from template"
    # Buat .env file baru dengan template lengkap
    cat > .env << 'ENVEOF'
APP_NAME=App038
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration
QUEUE_CONNECTION=rabbitmq

# RabbitMQ Configuration
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=

# Inertia.js Configuration
INERTIA_SSR_ENABLED=false

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,yourdomain.com
ENVEOF
    echo "✅ .env file created from template"
fi

# Verify .env file created
if [ -f .env ]; then
    echo "✅ .env file exists"
    echo "📝 File location: $(pwd)/.env"
    echo "📝 Total lines: $(wc -l < .env)"
else
    echo "❌ .env file creation failed"
    exit 1
fi
```

**Catatan:** 
- File `.env.example` sudah tersedia di repository dengan template lengkap
- Copy ke `.env` dan sesuaikan values-nya dengan passwords yang akan di-generate di langkah berikutnya
- **JANGAN commit file `.env`** ke repository (sudah di `.gitignore`)

**4.2. Generate Secure Passwords**

```bash
# Generate secure passwords untuk database, Redis, dan RabbitMQ
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
RABBITMQ_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Update .env file dengan passwords
sed -i "s/DB_PASSWORD=$/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i "s/REDIS_PASSWORD=$/REDIS_PASSWORD=$REDIS_PASSWORD/" .env
sed -i "s/RABBITMQ_PASSWORD=$/RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD/" .env

# Save passwords securely
echo "DB_PASSWORD: $DB_PASSWORD" > /root/app038-passwords.txt
echo "REDIS_PASSWORD: $REDIS_PASSWORD" >> /root/app038-passwords.txt
echo "RABBITMQ_PASSWORD: $RABBITMQ_PASSWORD" >> /root/app038-passwords.txt
chmod 600 /root/app038-passwords.txt

echo "✅ Passwords generated and saved to /root/app038-passwords.txt"
```

**4.3. Update APP_URL**

```bash
# Update APP_URL dengan domain atau IP VPS
read -p "Enter your domain name (or press Enter to use IP): " DOMAIN_NAME

if [ -z "$DOMAIN_NAME" ]; then
    # Use IP address
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=http://168.231.118.3|" .env
    echo "✅ APP_URL updated to: http://168.231.118.3"
else
    # Use domain
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=https://$DOMAIN_NAME|" .env
    echo "✅ APP_URL updated to: https://$DOMAIN_NAME"
fi

# Verify
grep APP_URL .env
```

**4.4. Environment Variables Wajib untuk Production:**

**PENTING:** Pastikan semua variables berikut sudah di-set dengan benar:

```env
# Application Configuration
APP_NAME=App038
APP_ENV=production
APP_KEY=                    # Akan di-generate di Step 4.3
APP_DEBUG=false            # HARUS false untuk production
APP_URL=https://yourdomain.com  # Ganti dengan domain atau IP VPS

# Database Configuration (PostgreSQL)
# PENTING: DB_HOST harus "postgres" (service name di docker-compose), BUKAN "localhost"
DB_CONNECTION=pgsql
DB_HOST=postgres           # Service name di docker-compose.prod.yml
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=              # Akan di-generate di Step 4.2

# Redis Configuration
REDIS_HOST=redis          # Service name di docker-compose.prod.yml
REDIS_PORT=6379
REDIS_PASSWORD=           # Akan di-generate di Step 4.2
REDIS_DB=0

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration
QUEUE_CONNECTION=rabbitmq

# RabbitMQ Configuration
RABBITMQ_HOST=rabbitmq    # Service name di docker-compose.prod.yml
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=        # Akan di-generate di Step 4.2

# Inertia.js Configuration
INERTIA_SSR_ENABLED=false # HARUS false (SSR server tidak tersedia)

# Sanctum Configuration (untuk API authentication)
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com,168.231.118.3
```

**Catatan Penting:**
- **DB_HOST, REDIS_HOST, RABBITMQ_HOST** harus menggunakan **service name** dari `docker-compose.prod.yml` (bukan `localhost` atau IP)
- **APP_DEBUG** harus `false` untuk production
- **APP_KEY** akan di-generate otomatis di langkah berikutnya
- Semua passwords akan di-generate secara otomatis
- Simpan semua passwords dengan aman (password manager recommended)

**4.5. Generate APP_KEY**

**Method 1: Generate Manual (Paling Simple - Recommended)**

```bash
# Generate APP_KEY secara manual (tidak perlu Docker atau PHP di host)
APP_KEY_VALUE=$(openssl rand -base64 32)
APP_KEY="base64:${APP_KEY_VALUE}"

# Update .env file
sed -i "s/APP_KEY=$/APP_KEY=$APP_KEY/" .env

# Verify
grep APP_KEY .env

echo "✅ APP_KEY generated: $APP_KEY"
```

**Method 2: Generate dengan Docker (Setelah container running)**

```bash
# Generate APP_KEY setelah container Laravel running
docker exec app038_laravel php artisan key:generate --force

# Verify
docker exec app038_laravel php artisan tinker --execute="echo config('app.key');"
```

**Catatan:** Method 1 (Generate Manual) adalah yang paling simple dan tidak perlu build Docker image atau install PHP. Recommended untuk production deployment.

```bash
# Add PHP repository
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 dan extensions
sudo apt install -y php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate APP_KEY
php8.4 artisan key:generate --show
# Copy output ke APP_KEY di .env file
```

**Opsi C: Generate APP_KEY Manual (Paling Simple - Tidak perlu PHP/Composer)**

```bash
# Generate random key dengan openssl
APP_KEY_VALUE=$(openssl rand -base64 32)
echo "base64:${APP_KEY_VALUE}"

# Atau langsung update .env
sed -i "s/APP_KEY=/APP_KEY=base64:$(openssl rand -base64 32)/" .env

# Verify
grep APP_KEY .env
```

**Opsi D: Skip Platform Check (Temporary - Hanya untuk generate key)**

```bash
# Install PHP 8.3 (default di Ubuntu 24.04)
sudo apt install -y php-cli php-mbstring php-xml php-curl php-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate APP_KEY dengan skip platform check
composer install --ignore-platform-reqs
php artisan key:generate --show
```

**Rekomendasi:** Gunakan **Opsi C** (Generate Manual) karena paling simple dan tidak perlu install PHP/Composer di host. Aplikasi akan berjalan di Docker dengan PHP 8.2 yang sudah sesuai dengan requirement.

**Quick Command untuk Generate APP_KEY Manual:**

```bash
# Generate dan update .env langsung
cd /var/www/app038
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env
echo "APP_KEY generated: base64:${APP_KEY_VALUE}"
grep APP_KEY .env
```

**4.7. Verify .env File**

```bash
# Check .env file (hide sensitive data)
cat .env | grep -v "^#" | grep -v "^$" | sed 's/=.*/=***HIDDEN***/'

# Verify required variables are set (show variable names only)
echo "=== Required Variables Check ==="
grep -E "^APP_KEY=|^DB_PASSWORD=|^REDIS_PASSWORD=|^RABBITMQ_PASSWORD=" .env | cut -d'=' -f1

# PENTING: Verify service names (bukan localhost!)
echo ""
echo "=== Service Names Check ==="
grep -E "^DB_HOST=|^REDIS_HOST=|^RABBITMQ_HOST=" .env

# Expected output:
# DB_HOST=postgres          # Harus "postgres" bukan "localhost"
# REDIS_HOST=redis          # Harus "redis" bukan "localhost"
# RABBITMQ_HOST=rabbitmq    # Harus "rabbitmq" bukan "localhost"

# Jika salah, fix:
sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' .env
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env
sed -i 's/^RABBITMQ_HOST=.*/RABBITMQ_HOST=rabbitmq/' .env

# Final verification
echo ""
echo "=== Final Verification ==="
if grep -q "^DB_HOST=postgres$" .env && \
   grep -q "^REDIS_HOST=redis$" .env && \
   grep -q "^RABBITMQ_HOST=rabbitmq$" .env; then
    echo "✅ Service names are correct"
else
    echo "❌ Service names are incorrect - please fix"
fi
```

**⚠️ PENTING: Service Names di Docker Compose**

Di Docker Compose, services diakses menggunakan **service names** (nama service di docker-compose.yml), bukan `localhost`:

- ✅ `DB_HOST=postgres` (benar - service name)
- ❌ `DB_HOST=localhost` (salah - tidak akan connect)
- ❌ `DB_HOST=127.0.0.1` (salah - tidak akan connect)

- ✅ `REDIS_HOST=redis` (benar - service name)
- ❌ `REDIS_HOST=localhost` (salah)

- ✅ `RABBITMQ_HOST=rabbitmq` (benar - service name)
- ❌ `RABBITMQ_HOST=localhost` (salah)

---

### Step 5: Create Docker Network

**5.1. Create Network**

```bash
# Create Docker network untuk aplikasi
docker network create app038_network

# Verify network
docker network ls | grep app038
```

---

### Step 6: Build & Start Docker Services

**6.1. Build Docker Images**

**PENTING: Pastikan pull latest changes dari repository terlebih dahulu!**

```bash
# Step 1: Handle local changes (jika ada conflict)
cd /var/www/app038
git status

# Jika ada local changes di Dockerfile, discard (versi di repo sudah lebih baik):
git checkout -- docker/php/Dockerfile

# Step 2: Pull latest changes dari repository (CRITICAL!)
git pull origin main

# Step 3: Verify Dockerfile sudah terupdate
grep -A 10 "Generate optimized autoloader" docker/php/Dockerfile
# Should show multiple fallback commands with --ignore-platform-reqs

# Step 4: Build semua images
docker-compose -f docker-compose.prod.yml build

# Atau build specific service
docker-compose -f docker-compose.prod.yml build laravel

# Check images
docker images | grep app038
```

**Catatan:** Jika build error dengan `composer dump-autoload`, pastikan sudah pull latest changes. Dockerfile sudah diupdate dengan multiple fallback approaches untuk handle berbagai error scenarios.

**Troubleshooting Build Errors:**

**Catatan:** 
- **Svelte container sudah dihapus** dari `docker-compose.prod.yml` karena aplikasi menggunakan Laravel + Inertia.js + Svelte (monolith)
- Laravel container sudah expose port `8080:80` untuk direct access
- Vite assets harus di-build di host sebelum container di-start (lihat Step 6.2)

**Error: "composer dump-autoload failed"**

Jika build gagal dengan error `composer dump-autoload`, coba langkah berikut:

**1. Check composer.lock compatibility:**

```bash
# Check apakah composer.lock ada
ls -la composer.lock

# Jika composer.lock dibuat dengan PHP 8.4, mungkin perlu regenerate
# Tapi untuk production, lebih baik fix Dockerfile (sudah diupdate)
```

**2. Build dengan no cache (jika ada masalah cache):**

```bash
# Build tanpa cache
docker-compose -f docker-compose.prod.yml build --no-cache laravel
```

**3. Check build logs untuk detail error:**

```bash
# Build dengan verbose output
docker-compose -f docker-compose.prod.yml build --progress=plain laravel 2>&1 | tee build.log

# Check error di log
grep -i error build.log
```

**4. Fix Dockerfile (CRITICAL - Update dengan multiple fallback):**

Dockerfile sudah diupdate dengan multiple fallback approaches untuk handle berbagai error scenarios. **Update Dockerfile dengan:**

```bash
# Edit Dockerfile
nano docker/php/Dockerfile

# Scroll ke line 140-146, ganti dengan:
# Generate optimized autoloader (after app files are copied)
# This ensures App namespace and other PSR-4 namespaces are properly mapped
# Use --no-scripts to avoid running post-autoload-dump scripts that may require env vars
# Add --ignore-platform-reqs to skip platform check issues
# Try multiple approaches if one fails
RUN (composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs --apcu 2>&1 || \
     composer dump-autoload --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     echo "Warning: composer dump-autoload failed, will regenerate at runtime") && \
    echo "Autoloader generation completed"
```

**Setelah update, rebuild:**

```bash
docker-compose -f docker-compose.prod.yml build --no-cache laravel
```

**5. Alternative: Build dengan platform flag:**

```bash
# Build dengan specify platform
docker build --platform linux/amd64 -f docker/php/Dockerfile -t app038-laravel:latest .
```

**6.2. Install Node.js & npm (untuk Build Vite Assets)**

**PENTING:** Vite assets perlu di-build sebelum container di-start. Install Node.js di host untuk build assets.

```bash
# Install Node.js 20.x (LTS) - Recommended untuk Vite 5.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version  # Should be v20.x.x
npm --version   # Should be 9.x.x or 10.x.x

# Install npm dependencies
cd /var/www/app038
npm install

# Build Vite assets untuk production
npm run build

# Verify build output
ls -la public/build/
# Expected: manifest.json dan assets/ directory
```

**6.3. Start Services**

```bash
# Start semua services
docker-compose -f docker-compose.prod.yml up -d

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

**Expected Output:**
```
NAME                IMAGE                    STATUS
app038_laravel      app038-laravel:latest    Up (healthy)
app038_postgres     postgres:15-alpine      Up (healthy)
app038_redis        redis:7-alpine          Up (healthy)
app038_rabbitmq     rabbitmq:3-management-alpine Up (healthy)
```

**Catatan:** 
- **Svelte container TIDAK diperlukan** - Laravel serve semua assets via Vite build
- RabbitMQ sudah termasuk di `docker-compose.prod.yml`
- Pastikan environment variables `RABBITMQ_USER` dan `RABBITMQ_PASSWORD` sudah di-set di `.env` file

**6.4. Verify Containers Running**

```bash
# Check all containers
docker ps

# Check specific containers
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038

# Check container health
docker-compose -f docker-compose.prod.yml ps

# Check container logs
docker logs app038_laravel --tail 50
docker logs app038_postgres --tail 50
docker logs app038_redis --tail 50
docker logs app038_rabbitmq --tail 50

# Verify Vite assets in container
docker exec app038_laravel ls -la /app/public/build/
# Expected: manifest.json dan assets/ directory
```

---

### Step 7: Setup Database

**7.1. Wait for Database Ready**

```bash
# Wait for PostgreSQL to be ready
sleep 10

# Check PostgreSQL status
docker exec app038_postgres pg_isready -U postgres
```

**7.2. Run Migrations**

```bash
# Run migrations
docker exec -it app038_laravel php artisan migrate --force

# Run seeders (opsional)
docker exec -it app038_laravel php artisan db:seed --force
```

**7.3. Verify Database Connection**

```bash
# Test database connection
docker exec -it app038_laravel php artisan tinker
# Di dalam tinker:
# DB::connection()->getPdo();
# exit
```

---

### Step 8: Install Nginx Reverse Proxy

**8.1. Install Nginx**

```bash
# Install Nginx
sudo apt install nginx -y

# Start Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Check status
sudo systemctl status nginx
```

**8.2. Create Nginx Configuration**

```bash
# Create Nginx configuration file
sudo nano /etc/nginx/sites-available/app038
```

**Isi dengan konfigurasi berikut (ganti `yourdomain.com` dengan domain Anda):**

```nginx
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # For Let's Encrypt verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }
    
    # Redirect semua HTTP ke HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS Configuration
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    # SSL Configuration (akan di-setup oleh Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/app038-access.log;
    error_log /var/log/nginx/app038-error.log;

    # Client body size
    client_max_body_size 20M;

    # Proxy to Svelte container (Frontend)
    # Svelte container expose port 80, map ke host port 80
    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support (untuk Inertia.js dan real-time features)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
    }
    
    # API routes (jika ingin proxy ke Laravel langsung)
    # Uncomment jika ingin route /api ke Laravel container
    # location /api {
    #     proxy_pass http://127.0.0.1:8080;
    #     proxy_set_header Host $host;
    #     proxy_set_header X-Real-IP $remote_addr;
    #     proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    #     proxy_set_header X-Forwarded-Proto $scheme;
    # }

    # Health check endpoint
    location /health {
        proxy_pass http://127.0.0.1:80/health;
        access_log off;
    }
}
```

**Catatan:** Jika belum punya domain, gunakan konfigurasi tanpa SSL terlebih dahulu:

```nginx
# HTTP Configuration (tanpa SSL - untuk testing)
server {
    listen 80;
    listen [::]:80;
    server_name 168.231.118.3 yourdomain.com www.yourdomain.com;

    # Logging
    access_log /var/log/nginx/app038-access.log;
    error_log /var/log/nginx/app038-error.log;

    # Client body size
    client_max_body_size 20M;

    # Proxy to Laravel container
    # Laravel container expose port 8080:80 (host:container)
    # Laravel serve semua: HTML, API, dan Assets (via Vite build)
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support (untuk Inertia.js dan real-time features)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
        proxy_temp_file_write_size 256k;
        proxy_max_temp_file_size 0;
    }

    # Health check endpoint
    location /health {
        proxy_pass http://127.0.0.1:8080/health;
        access_log off;
    }
}
```

**8.3. Enable Site**

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/

# Remove default site (opsional)
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

**8.4. Verify Nginx Configuration**

```bash
# Check Nginx status
sudo systemctl status nginx

# Check Nginx configuration
sudo nginx -t

# View Nginx logs
sudo tail -f /var/log/nginx/error.log
```

---

### Step 9: Setup SSL dengan Let's Encrypt

**9.1. Install Certbot**

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Verify installation
certbot --version
```

**9.2. Get SSL Certificate**

```bash
# Get SSL certificate (ganti dengan domain Anda)
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Follow prompts:
# - Enter email address (untuk renewal notifications)
# - Agree to terms (A)
# - Choose redirect HTTP to HTTPS (option 2)

# Certbot akan otomatis:
# - Generate SSL certificates
# - Update Nginx configuration
# - Setup auto-renewal
```

**9.3. Verify SSL Certificate**

```bash
# Check certificate
sudo certbot certificates

# Test auto-renewal
sudo certbot renew --dry-run

# Check certificate expiration
sudo certbot certificates | grep "Expiry Date"
```

**9.4. Setup Auto-renewal (Sudah Otomatis)**

```bash
# Check cron job (sudah otomatis dibuat oleh Certbot)
sudo systemctl status certbot.timer

# Manual renewal test
sudo certbot renew --dry-run
```

---

### Step 10: Setup Domain DNS

**10.1. Configure DNS Records**

Di DNS provider Anda (Hostinger, Cloudflare, dll), buat DNS records berikut:

1. **A Record untuk domain utama:**
   ```
   Type: A
   Name: @ (atau kosong)
   Value: 168.231.118.3
   TTL: 3600 (atau auto)
   ```

2. **A Record untuk www:**
   ```
   Type: A
   Name: www
   Value: 168.231.118.3
   TTL: 3600 (atau auto)
   ```

**10.2. Verify DNS Propagation**

```bash
# Check DNS propagation dari local machine
dig yourdomain.com
nslookup yourdomain.com

# Check dari VPS
dig yourdomain.com
nslookup yourdomain.com

# Check apakah domain pointing ke IP VPS yang benar
dig +short yourdomain.com
# Output harus sama dengan: 168.231.118.3

# Test HTTP connection
curl -I http://yourdomain.com
# Harus return HTTP response (bisa 301 redirect ke HTTPS jika SSL sudah aktif)

# Tunggu beberapa menit untuk DNS propagate (bisa sampai 24 jam)
# Biasanya 5-30 menit untuk sebagian besar DNS provider
```

**Tips DNS Propagation:**
- Gunakan DNS checker online: https://dnschecker.org
- Set TTL ke nilai rendah (300-600) sebelum setup untuk faster propagation
- Setelah setup selesai, bisa naikkan TTL ke 3600 untuk better performance

---

### Step 11: Verify Deployment

**11.1. Check Health Endpoint**

```bash
# Via terminal di VPS
curl http://localhost/health
# Expected: HTTP 200 OK dengan response "healthy"

# Atau via browser
curl http://168.231.118.3/health
curl https://yourdomain.com/health
```

**11.2. Check Application**

```bash
# Test website
curl -I http://168.231.118.3
curl -I https://yourdomain.com

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com | grep "Verify return code"
# Expected: Verify return code: 0 (ok)
```

**11.3. Check All Services**

```bash
# Check Docker containers
docker ps
# Expected: laravel, postgres, redis, rabbitmq

# Check container logs
docker logs app038_laravel --tail 50
docker logs app038_postgres --tail 50
docker logs app038_redis --tail 50
docker logs app038_rabbitmq --tail 50

# Check network connectivity
docker exec app038_laravel ping -c 3 postgres
docker exec app038_laravel ping -c 3 redis
docker exec app038_laravel ping -c 3 rabbitmq

# Check container health
docker ps --format "table {{.Names}}\t{{.Status}}"
# Semua container harus show "Up" atau "Up (healthy)"
```

**11.4. Test Application Functionality**

1. Buka browser: `https://yourdomain.com` atau `http://168.231.118.3`
2. Website harus sudah bisa diakses
3. Test login dan functionality
4. Check SSL certificate (jika menggunakan domain)

---

### Step 11A: Final Verification & Testing

**⚠️ PENTING: Pastikan semua langkah sudah dilakukan dengan benar!**

**11A.1. Verify All Services Running**

```bash
# Check all containers
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038

# Expected output:
# app038_laravel    Up (healthy)    0.0.0.0:8080->80/tcp
# app038_postgres   Up (healthy)    5432/tcp
# app038_redis      Up (healthy)    6379/tcp
# app038_rabbitmq   Up (healthy)    5672/tcp, 15672/tcp
```

**11A.2. Verify Application Access**

```bash
# Test from container directly
curl http://localhost:8080/health
# Expected: healthy

# Test from host Nginx
curl http://localhost/health
# Expected: healthy

# Test from external (if DNS already configured)
curl http://yourdomain.com/health
curl https://yourdomain.com/health
# Expected: healthy
```
# Gunakan IP tersebut, atau lebih baik gunakan host.docker.internal jika tersedia
```

**Reload Nginx:**
```bash
sudo nginx -t
sudo systemctl reload nginx
```

**11A.3. Verify Port Configuration**

```bash
# Check apakah Nginx menggunakan port 80
sudo netstat -tlnp | grep :80
sudo ss -tlnp | grep :80
# Expected: nginx harus listen di port 80

# Check apakah Laravel container menggunakan port 8080
sudo netstat -tlnp | grep :8080
sudo ss -tlnp | grep :8080
# Expected: docker-proxy atau container harus listen di port 8080

# Verify port mapping
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep app038_laravel
# Expected: 0.0.0.0:8080->80/tcp
```

**11A.4. Verify Firewall Rules**

```bash
# Check firewall status
sudo ufw status verbose

# Pastikan port 80 dan 443 terbuka
# Expected output harus include:
# 80/tcp                     ALLOW       Anywhere
# 443/tcp                    ALLOW       Anywhere

# Jika belum, tambahkan:
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

**11A.5. Test Akses dari Internet (Tanpa Domain - Via IP)**

```bash
# Test dari VPS sendiri
curl -I http://168.231.118.3
# Expected: HTTP 200 atau 301 (redirect ke HTTPS jika SSL aktif)

# Test dari komputer lokal (bukan VPS)
# Buka browser dan akses: http://168.231.118.3
# Website harus bisa diakses

# Test dengan curl dari komputer lokal
curl -I http://168.231.118.3
curl http://168.231.118.3/health
# Expected: HTTP 200 OK dengan response "healthy"
```

**11A.6. Test Akses dari Internet (Dengan Domain - Jika Punya Domain)**

```bash
# Pastikan DNS sudah pointing ke IP VPS
dig +short yourdomain.com
# Expected: 168.231.118.3

# Test HTTP (harus redirect ke HTTPS jika SSL aktif)
curl -I http://yourdomain.com
# Expected: HTTP 301 (redirect) atau 200

# Test HTTPS
curl -I https://yourdomain.com
# Expected: HTTP 200 OK

# Test dari browser
# Buka: https://yourdomain.com
# Website harus bisa diakses dengan SSL certificate valid
```

**11A.7. Final Verification Checklist**

**Lakukan semua check berikut untuk memastikan website bisa diakses online:**

```bash
# ✅ Checklist 1: Docker Containers Running
docker ps
# Expected: Semua container (laravel, postgres, redis, rabbitmq) harus "Up" dan "healthy"

# ✅ Checklist 2: Nginx Running
sudo systemctl status nginx
# Expected: Active (running)

# ✅ Checklist 3: Port 80 & 443 Open
sudo ufw status | grep -E "80|443"
# Expected: 80/tcp dan 443/tcp harus ALLOW

# ✅ Checklist 4: Port 80 Not Used by Container
sudo netstat -tlnp | grep :80
# Expected: Hanya nginx yang listen di port 80 (bukan container)

# ✅ Checklist 5: Nginx Configuration Valid
sudo nginx -t
# Expected: Syntax OK, test successful

# ✅ Checklist 6: Health Endpoint Accessible
curl http://localhost/health
curl http://168.231.118.3/health
# Expected: HTTP 200 OK dengan response "healthy"

# ✅ Checklist 7: Website Accessible from VPS
curl -I http://168.231.118.3
# Expected: HTTP 200 atau 301

# ✅ Checklist 8: Website Accessible from Internet (Test dari komputer lokal)
# Buka browser: http://168.231.118.3
# Expected: Website bisa diakses

# ✅ Checklist 9: SSL Certificate (Jika menggunakan domain)
curl -I https://yourdomain.com
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com < /dev/null 2>/dev/null | grep "Verify return code"
# Expected: Verify return code: 0 (ok)

# ✅ Checklist 10: Database Connection
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"
# Expected: OK (tidak ada error)

# ✅ Checklist 11: Application Logs (No Critical Errors)
docker logs app038_laravel --tail 20 | grep -i error
# Expected: Tidak ada error critical (warnings boleh ada)
```

**11A.8. Troubleshooting Akses dari Internet**

**Issue 1: Website tidak bisa diakses dari internet (timeout)**

```bash
# Check firewall
sudo ufw status verbose
# Pastikan port 80 dan 443 terbuka

# Check apakah Nginx listen di port 80
sudo netstat -tlnp | grep :80
# Expected: nginx harus listen di 0.0.0.0:80

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Test dari VPS sendiri
curl -I http://localhost
curl -I http://168.231.118.3
```

**Issue 2: Port 80 sudah digunakan oleh container**

```bash
# Find process using port 80
sudo lsof -i :80
sudo netstat -tlnp | grep :80

# Stop container yang menggunakan port 80
docker ps | grep 80
docker stop <container_id>

# Restart Nginx
sudo systemctl restart nginx

# Verify
sudo netstat -tlnp | grep :80
# Expected: Hanya nginx yang listen
```

**Issue 3: Nginx proxy error (502 Bad Gateway)**

```bash
# Check Nginx error logs
sudo tail -f /var/log/nginx/app038-error.log

# Check apakah Svelte container running
docker ps | grep app038_svelte

# Check Svelte container logs
docker logs app038_svelte --tail 50

# Test connection dari Nginx ke container
# Jika container menggunakan port 8080:
curl http://127.0.0.1:8080/health
# Expected: HTTP 200 OK

# Update Nginx proxy_pass jika perlu
sudo nano /etc/nginx/sites-available/app038
# Pastikan proxy_pass pointing ke port yang benar
sudo nginx -t
sudo systemctl reload nginx
```

**Issue 3A: Nginx 500 Internal Server Error**

**Error:** `500 Internal Server Error nginx/1.24.0 (Ubuntu)`

**Penyebab:**
- Container tidak berjalan atau crash
- Container mengembalikan error 500 dari aplikasi
- Nginx proxy_pass pointing ke port yang salah
- Container health check gagal
- Permission issues di container

**Solusi:**

**1. Check Nginx Error Logs (PENTING - Lakukan ini dulu!):**

```bash
# Check Nginx error logs untuk detail error
sudo tail -50 /var/log/nginx/app038-error.log

# Check Nginx access logs
sudo tail -50 /var/log/nginx/app038-access.log

# Real-time monitoring error logs
sudo tail -f /var/log/nginx/app038-error.log
# Lalu coba akses website lagi dari browser, lihat error yang muncul
```

**2. Check Container Status:**

```bash
# Check semua containers running
docker ps

# Check Svelte container khususnya
docker ps | grep app038_svelte
# Expected: Harus show "Up" status

# Jika container tidak running atau status "Exited", check logs:
docker logs app038_svelte --tail 100

# Check Laravel container juga
docker ps | grep app038_laravel
docker logs app038_laravel --tail 100
```

**3. Test Container Health Endpoint Langsung:**

```bash
# Test health endpoint dari container langsung (bypass Nginx)
# Jika Svelte container expose port 8080:
curl http://127.0.0.1:8080/health
# Expected: HTTP 200 OK dengan response "healthy"

# Atau jika container tidak expose port, test dari dalam container:
docker exec app038_svelte wget -q -O- http://localhost/health
# Expected: "healthy"

# Test Laravel container juga:
docker exec app038_laravel wget -q -O- http://localhost/health
# Expected: "healthy"
```

**4. Check Nginx Configuration:**

```bash
# Check Nginx configuration
sudo nginx -t

# Check apakah proxy_pass pointing ke port yang benar
sudo grep -A 5 "proxy_pass" /etc/nginx/sites-available/app038

# Check apakah Svelte container menggunakan port yang sesuai
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep app038_svelte
# Lihat port mapping, contoh: "0.0.0.0:8080->80/tcp"
# Nginx proxy_pass harus pointing ke port yang sama (8080)
```

**5. Fix Port Mapping Issue (Jika Container Port Berbeda):**

```bash
# Check port yang digunakan Svelte container
docker port app038_svelte
# Atau
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep app038_svelte

# Jika container menggunakan port 8080, update Nginx config:
sudo nano /etc/nginx/sites-available/app038

# Update proxy_pass dari:
# proxy_pass http://127.0.0.1:80;
# Menjadi:
# proxy_pass http://127.0.0.1:8080;

# Save dan test
sudo nginx -t
sudo systemctl reload nginx

# Test lagi
curl http://localhost/health
```

**6. Restart Containers:**

```bash
# Restart Svelte container
docker-compose -f docker-compose.prod.yml restart svelte

# Wait untuk container ready
sleep 10

# Check container status
docker ps | grep app038_svelte

# Check container logs
docker logs app038_svelte --tail 50

# Test health endpoint
curl http://127.0.0.1:8080/health
# Atau sesuai port yang digunakan container
```

**7. Check Container Logs untuk Errors:**

```bash
# Check Svelte container logs untuk errors
docker logs app038_svelte --tail 100 | grep -i error

# Check Laravel container logs
docker logs app038_laravel --tail 100 | grep -i error

# Check semua container logs
docker-compose -f docker-compose.prod.yml logs --tail=50
```

**8. Fix Permission Issues (Jika Ada):**

```bash
# Check storage permissions
docker exec app038_laravel ls -la storage/
# Expected: storage harus writable

# Fix permissions jika perlu
docker exec app038_laravel chmod -R 775 storage bootstrap/cache
docker exec app038_laravel chown -R www-data:www-data storage bootstrap/cache
```

**9. Complete Reset (Jika Masih Error):**

```bash
# Stop semua containers
docker-compose -f docker-compose.prod.yml down

# Check Nginx config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx

# Start containers lagi
docker-compose -f docker-compose.prod.yml up -d

# Wait untuk containers ready
sleep 20

# Check status
docker ps

# Test health endpoint
curl http://127.0.0.1:8080/health
# Atau sesuai port container

# Test via Nginx
curl http://localhost/health
```

**10. Quick Diagnostic Script:**

```bash
# Copy-paste script ini untuk diagnostic lengkap
echo "=== 500 Error Diagnostic ==="
echo ""
echo "1. Nginx Status:"
sudo systemctl status nginx --no-pager | grep Active
echo ""
echo "2. Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038
echo ""
echo "3. Nginx Error Log (Last 10 lines):"
sudo tail -10 /var/log/nginx/app038-error.log
echo ""
echo "4. Svelte Container Logs (Last 10 lines):"
docker logs app038_svelte --tail 10
echo ""
echo "5. Test Container Health (Direct):"
docker exec app038_svelte wget -q -O- http://localhost/health 2>&1 || echo "Container health check failed"
echo ""
echo "6. Test via Nginx:"
curl -I http://localhost/health 2>&1 | head -1
echo ""
echo "7. Port Mapping:"
docker port app038_svelte 2>&1 || echo "Container not running"
echo ""
echo "=== End Diagnostic ==="
```

**Common Causes:**
- ❌ Container tidak running atau crash
- ❌ Nginx proxy_pass pointing ke port yang salah
- ❌ Container health check gagal
- ❌ Permission issues di container
- ❌ Application error di dalam container
- ❌ Network connectivity issue antara Nginx dan container

**Prevention:**
- **Selalu check container status** sebelum troubleshoot Nginx
- **Verify port mapping** antara container dan Nginx proxy_pass
- **Check container logs** untuk application errors
- **Test health endpoint langsung** dari container untuk isolate masalah

**Issue 4: DNS tidak resolve (jika menggunakan domain)**

```bash
# Check DNS propagation
dig +short yourdomain.com
# Expected: 168.231.118.3

# Check dari multiple DNS servers
dig @8.8.8.8 yourdomain.com
dig @1.1.1.1 yourdomain.com

# Tunggu DNS propagation (bisa sampai 24 jam, biasanya 5-30 menit)
# Gunakan DNS checker online: https://dnschecker.org
```

**11A.9. Status Deployment Saat Ini**

**Gunakan checklist berikut untuk track progress deployment:**

```bash
# Copy dan paste di terminal, check setiap item:
echo "=== Deployment Status Checklist ==="
echo ""
echo "1. Docker & Docker Compose Installed:"
docker --version && docker-compose --version && echo "✅ OK" || echo "❌ MISSING"
echo ""
echo "2. Firewall Configured:"
sudo ufw status | grep -q "80/tcp" && echo "✅ Port 80 Open" || echo "❌ Port 80 Closed"
sudo ufw status | grep -q "443/tcp" && echo "✅ Port 443 Open" || echo "❌ Port 443 Closed"
echo ""
echo "3. Docker Network Created:"
docker network ls | grep -q app038_network && echo "✅ Network Exists" || echo "❌ Network Missing"
echo ""
echo "4. Environment File Configured:"
[ -f /var/www/app038/.env ] && echo "✅ .env Exists" || echo "❌ .env Missing"
grep -q "APP_ENV=production" /var/www/app038/.env 2>/dev/null && echo "✅ APP_ENV=production" || echo "❌ APP_ENV Wrong"
grep -q "DB_HOST=postgres" /var/www/app038/.env 2>/dev/null && echo "✅ DB_HOST=postgres" || echo "❌ DB_HOST Wrong"
echo ""
echo "5. Docker Containers Running:"
docker ps | grep -q app038_laravel && echo "✅ Laravel Running" || echo "❌ Laravel Not Running"
docker ps | grep -q app038_svelte && echo "✅ Svelte Running" || echo "❌ Svelte Not Running"
docker ps | grep -q app038_postgres && echo "✅ PostgreSQL Running" || echo "❌ PostgreSQL Not Running"
docker ps | grep -q app038_redis && echo "✅ Redis Running" || echo "❌ Redis Not Running"
echo ""
echo "6. Nginx Installed & Running:"
sudo systemctl is-active --quiet nginx && echo "✅ Nginx Running" || echo "❌ Nginx Not Running"
[ -f /etc/nginx/sites-enabled/app038 ] && echo "✅ Nginx Config Exists" || echo "❌ Nginx Config Missing"
echo ""
echo "7. SSL Certificate (Jika menggunakan domain):"
[ -d /etc/letsencrypt/live ] && echo "✅ SSL Directory Exists" || echo "⚠️  SSL Not Configured (OK jika belum punya domain)"
echo ""
echo "8. Website Accessible:"
curl -s -o /dev/null -w "%{http_code}" http://localhost/health | grep -q "200" && echo "✅ Health Endpoint OK" || echo "❌ Health Endpoint Failed"
curl -s -o /dev/null -w "%{http_code}" http://168.231.118.3/health | grep -q "200" && echo "✅ Website Accessible via IP" || echo "❌ Website Not Accessible"
echo ""
echo "=== End of Checklist ==="
```

**Setelah semua checklist ✅, website sudah siap diakses dari internet!**

---

### Step 12: Setup Auto-start on Boot

**12.1. Enable Docker Auto-start**

```bash
# Docker sudah auto-start, tapi pastikan
sudo systemctl enable docker
sudo systemctl status docker
```

**12.2. Setup Auto-start untuk Docker Compose**

```bash
# Create systemd service
sudo nano /etc/systemd/system/app038.service
```

**Isi file service:**

```ini
[Unit]
Description=App038 Docker Compose
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/var/www/app038
ExecStart=/usr/local/bin/docker-compose -f docker-compose.prod.yml up -d
ExecStop=/usr/local/bin/docker-compose -f docker-compose.prod.yml down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
```

**12.3. Enable Service**

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service
sudo systemctl enable app038.service

# Test service
sudo systemctl start app038.service
sudo systemctl status app038.service
```

---

## Post-Deployment

### 1. Verify RabbitMQ Service

**Catatan:** File `docker-compose.prod.yml` sudah diupdate untuk include RabbitMQ service dengan semua environment variables yang diperlukan.

**Verify RabbitMQ sudah running:**

```bash
# Check RabbitMQ container
docker ps | grep rabbitmq

# Check RabbitMQ logs
docker logs app038_rabbitmq --tail 50

# Test RabbitMQ connection
docker exec app038_rabbitmq rabbitmq-diagnostics ping
```

**Jika RabbitMQ tidak muncul, pastikan:**

1. Service RabbitMQ sudah ditambahkan di `docker-compose.prod.yml`
2. Environment variables `RABBITMQ_USER` dan `RABBITMQ_PASSWORD` sudah di-set di `.env`
3. Volume `rabbitmq_data` sudah ditambahkan di section volumes

**Contoh konfigurasi RabbitMQ (jika belum ada):**

```yaml
  # RabbitMQ Message Queue
  rabbitmq:
    image: rabbitmq:3-management-alpine
    container_name: app038_rabbitmq
    restart: unless-stopped
    environment:
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
      RABBITMQ_DEFAULT_VHOST: /
    volumes:
      - rabbitmq_data:/var/lib/rabbitmq
    networks:
      - app038_network
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "ping"]
      interval: 30s
      timeout: 10s
      retries: 5
```

**Dan tambahkan volume di section volumes:**

```yaml
volumes:
  postgres_data:
  redis_data:
  rabbitmq_data:  # Tambahkan ini
```

**Update laravel service untuk include RabbitMQ environment variables:**

```yaml
  laravel:
    # ... existing config ...
    environment:
      # ... existing env vars ...
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672
      - RABBITMQ_USER=${RABBITMQ_USER}
      - RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}
    depends_on:
      - postgres
      - redis
      - rabbitmq  # Tambahkan dependency
```

**Setelah update, restart services:**

```bash
cd /var/www/app038
docker-compose -f docker-compose.prod.yml up -d
```

### 2. Setup Queue Workers (Jika Diperlukan)

Jika aplikasi menggunakan Laravel queues, tambahkan service di `docker-compose.prod.yml`:

```yaml
queue-worker:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  container_name: app038_queue_worker
  restart: unless-stopped
  command: php artisan queue:work rabbitmq --sleep=3 --tries=3 --max-time=3600
  environment:
    - APP_ENV=production
    - APP_DEBUG=false
    - DB_CONNECTION=pgsql
    - DB_HOST=postgres
    - DB_PORT=5432
    - DB_DATABASE=${DB_DATABASE}
    - DB_USERNAME=${DB_USERNAME}
    - DB_PASSWORD=${DB_PASSWORD}
    - REDIS_HOST=redis
    - REDIS_PORT=6379
    - REDIS_PASSWORD=${REDIS_PASSWORD}
    - CACHE_DRIVER=redis
    - SESSION_DRIVER=redis
    - QUEUE_CONNECTION=rabbitmq
    - RABBITMQ_HOST=rabbitmq
    - RABBITMQ_PORT=5672
    - RABBITMQ_USER=${RABBITMQ_USER}
    - RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}
  volumes:
    - ./storage:/app/storage
    - ./bootstrap/cache:/app/bootstrap/cache
  networks:
    - app038_network
  depends_on:
    - postgres
    - redis
    - rabbitmq
```

**Start queue worker:**

```bash
# Update docker-compose.prod.yml dengan service di atas
# Lalu start service
docker-compose -f docker-compose.prod.yml up -d queue-worker

# Check status
docker ps | grep queue-worker
```

### 3. Setup Scheduled Tasks (Laravel Scheduler)

Tambahkan service untuk Laravel scheduler:

```yaml
scheduler:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  container_name: app038_scheduler
  restart: unless-stopped
  command: php artisan schedule:work
  environment:
    # Same as laravel service (copy dari laravel service)
  volumes:
    - ./storage:/app/storage
  networks:
    - app038_network
  depends_on:
    - postgres
    - redis
```

**Start scheduler:**

```bash
# Update docker-compose.prod.yml dengan service di atas
# Lalu start service
docker-compose -f docker-compose.prod.yml up -d scheduler

# Check status
docker ps | grep scheduler
```

### 4. Setup Automated Backups

**3.1. Database Backup Script**

```bash
# Create backup script
sudo nano /usr/local/bin/app038-backup-db.sh
```

**Isi script:**

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/app038"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
docker exec app038_postgres pg_dump -U postgres app038 | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/app038-backup-db.sh

# Test script
sudo /usr/local/bin/app038-backup-db.sh
```

**3.2. Setup Cron Job untuk Backup**

```bash
# Edit crontab
sudo crontab -e

# Add line (daily backup at 2 AM):
0 2 * * * /usr/local/bin/app038-backup-db.sh >> /var/log/app038-backup.log 2>&1
```

**3.3. Storage Backup Script**

```bash
# Create storage backup script
sudo nano /usr/local/bin/app038-backup-storage.sh
```

**Isi script:**

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/app038/storage"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup storage directory
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /var/www/app038 storage

# Keep only last 7 days
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +7 -delete

echo "Storage backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/app038-backup-storage.sh

# Add to crontab (weekly backup)
# 0 3 * * 0 /usr/local/bin/app038-backup-storage.sh >> /var/log/app038-backup.log 2>&1
```

---

## Monitoring & Maintenance

### Daily Tasks

```bash
# Check application health
curl http://168.231.118.3/health
curl https://yourdomain.com/health

# Check container status
docker ps

# Check resource usage
free -h      # RAM
df -h        # Disk
htop         # CPU & Memory (install: apt install htop)
```

### Weekly Tasks

```bash
# Review application logs
docker logs app038_laravel --tail 100
docker logs app038_svelte --tail 100
docker logs app038_postgres --tail 100

# Check Nginx logs
sudo tail -f /var/log/nginx/app038-error.log

# Check for security updates
sudo apt list --upgradable

# Check disk usage
df -h
docker system df
```

### Monthly Tasks

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker images (jika perlu)
cd /var/www/app038
git pull origin main
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d

# Review and optimize database
docker exec app038_postgres psql -U postgres -d app038 -c "VACUUM ANALYZE;"

# Review backup files
ls -lh /var/backups/app038/
```

---

## Troubleshooting

### Issue: .env.example File Not Found

**Error:** `cp: cannot stat '.env.example': No such file or directory`

**Solusi:**

**1. Buat .env file langsung:**

```bash
# Buat file .env baru
touch .env

# Atau gunakan template dari dokumentasi
cat > .env << 'EOF'
# Application Configuration
APP_NAME=App038
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://168.231.118.3

LOG_CHANNEL=stack
LOG_LEVEL=info

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration
QUEUE_CONNECTION=rabbitmq

# RabbitMQ Configuration
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=
RABBITMQ_VHOST=/
EOF

# Edit file untuk mengisi values
nano .env
```

**2. Atau copy dari ENV_SKELETON.md:**

```bash
# File ENV_SKELETON.md ada di repository
cat ENV_SKELETON.md
# Copy relevant sections ke .env file
```

**3. Generate APP_KEY setelah membuat .env:**

```bash
# Install PHP CLI dan Composer (jika belum)
sudo apt install php-cli php-mbstring -y
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate APP_KEY (akan dijelaskan di Step 4.3)
```

### Issue: Docker Build Error - "docker/svelte/default.conf: not found"

**Error:** `failed to solve: failed to compute cache key: failed to calculate checksum of ref ... "/docker/svelte/default.conf": not found`

**Penyebab:**
- File `.dockerignore` di root project mengexclude folder `docker/svelte`
- File konfigurasi Nginx (`default.conf` dan `nginx.conf`) tidak tersedia saat Docker build
- Build context tidak include folder `docker/svelte` karena di-exclude oleh `.dockerignore`

**Solusi:**

**1. Pull Latest Changes dari Repository (CRITICAL - Lakukan ini dulu!):**

```bash
# Pull latest changes dari repository
cd /var/www/app038

# Check status
git status

# Pull latest changes (file .dockerignore sudah diupdate di repository)
git pull origin main

# Verify .dockerignore sudah terupdate
grep -A 2 "docker/svelte" .dockerignore
# Should show commented line atau tidak ada baris yang mengexclude docker/svelte
```

**2. Manual Fix .dockerignore (Jika pull tidak berhasil):**

```bash
# Edit .dockerignore
nano .dockerignore
# Atau
vi .dockerignore

# Hapus atau comment baris berikut:
# docker/svelte

# Atau pastikan baris tersebut sudah di-comment:
# # docker/svelte  # Commented out - needed for Docker build

# Save dan exit (Ctrl+X, Y, Enter untuk nano)
```

**3. Verifikasi File Konfigurasi Ada:**

```bash
# Check apakah file default.conf dan nginx.conf ada
ls -la docker/svelte/default.conf
ls -la docker/svelte/nginx.conf

# Expected output: file harus ada dan readable
# -rw-r--r-- 1 user user ... docker/svelte/default.conf
# -rw-r--r-- 1 user user ... docker/svelte/nginx.conf
```

**4. Rebuild dengan No Cache:**

```bash
# Clean build untuk service svelte
docker-compose -f docker-compose.prod.yml build --no-cache svelte

# Atau rebuild semua services
docker-compose -f docker-compose.prod.yml build --no-cache

# Check build progress
docker-compose -f docker-compose.prod.yml build --progress=plain svelte
```

**5. Verify Build Context:**

```bash
# Test apakah file bisa diakses dari build context
docker build --no-cache -f docker/svelte/Dockerfile -t test-svelte:latest . 2>&1 | grep -i "default.conf"

# Jika masih error, check apakah file benar-benar ada:
find . -name "default.conf" -type f
# Should show: ./docker/svelte/default.conf
```

**6. Alternative: Copy File ke Lokasi Lain (Jika Masih Error):**

Jika masih error setelah fix `.dockerignore`, bisa copy file ke lokasi yang tidak di-exclude:

```bash
# Copy file ke root project (temporary)
cp docker/svelte/default.conf ./default.conf.svelte
cp docker/svelte/nginx.conf ./nginx.conf.svelte

# Update Dockerfile untuk menggunakan file di root:
# COPY default.conf.svelte /etc/nginx/conf.d/default.conf
# COPY nginx.conf.svelte /etc/nginx/nginx.conf

# Tapi ini tidak recommended, lebih baik fix .dockerignore
```

**Catatan:** File `.dockerignore` di repository sudah diupdate untuk tidak mengexclude `docker/svelte`. Pastikan Anda sudah pull latest changes sebelum build.

**Prevention:** 
- Jangan exclude folder `docker/` atau subfolder-nya di `.dockerignore` jika file-file tersebut diperlukan untuk Docker build
- Gunakan pattern yang lebih spesifik jika perlu exclude file tertentu (misalnya: `docker/**/*.md` untuk exclude markdown files saja)

### Issue: Docker Build Error - composer dump-autoload Failed

**Error:** `failed to solve: process "/bin/sh -c composer dump-autoload --optimize --classmap-authoritative --no-dev" did not complete successfully: exit code: 1`

**Atau:** `ERROR: process "/bin/sh -c composer dump-autoload --optimize --classmap-authoritative --no-dev" did not complete successfully: exit code: 1`

**Penyebab:**
- Composer dependencies tidak terinstall dengan benar
- Platform check issues (PHP version mismatch)
- Composer.lock tidak compatible
- Missing files atau permissions issues

**Solusi:**

**1. Pull Latest Changes dari Repository (CRITICAL - Lakukan ini dulu!):**

```bash
# Pull latest changes dari repository
cd /var/www/app038

# Check status dulu
git status

# Jika ada local changes yang conflict, pilih salah satu:
# Opsi A: Discard local changes (Recommended - karena versi di repo sudah lebih baik)
git checkout -- docker/php/Dockerfile
git pull origin main

# Opsi B: Stash local changes (jika ingin keep changes untuk review)
git stash
git pull origin main
git stash pop  # Jika ingin apply changes kembali (biasanya tidak perlu)

# Opsi C: Commit local changes dulu (jika ada perubahan penting)
git add docker/php/Dockerfile
git commit -m "temp: local Dockerfile changes"
git pull origin main
# Resolve conflicts jika ada

# Verify Dockerfile sudah terupdate
grep -A 10 "Generate optimized autoloader" docker/php/Dockerfile

# Expected output harus menunjukkan multiple fallback commands dengan --ignore-platform-reqs
```

**2. Build dengan --no-cache (Setelah pull latest changes):**

```bash
# Clean build tanpa cache
cd /var/www/app038
docker-compose -f docker-compose.prod.yml build --no-cache laravel
```

**3. Handle Git Merge Conflicts (Jika pull gagal karena local changes):**

**Error:** `error: Your local changes to the following files would be overwritten by merge`

**Solusi:**

```bash
# Opsi A: Discard local changes (Recommended - versi di repo sudah lebih baik)
cd /var/www/app038
git checkout -- docker/php/Dockerfile
git pull origin main

# Opsi B: Stash local changes
git stash
git pull origin main
# Jika tidak perlu local changes, bisa skip: git stash drop

# Opsi C: Commit local changes dulu
git add docker/php/Dockerfile
git commit -m "temp: local Dockerfile changes"
git pull origin main
# Resolve conflicts jika ada, atau:
# git checkout --theirs docker/php/Dockerfile  # Use version from repo
# git add docker/php/Dockerfile
# git commit -m "fix: use updated Dockerfile from repo"
```

**4. Check dan fix Dockerfile (Jika pull tidak berhasil atau perlu manual update):**

Dockerfile sudah diupdate dengan multiple fallback approaches. **Pastikan menggunakan versi terbaru:**

```bash
# Check Dockerfile line 140-149
grep -A 10 "Generate optimized autoloader" docker/php/Dockerfile

# Jika output menunjukkan command lama (tanpa multiple fallback), perlu update manual:
nano docker/php/Dockerfile
```

**Update line 140-146 dengan:**

```dockerfile
# Generate optimized autoloader (after app files are copied)
# This ensures App namespace and other PSR-4 namespaces are properly mapped
# Use --no-scripts to avoid running post-autoload-dump scripts that may require env vars
# Add --ignore-platform-reqs to skip platform check issues
# Try multiple approaches if one fails
RUN (composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs --apcu 2>&1 || \
     composer dump-autoload --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     echo "Warning: composer dump-autoload failed, will regenerate at runtime") && \
    echo "Autoloader generation completed"
```

**Atau gunakan sed untuk update otomatis:**

```bash
# Backup Dockerfile
cp docker/php/Dockerfile docker/php/Dockerfile.bak

# Update dengan sed (complex, lebih baik edit manual)
# Atau pull latest dari repository jika sudah diupdate
```

**3. Check composer.lock:**

```bash
# Check apakah composer.lock ada
ls -la composer.lock

# Jika composer.lock dibuat dengan PHP 8.4, mungkin perlu regenerate
# Tapi untuk production, lebih baik gunakan --ignore-platform-reqs di Dockerfile
```

**4. Build dengan verbose output untuk debug (Jika masih error setelah pull):**

```bash
# Build dengan progress plain untuk melihat detail error
docker-compose -f docker-compose.prod.yml build --progress=plain laravel 2>&1 | tee build.log

# Check error di log
tail -100 build.log | grep -i error

# Check detail error dari composer
grep -A 30 "composer dump-autoload" build.log | tail -40
```

**5. Alternative: Build manual dengan Docker:**

```bash
# Build manual dengan specify platform
cd /var/www/app038
docker build --platform linux/amd64 \
  --build-arg BUILDKIT_INLINE_CACHE=1 \
  -f docker/php/Dockerfile \
  -t app038-laravel:latest .
```

**6. Check disk space dan permissions:**

```bash
# Check disk space
df -h

# Check permissions
ls -la /var/www/app038

# Fix permissions jika perlu
sudo chown -R $USER:$USER /var/www/app038
```

**7. Rebuild composer dependencies:**

```bash
# Jika masih error, coba rebuild dari stage composer
docker build --target composer -f docker/php/Dockerfile -t app038-composer:latest .
docker build --from app038-composer:latest -f docker/php/Dockerfile -t app038-laravel:latest .
```

**8. Alternative: Skip dump-autoload di build time (generate di runtime):**

Jika semua solusi di atas gagal, bisa skip dump-autoload di build time dan generate di runtime:

```bash
# Edit Dockerfile, comment atau hapus line 140-146
# Atau ganti dengan:
RUN echo "Autoloader will be generated at container startup" && \
    mkdir -p bootstrap/cache && \
    touch bootstrap/cache/.gitkeep

# Lalu di entrypoint atau startup script, tambahkan:
# composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs
```

**9. Check detail error dari build log:**

```bash
# Build dengan verbose dan save log
docker-compose -f docker-compose.prod.yml build --progress=plain laravel 2>&1 | tee build-full.log

# Check error detail sekitar line composer dump-autoload
grep -A 20 "composer dump-autoload" build-full.log

# Check apakah ada missing files atau permissions issues
grep -i "permission\|denied\|missing\|not found" build-full.log
```

**Catatan:** Dockerfile sudah diupdate dengan multiple fallback approaches. **Pastikan menggunakan versi terbaru dari repository atau update manual sesuai instruksi di atas.**

### Issue: Docker Run Error - Image Not Found

**Error:** `Unable to find image 'artisan:latest' locally` atau `pull access denied for artisan`

**Penyebab:**
- Command untuk extract image name dari docker-compose tidak bekerja dengan benar
- Image name yang digunakan salah
- Image belum di-build

**Solusi:**

**1. Generate APP_KEY Manual (Paling Simple - Recommended):**

```bash
# Generate APP_KEY tanpa Docker/PHP
cd /var/www/app038
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env
echo "APP_KEY generated: base64:${APP_KEY_VALUE}"
grep APP_KEY .env
```

**2. Generate dengan PHP Docker Image (Jika perlu menggunakan artisan):**

```bash
# Gunakan PHP image langsung
cd /var/www/app038
docker run --rm -v $(pwd):/app -w /app \
  php:8.2-cli-alpine \
  sh -c "composer install --ignore-platform-reqs --no-dev --no-scripts && php artisan key:generate --show"

# Copy output ke .env file
```

**3. Build Image Dulu, Lalu Generate:**

```bash
# Build image dulu
cd /var/www/app038
docker-compose -f docker-compose.prod.yml build laravel

# Generate APP_KEY menggunakan image yang sudah di-build
docker run --rm -v $(pwd):/app -w /app \
  app038_laravel:latest \
  php artisan key:generate --show
```

**Catatan:** Method 1 (Generate Manual) adalah yang paling simple dan tidak perlu build Docker image atau install PHP. Recommended untuk production deployment.

### Issue: PHP Version Mismatch (Composer Platform Check)

**Error:** `Your Composer dependencies require a PHP version ">= 8.4.0". You are running 8.3.6.`

**Penyebab:** 
- `composer.lock` mungkin dibuat dengan PHP 8.4
- Atau ada dependency yang memerlukan PHP 8.4
- Tapi aplikasi akan berjalan di Docker dengan PHP 8.2 (sesuai Dockerfile)

**Solusi:**

**1. Generate APP_KEY Manual (Paling Simple - Recommended):**

```bash
# Generate APP_KEY tanpa perlu PHP/Composer
APP_KEY_VALUE=$(openssl rand -base64 32)
echo "base64:${APP_KEY_VALUE}"

# Update .env file
sed -i "s/APP_KEY=/APP_KEY=base64:${APP_KEY_VALUE}/" .env

# Verify
grep APP_KEY .env
```

**2. Install PHP 8.4 dari PPA:**

```bash
# Add PHP repository
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4
sudo apt install -y php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate APP_KEY
cd /var/www/app038
php8.4 artisan key:generate
```

**3. Generate dengan Docker (Tidak perlu install PHP di host):**

```bash
# Method 1: Gunakan PHP image langsung (Simple)
cd /var/www/app038
docker run --rm -v $(pwd):/app -w /app \
  php:8.2-cli-alpine \
  sh -c "composer install --ignore-platform-reqs --no-dev --no-scripts && php artisan key:generate --show"

# Method 2: Build image dulu, lalu generate (jika image sudah di-build)
docker-compose -f docker-compose.prod.yml build laravel
docker run --rm -v $(pwd):/app -w /app \
  app038_laravel:latest \
  php artisan key:generate --show

# Method 3: Generate manual (Paling Simple - Recommended)
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env
grep APP_KEY .env
```

**4. Skip Platform Check (Temporary):**

```bash
# Install PHP 8.3 (default)
sudo apt install -y php-cli php-mbstring php-xml php-curl php-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate dengan skip platform check
cd /var/www/app038
composer install --ignore-platform-reqs
php artisan key:generate
```

**Catatan:** Untuk production, aplikasi akan berjalan di Docker dengan PHP 8.2, jadi tidak perlu install PHP 8.4 di host. Generate APP_KEY manual (Opsi 1) adalah cara tercepat.

### Issue: Git Clone Authentication Failed

**Error:** `remote: Invalid username or token. Password authentication is not supported for Git operations.`

**Solusi:**

**1. Menggunakan Personal Access Token (PAT):**

```bash
# Clear credential cache
git credential-cache exit

# Clone dengan token di URL
git clone https://YOUR_PERSONAL_ACCESS_TOKEN@github.com/rdeeanz/app038.git

# Atau clone normal, lalu masukkan token saat diminta password
git clone https://github.com/rdeeanz/app038.git
# Username: rdeeanz
# Password: YOUR_PERSONAL_ACCESS_TOKEN (bukan password GitHub)
```

**2. Menggunakan SSH Key (Recommended):**

```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "vps-hostinger"
cat ~/.ssh/id_ed25519.pub
# Copy output dan add ke GitHub Settings → SSH and GPG keys

# Test connection
ssh -T git@github.com

# Clone dengan SSH
git clone git@github.com:rdeeanz/app038.git
```

**3. Setup Credential Helper:**

```bash
# Store credentials (akan menyimpan token)
git config --global credential.helper store

# Clone repository
git clone https://github.com/rdeeanz/app038.git
# Masukkan username dan token saat diminta
# Credentials akan tersimpan untuk next time
```

**Cara membuat Personal Access Token:**
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Select scopes: `repo` (untuk private) atau `public_repo` (untuk public)
4. Generate dan copy token
5. Gunakan token sebagai password

### Issue: Cannot Connect via SSH

```bash
# Check firewall
sudo ufw status
sudo ufw allow 22/tcp

# Check SSH service
sudo systemctl status ssh
sudo systemctl restart ssh
```

### Issue: Container Restarting (Crash Loop)

**Error:** `Error response from daemon: Container ... is restarting, wait until the container is running.`

**Penyebab:**
- Container terus-menerus crash dan restart (crash loop)
- Biasanya disebabkan oleh application error, missing environment variables, atau configuration issues

**Solusi Step-by-Step:**

**1. Check Container Status dan Logs (PENTING - Lakukan ini dulu!):**

```bash
# Check status semua containers
docker ps -a

# Check status container Laravel
docker ps -a | grep app038_laravel
# Expected: Status akan show "Restarting" atau "Exited"

# Check logs container Laravel (ini yang paling penting!)
docker logs app038_laravel --tail 100
# Atau untuk real-time logs:
docker logs app038_laravel -f

# Check logs dengan timestamp untuk melihat pattern
docker logs app038_laravel --tail 100 --timestamps
```

**2. Common Causes dan Solutions:**

**A. Missing APP_KEY (Paling Umum):**

**Error di logs:** `No application encryption key has been specified`

**Solusi:**
```bash
# Check apakah APP_KEY sudah di-set
grep APP_KEY .env

# Jika APP_KEY kosong atau tidak ada, generate:
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=.*/APP_KEY=base64:${APP_KEY_VALUE}/" .env
# Atau jika belum ada baris APP_KEY:
echo "APP_KEY=base64:${APP_KEY_VALUE}" >> .env

# Verify
grep APP_KEY .env

# Restart container
docker-compose -f docker-compose.prod.yml restart laravel
```

**B. Database Connection Error:**

**Error di logs:** `SQLSTATE[08006] [7] could not connect to server` atau `Connection refused`

**Solusi:**
```bash
# Check apakah PostgreSQL container running
docker ps | grep app038_postgres

# Check PostgreSQL logs
docker logs app038_postgres --tail 50

# Check database environment variables
grep -E "DB_HOST|DB_DATABASE|DB_USERNAME|DB_PASSWORD" .env

# Test database connection dari host
docker exec app038_postgres pg_isready -U postgres

# Jika PostgreSQL tidak running, start dulu:
docker-compose -f docker-compose.prod.yml up -d postgres

# Wait untuk PostgreSQL ready (10-30 detik)
sleep 15

# Restart Laravel container
docker-compose -f docker-compose.prod.yml restart laravel
```

**C. Missing Environment Variables:**

**Error di logs:** `Undefined array key` atau `Environment variable not found`

**Solusi:**
```bash
# Check semua required environment variables
cat .env | grep -v "^#" | grep -v "^$"

# Verify required variables ada:
# - APP_KEY
# - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - REDIS_HOST, REDIS_PASSWORD
# - RABBITMQ_USER, RABBITMQ_PASSWORD

# Jika ada yang missing, tambahkan ke .env file
nano .env

# Restart container
docker-compose -f docker-compose.prod.yml restart laravel
```

**D. Storage Permissions Error:**

**Error di logs:** `Permission denied` atau `failed to open stream: Permission denied`

**Solusi:**
```bash
# Fix storage permissions
cd /var/www/app038
sudo chown -R $USER:$USER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Atau jika menggunakan Docker volumes:
docker-compose -f docker-compose.prod.yml down
sudo chown -R 1000:1000 storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
docker-compose -f docker-compose.prod.yml up -d
```

**E. Missing Dependencies atau Composer Error:**

**Error di logs:** `Class not found` atau `Composer autoload error`

**Solusi:**
```bash
# Rebuild container dengan no cache
docker-compose -f docker-compose.prod.yml build --no-cache laravel

# Start container
docker-compose -f docker-compose.prod.yml up -d laravel

# Check logs lagi
docker logs app038_laravel --tail 50
```

**3. Stop Container dan Check Logs Detail:**

```bash
# Stop container untuk prevent restart loop
docker stop app038_laravel

# Check logs tanpa restart interference
docker logs app038_laravel --tail 200

# Check last error message
docker logs app038_laravel 2>&1 | tail -50 | grep -i error

# Check specific error patterns
docker logs app038_laravel 2>&1 | grep -iE "fatal|error|exception|failed"
```

**4. Execute Command di Container (Jika Container Bisa Start Sebentar):**

```bash
# Wait untuk container start (meskipun akan restart)
# Coba execute command saat container sedang running
docker exec app038_laravel php artisan --version

# Check environment variables di container
docker exec app038_laravel env | grep -E "APP_|DB_|REDIS_"

# Check PHP errors
docker exec app038_laravel php -v
docker exec app038_laravel php -m | grep -E "pdo|pgsql|redis"
```

**5. Temporary Fix: Run Migration Tanpa Container (Jika Container Tidak Stabil):**

```bash
# Stop container
docker stop app038_laravel

# Run migration menggunakan PHP image langsung
docker run --rm \
  --network app038_network \
  -v $(pwd):/app \
  -w /app \
  -e APP_ENV=production \
  -e DB_HOST=postgres \
  -e DB_PORT=5432 \
  -e DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2) \
  -e DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2) \
  -e DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2) \
  php:8.2-cli-alpine \
  sh -c "apk add --no-cache postgresql-dev && docker-php-ext-install pdo pdo_pgsql && php artisan migrate --force"
```

**6. Check Dependencies (PostgreSQL, Redis, RabbitMQ):**

```bash
# Check semua dependencies running
docker ps | grep -E "postgres|redis|rabbitmq"

# Check network connectivity
docker network inspect app038_network

# Test connectivity dari Laravel container (jika bisa start)
docker exec app038_laravel ping -c 3 postgres
docker exec app038_laravel ping -c 3 redis
docker exec app038_laravel ping -c 3 rabbitmq
```

**7. Complete Reset (Last Resort):**

```bash
# Stop semua containers
docker-compose -f docker-compose.prod.yml down

# Check dan fix .env file
nano .env
# Pastikan semua required variables ada dan valid

# Rebuild containers
docker-compose -f docker-compose.prod.yml build --no-cache

# Start dependencies dulu
docker-compose -f docker-compose.prod.yml up -d postgres redis rabbitmq

# Wait untuk dependencies ready
sleep 20

# Start Laravel
docker-compose -f docker-compose.prod.yml up -d laravel

# Monitor logs
docker logs app038_laravel -f
```

**8. Check System Resources:**

```bash
# Check disk space
df -h

# Check memory
free -h

# Check Docker resources
docker system df

# Clean Docker jika perlu
docker system prune -f
```

**Quick Diagnostic Commands:**

```bash
# One-liner untuk check semua issues
echo "=== Container Status ===" && \
docker ps -a | grep app038 && \
echo -e "\n=== Laravel Logs (Last 50 lines) ===" && \
docker logs app038_laravel --tail 50 && \
echo -e "\n=== Environment Variables ===" && \
grep -E "APP_KEY|DB_|REDIS_|RABBITMQ_" .env && \
echo -e "\n=== Dependencies Status ===" && \
docker ps | grep -E "postgres|redis|rabbitmq"
```

### Issue: Supervisor Directory Not Found

**Error:** `Error: The directory named as part of the path /var/log/supervisor/supervisord.log does not exist`

**Penyebab:**
- Directory `/var/log/supervisor/` tidak ada di container
- Supervisor mencoba menulis log ke path yang tidak ada
- Dockerfile belum membuat directory yang diperlukan

**Solusi:**

**1. Pull Latest Changes dari Repository (CRITICAL - Lakukan ini dulu!):**

```bash
# Pull latest changes dari repository (Dockerfile sudah diupdate)
cd /var/www/app038
git pull origin main

# Verify Dockerfile sudah terupdate
grep -A 5 "Create Supervisor directories" docker/php/Dockerfile
# Should show: RUN mkdir -p /var/log/supervisor /var/run
```

**2. Rebuild Container dengan No Cache:**

```bash
# Rebuild Laravel container
docker-compose -f docker-compose.prod.yml build --no-cache laravel

# Start container
docker-compose -f docker-compose.prod.yml up -d laravel

# Check logs
docker logs app038_laravel --tail 50
```

**3. Manual Fix (Jika Pull Tidak Berhasil):**

**Opsi A: Update Dockerfile Manual:**

```bash
# Edit Dockerfile
nano docker/php/Dockerfile

# Cari baris:
# # Configure Supervisor
# COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Tambahkan setelah baris COPY supervisord.conf:
# # Create Supervisor directories and set permissions
# RUN mkdir -p /var/log/supervisor /var/run \
#     && chmod -R 755 /var/log/supervisor /var/run

# Save dan exit (Ctrl+X, Y, Enter)
```

**Opsi B: Update supervisord.conf untuk Gunakan stdout/stderr:**

```bash
# Edit supervisord.conf
nano docker/php/supervisord.conf

# Ganti baris:
# logfile=/var/log/supervisor/supervisord.log
# Menjadi:
# logfile=/dev/stdout

# Atau hapus baris logfile untuk default ke stdout

# Save dan exit
```

**4. Alternative: Fix di Container Running (Temporary):**

```bash
# Stop container
docker stop app038_laravel

# Start container dengan command override untuk create directory
docker run --rm -it \
  --name app038_laravel_temp \
  --network app038_network \
  -v $(pwd)/storage:/app/storage \
  -v $(pwd)/bootstrap/cache:/app/bootstrap/cache \
  app038_laravel:latest \
  sh -c "mkdir -p /var/log/supervisor /var/run && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf"

# Tapi ini hanya temporary, lebih baik fix Dockerfile
```

**5. Verify Fix:**

```bash
# Rebuild dan start
docker-compose -f docker-compose.prod.yml build --no-cache laravel
docker-compose -f docker-compose.prod.yml up -d laravel

# Check logs (seharusnya tidak ada error supervisor)
docker logs app038_laravel --tail 50

# Check container status
docker ps | grep app038_laravel
# Harus show "Up" (bukan "Restarting")

# Test supervisor running
docker exec app038_laravel ps aux | grep supervisord
```

**Catatan:** Dockerfile di repository sudah diupdate untuk membuat directory yang diperlukan. Pastikan pull latest changes sebelum rebuild.

### Issue: CollisionServiceProvider Not Found

**Error:** `Class "NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider" not found`

**Penyebab:**
- `nunomaduro/collision` adalah dev dependency (require-dev)
- Di production, Composer install dengan `--no-dev` flag (benar)
- Tapi file cache di `bootstrap/cache/` masih mengandung referensi ke CollisionServiceProvider
- File cache ini di-generate saat development dengan dev dependencies terinstall

**Solusi:**

**1. Pull Latest Changes dari Repository (CRITICAL - Lakukan ini dulu!):**

```bash
# Pull latest changes (Dockerfile sudah diupdate untuk clear bootstrap cache)
cd /var/www/app038
git pull origin main

# Verify Dockerfile sudah terupdate
grep -A 5 "Clear bootstrap cache" docker/php/Dockerfile
# Should show: RUN rm -f bootstrap/cache/services.php ...
```

**2. Clear Bootstrap Cache di Container (Quick Fix):**

```bash
# Clear bootstrap cache files yang mengandung dev dependency references
docker exec app038_laravel rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php bootstrap/cache/routes*.php 2>/dev/null || true

# Atau jika container tidak bisa exec, stop dulu:
docker stop app038_laravel
docker run --rm -v $(pwd):/app -w /app app038_laravel:latest sh -c "rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php bootstrap/cache/routes*.php"

# Restart container
docker-compose -f docker-compose.prod.yml restart laravel

# Laravel akan regenerate cache files tanpa dev dependencies
```

**3. Rebuild Container dengan No Cache (Recommended):**

```bash
# Rebuild container (Dockerfile sudah diupdate untuk clear cache)
docker-compose -f docker-compose.prod.yml build --no-cache laravel

# Start container
docker-compose -f docker-compose.prod.yml up -d laravel

# Verify cache files tidak mengandung Collision
docker exec app038_laravel cat bootstrap/cache/services.php | grep -i collision || echo "No Collision references found (good!)"
```

**4. Manual Clear Cache (Jika Rebuild Tidak Mungkin):**

```bash
# SSH ke VPS
ssh root@168.231.118.3
cd /var/www/app038

# Stop container
docker stop app038_laravel

# Remove cache files
rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php bootstrap/cache/routes*.php

# Start container
docker-compose -f docker-compose.prod.yml up -d laravel

# Laravel akan regenerate cache files saat startup
```

**5. Verify Fix:**

```bash
# Check container logs (seharusnya tidak ada Collision error)
docker logs app038_laravel --tail 50 | grep -i collision || echo "No Collision errors (good!)"

# Try run migration
docker exec -it app038_laravel php artisan migrate --force

# Check cache files
docker exec app038_laravel ls -la bootstrap/cache/
# Files akan di-regenerate oleh Laravel tanpa dev dependencies
```

**6. Alternative: Run Migration Tanpa Container (Jika Masih Error):**

```bash
# Run migration menggunakan PHP image langsung
docker run --rm \
  --network app038_network \
  -v $(pwd):/app \
  -w /app \
  -e APP_ENV=production \
  -e DB_HOST=postgres \
  -e DB_PORT=5432 \
  -e DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2) \
  -e DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2) \
  -e DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2) \
  php:8.2-cli-alpine \
  sh -c "apk add --no-cache postgresql-dev && docker-php-ext-install pdo pdo_pgsql && rm -f bootstrap/cache/*.php && php artisan migrate --force"
```

**Prevention:**
- Dockerfile sudah diupdate untuk clear bootstrap cache saat build
- Pastikan pull latest changes sebelum rebuild
- Jangan commit `bootstrap/cache/` files ke repository (sudah di .gitignore)

**Catatan:** Dockerfile di repository sudah diupdate untuk clear bootstrap cache files yang mengandung dev dependency references. Pastikan pull latest changes sebelum rebuild.

**Prevention:**
- Pastikan semua environment variables sudah di-set sebelum start container
- Verify dependencies (postgres, redis, rabbitmq) running sebelum start Laravel
- Check logs setelah start container untuk early detection
- Use health checks untuk automatic recovery

### Issue: Docker Containers Tidak Start

```bash
# Check Docker service
sudo systemctl status docker
sudo systemctl restart docker

# Check disk space
df -h

# Check Docker logs
docker-compose -f docker-compose.prod.yml logs

# Check specific service logs
docker logs app038_laravel
docker logs app038_postgres
```

### Issue: Nginx 502 Bad Gateway

```bash
# Check Laravel container running
docker ps | grep laravel

# Check Nginx error log
sudo tail -f /var/log/nginx/app038-error.log

# Check container logs
docker logs app038_laravel --tail 50

# Restart Laravel container
docker-compose -f docker-compose.prod.yml restart laravel

# Check port 80 dari container
docker exec app038_laravel netstat -tulpn | grep 80
```

### Issue: SSL Certificate Tidak Terbit

```bash
# Check domain pointing ke VPS
dig yourdomain.com
# Output harus: 168.231.118.3

# Check firewall (port 80 dan 443 harus open)
sudo ufw status

# Check Nginx configuration
sudo nginx -t

# Manual certbot
sudo certbot certonly --standalone -d yourdomain.com

# Check certificate
sudo certbot certificates
```

### Issue: Database Connection Failed

**Error:** `SQLSTATE[08006] [7] connection to server at "localhost" (::1), port 5432 failed: Connection refused`

**Penyebab:**
- Laravel mencoba connect ke `localhost` bukan ke service name `postgres`
- Environment variable `DB_HOST` tidak di-set dengan benar di `.env` file
- Atau PostgreSQL container tidak running
- Atau network connectivity issue

**Solusi Step-by-Step:**

**1. Check PostgreSQL Container Status (PENTING - Lakukan ini dulu!):**

```bash
# Check apakah PostgreSQL container running
docker ps | grep app038_postgres

# Expected output: Harus show "Up" status
# Jika tidak ada atau status "Exited", start dulu:
docker-compose -f docker-compose.prod.yml up -d postgres

# Wait untuk PostgreSQL ready (10-30 detik)
sleep 15

# Check PostgreSQL logs
docker logs app038_postgres --tail 50

# Test PostgreSQL dari host
docker exec app038_postgres pg_isready -U postgres
# Expected: "postgres:5432 - accepting connections"
```

**2. Check Environment Variables di .env File:**

```bash
# Check DB_HOST di .env file
grep DB_HOST .env

# Expected: DB_HOST=postgres (bukan localhost atau 127.0.0.1)
# Jika salah, fix:
sed -i 's/DB_HOST=.*/DB_HOST=postgres/' .env

# Verify semua database variables
grep -E "DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD" .env

# Expected output:
# DB_HOST=postgres
# DB_PORT=5432
# DB_DATABASE=app038
# DB_USERNAME=postgres
# DB_PASSWORD=your_password_here
```

**3. Check Environment Variables di Container:**

```bash
# Check environment variables di Laravel container
docker exec app038_laravel env | grep DB_

# Expected output:
# DB_CONNECTION=pgsql
# DB_HOST=postgres
# DB_PORT=5432
# DB_DATABASE=app038
# DB_USERNAME=postgres
# DB_PASSWORD=your_password_here

# Jika DB_HOST masih "localhost", restart container:
docker-compose -f docker-compose.prod.yml restart laravel
```

**4. Test Network Connectivity:**

```bash
# Test ping dari Laravel container ke PostgreSQL
docker exec app038_laravel ping -c 3 postgres

# Expected: Harus bisa ping (3 packets transmitted, 3 received)
# Jika tidak bisa ping, check network:
docker network inspect app038_network

# Verify Laravel dan PostgreSQL di network yang sama
docker network inspect app038_network | grep -A 5 "app038_laravel\|app038_postgres"
```

**5. Test Database Connection Manual:**

```bash
# Test connection dari Laravel container
docker exec app038_laravel php artisan tinker

# Di dalam tinker, test connection:
# DB::connection()->getPdo();
# exit

# Atau test dengan psql dari Laravel container (jika psql installed):
docker exec app038_laravel sh -c "apk add --no-cache postgresql-client && psql -h postgres -U postgres -d app038 -c 'SELECT 1;'"
```

**6. Fix .env File (Jika DB_HOST Salah):**

```bash
# Edit .env file
nano .env

# Pastikan baris berikut ada dan benar:
# DB_CONNECTION=pgsql
# DB_HOST=postgres          # PENTING: harus "postgres" bukan "localhost"
# DB_PORT=5432
# DB_DATABASE=app038
# DB_USERNAME=postgres
# DB_PASSWORD=your_strong_password_here

# Save (Ctrl+X, Y, Enter)

# Restart Laravel container untuk load environment variables baru
docker-compose -f docker-compose.prod.yml restart laravel

# Verify environment variables sudah terupdate
docker exec app038_laravel env | grep DB_HOST
# Expected: DB_HOST=postgres
```

**7. Complete Reset (Jika Masih Error):**

```bash
# Stop semua containers
docker-compose -f docker-compose.prod.yml down

# Check .env file
cat .env | grep -E "DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD"

# Fix .env jika perlu
nano .env
# Pastikan:
# DB_HOST=postgres (bukan localhost)

# Start PostgreSQL dulu
docker-compose -f docker-compose.prod.yml up -d postgres

# Wait untuk PostgreSQL ready
sleep 20

# Check PostgreSQL ready
docker exec app038_postgres pg_isready -U postgres

# Start Laravel
docker-compose -f docker-compose.prod.yml up -d laravel

# Check logs
docker logs app038_laravel --tail 50

# Test migration
docker exec -it app038_laravel php artisan migrate --force
```

**8. Verify Database Credentials:**

```bash
# Test connection dengan credentials dari .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Test dari PostgreSQL container
docker exec app038_postgres psql -U $DB_USERNAME -d $DB_DATABASE -c "SELECT 1;"

# Jika error, mungkin credentials salah atau database belum dibuat
```

**9. Create Database (Jika Database Tidak Ada):**

```bash
# Connect ke PostgreSQL
docker exec -it app038_postgres psql -U postgres

# Di dalam psql:
# CREATE DATABASE app038;
# \q

# Atau dari command line:
docker exec app038_postgres psql -U postgres -c "CREATE DATABASE app038;"
```

### Issue: Password Authentication Failed for User "postgres"

**Error:** `FATAL: password authentication failed for user "postgres"`

**Penyebab:**
- `DB_PASSWORD` di `.env` file tidak sesuai dengan `POSTGRES_PASSWORD` yang digunakan saat postgres container dibuat
- Password di `.env` berbeda dengan password yang di-set di postgres container
- Postgres container dibuat dengan password berbeda, lalu `.env` di-update tapi container tidak di-recreate

**Solusi:**

**1. Check Password di .env File:**

```bash
# Check DB_PASSWORD di .env
cd /var/www/app038
grep DB_PASSWORD .env

# Check apakah password kosong atau tidak ada
# Jika kosong atau tidak ada, perlu di-set
```

**2. Check Password yang Digunakan oleh Postgres Container:**

```bash
# Check environment variables di postgres container
docker exec app038_postgres env | grep POSTGRES_PASSWORD

# Atau check dengan inspect
docker inspect app038_postgres | grep -A 10 "Env"
# Cari POSTGRES_PASSWORD di output
```

**3. Fix Password Mismatch - Opsi A: Update .env dan Recreate Postgres Container (Recommended):**

**⚠️ PENTING: Lakukan semua langkah ini secara berurutan!**

```bash
# Step 1: Navigate ke project directory
cd /var/www/app038

# Step 2: Check password saat ini di .env
echo "=== Current Password di .env ==="
grep "^DB_PASSWORD=" .env | cut -d '=' -f2
echo ""

# Step 3: Check password yang digunakan postgres container
echo "=== Password di Postgres Container ==="
docker exec app038_postgres env | grep POSTGRES_PASSWORD | cut -d '=' -f2
echo ""

# Step 4: Generate password baru yang kuat
NEW_DB_PASSWORD=$(openssl rand -base64 32)
echo "=== New Password Generated ==="
echo "${NEW_DB_PASSWORD}"
echo ""
echo "⚠️ SIMPAN PASSWORD INI! Anda akan membutuhkannya nanti."
echo ""

# Step 5: Update .env file dengan password baru
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_DB_PASSWORD}/" .env

# Step 6: Verify .env updated
echo "=== Verify .env Updated ==="
grep "^DB_PASSWORD=" .env
echo ""

# Step 7: Stop postgres container (PENTING: Backup data dulu jika ada data penting!)
echo "=== Stopping Postgres Container ==="
docker-compose -f docker-compose.prod.yml stop postgres

# Step 8: Remove postgres container (data di volume akan tetap ada)
echo "=== Removing Postgres Container ==="
docker-compose -f docker-compose.prod.yml rm -f postgres

# Step 9: Start postgres container lagi (akan menggunakan password baru dari .env)
echo "=== Starting Postgres Container dengan Password Baru ==="
docker-compose -f docker-compose.prod.yml up -d postgres

# Step 10: Wait untuk postgres ready (PENTING - jangan skip!)
echo "=== Waiting for Postgres Ready (15 seconds) ==="
sleep 15

# Step 11: Verify postgres ready
echo "=== Verifying Postgres Ready ==="
docker exec app038_postgres pg_isready -U postgres
# Expected: "postgres:5432 - accepting connections"
echo ""

# Step 12: Restart Laravel container untuk reload .env
echo "=== Restarting Laravel Container ==="
docker-compose -f docker-compose.prod.yml restart laravel

# Step 13: Wait untuk Laravel ready
echo "=== Waiting for Laravel Ready (10 seconds) ==="
sleep 10

# Step 14: Test connection
echo "=== Testing Database Connection ==="
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connection OK';"
# Expected: "Connection OK" (tidak ada error)
echo ""

# Step 15: Verify password sync
echo "=== Final Verification ==="
echo "DB_PASSWORD di .env:"
grep "^DB_PASSWORD=" .env | cut -d '=' -f2
echo ""
echo "POSTGRES_PASSWORD di container:"
docker exec app038_postgres env | grep POSTGRES_PASSWORD | cut -d '=' -f2
echo ""
echo "✅ Jika kedua password sama, fix berhasil!"
```

**Quick Fix Script (Copy-Paste Semua Sekaligus):**

```bash
# Copy semua script ini dan paste di terminal VPS
cd /var/www/app038 && \
NEW_DB_PASSWORD=$(openssl rand -base64 32) && \
echo "Generated Password: ${NEW_DB_PASSWORD}" && \
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_DB_PASSWORD}/" .env && \
echo "✅ .env updated" && \
docker-compose -f docker-compose.prod.yml stop postgres && \
docker-compose -f docker-compose.prod.yml rm -f postgres && \
docker-compose -f docker-compose.prod.yml up -d postgres && \
echo "⏳ Waiting for postgres..." && \
sleep 20 && \
docker exec app038_postgres pg_isready -U postgres && \
docker-compose -f docker-compose.prod.yml restart laravel && \
sleep 10 && \
echo "🧪 Testing connection..." && \
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo(); echo '✅ Connection OK';" && \
echo "✅ Fix completed! Password: ${NEW_DB_PASSWORD}"
```

**4. Fix Password Mismatch - Opsi B: Update Postgres Password di Database (Jika Ada Data Penting):**

```bash
# Step 1: Check password yang digunakan postgres container saat ini
docker exec app038_postgres env | grep POSTGRES_PASSWORD

# Step 2: Connect ke postgres dengan password yang benar (dari step 1)
# Atau jika tahu password, connect langsung:
docker exec -it app038_postgres psql -U postgres

# Step 3: Di dalam psql, update password:
# ALTER USER postgres WITH PASSWORD 'new_password_here';
# \q

# Step 4: Update .env file dengan password yang sama
nano .env
# Update DB_PASSWORD dengan password yang baru di-set

# Step 5: Restart Laravel container
docker-compose -f docker-compose.prod.yml restart laravel

# Step 6: Test connection
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connection OK';"
```

**5. Quick Fix - Verify dan Sync Password:**

```bash
# One-liner untuk check password mismatch
cd /var/www/app038
echo "=== Password Check ==="
echo "DB_PASSWORD di .env:"
grep "^DB_PASSWORD=" .env | cut -d '=' -f2
echo ""
echo "POSTGRES_PASSWORD di container:"
docker exec app038_postgres env | grep POSTGRES_PASSWORD | cut -d '=' -f2
echo ""
echo "=== Jika berbeda, gunakan Opsi A untuk fix ==="
```

**6. Prevention - Setup Password dengan Benar dari Awal:**

```bash
# Generate password yang kuat
DB_PASS=$(openssl rand -base64 32)
echo "Generated Password: ${DB_PASS}"

# Update .env file
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env

# Verify
grep DB_PASSWORD .env

# Pastikan password ini digunakan saat pertama kali create postgres container
# Jika postgres container sudah ada, gunakan Opsi A untuk recreate dengan password baru
```

**7. Verify Fix:**

```bash
# Test database connection
docker exec app038_laravel php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connection OK'; } catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }"

# Expected: "Connection OK" (tidak ada error)
# Jika masih error, check error message untuk detail lebih lanjut

# Test dengan psql langsung
docker exec app038_postgres psql -U postgres -d app038 -c "SELECT 1;"
# Expected: Output dengan "1" (tidak ada error)
```

**Common Mistakes:**
- ❌ Password di `.env` berbeda dengan password di postgres container
- ❌ Password kosong atau tidak di-set
- ❌ Postgres container dibuat dengan password default, lalu `.env` di-update tapi container tidak di-recreate
- ❌ Multiple `.env` files atau `.env` file di lokasi yang salah

**Prevention:**
- **Selalu generate password yang kuat** sebelum create postgres container
- **Set password di `.env` file** sebelum run `docker-compose up -d`
- **Jika update password di `.env`**, pastikan untuk **recreate postgres container** agar password sync
- **Backup database** sebelum recreate postgres container jika ada data penting

**Quick Diagnostic Commands:**

```bash
# One-liner untuk check semua database issues
echo "=== PostgreSQL Container ===" && \
docker ps | grep postgres && \
echo -e "\n=== Environment Variables (.env) ===" && \
grep -E "DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME" .env && \
echo -e "\n=== Environment Variables (Container) ===" && \
docker exec app038_laravel env | grep DB_ && \
echo -e "\n=== Network Connectivity ===" && \
docker exec app038_laravel ping -c 2 postgres && \
echo -e "\n=== PostgreSQL Ready ===" && \
docker exec app038_postgres pg_isready -U postgres
```

**Common Mistakes:**
- ❌ `DB_HOST=localhost` → ✅ `DB_HOST=postgres`
- ❌ `DB_HOST=127.0.0.1` → ✅ `DB_HOST=postgres`
- ❌ PostgreSQL container tidak running
- ❌ Network tidak sama antara Laravel dan PostgreSQL containers
- ❌ Environment variables tidak di-pass ke container

**10. Step-by-Step Fix untuk Error "connection to localhost" (Lakukan Semua Langkah Ini Secara Berurutan!):**

Jika masih mendapatkan error "connection to localhost" setelah fix .env, ikuti langkah-langkah berikut **secara berurutan**:

```bash
# Step 1: Navigate ke project directory
cd /var/www/app038

# Step 2: Check DB_HOST di .env file
grep DB_HOST .env
# Jika output: DB_HOST=localhost atau DB_HOST=127.0.0.1 atau tidak ada → LANJUT KE STEP 3

# Step 3: Fix DB_HOST di .env file (PENTING!)
sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' .env
# Atau jika baris DB_HOST tidak ada sama sekali:
if ! grep -q "^DB_HOST=" .env; then
    echo "DB_HOST=postgres" >> .env
fi

# Step 4: Verify fix
grep DB_HOST .env
# Expected output: DB_HOST=postgres

# Step 5: Check apakah PostgreSQL container running
docker ps | grep app038_postgres
# Jika tidak ada atau status "Exited", start:
docker-compose -f docker-compose.prod.yml up -d postgres

# Step 6: Wait untuk PostgreSQL ready (PENTING - jangan skip!)
sleep 20

# Step 7: Verify PostgreSQL ready
docker exec app038_postgres pg_isready -U postgres
# Expected: "postgres:5432 - accepting connections"
# Jika error, wait lebih lama dan check logs: docker logs app038_postgres

# Step 8: Stop Laravel container (untuk force reload environment variables)
docker-compose -f docker-compose.prod.yml stop laravel

# Step 9: Start Laravel container lagi (akan load .env baru)
docker-compose -f docker-compose.prod.yml up -d laravel

# Step 10: Wait untuk Laravel container ready
sleep 10

# Step 11: Verify environment variables di container (PENTING!)
docker exec app038_laravel env | grep DB_HOST
# Expected: DB_HOST=postgres (BUKAN localhost!)
# Jika masih "localhost", ada masalah dengan .env file atau container tidak reload

# Step 12: Test network connectivity
docker exec app038_laravel ping -c 3 postgres
# Expected: 3 packets transmitted, 3 received
# Jika tidak bisa ping, check network: docker network inspect app038_network

# Step 13: Test database connection dengan tinker
docker exec app038_laravel php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connection OK'; } catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }"
# Expected: "Connection OK" (tidak ada error)
# Jika masih error, check error message untuk detail

# Step 14: Run migration (setelah semua step di atas berhasil)
docker exec -it app038_laravel php artisan migrate --force
```

**Jika masih error setelah semua langkah di atas, lakukan complete reset:**

```bash
# Complete reset: Stop semua, fix .env, start lagi
cd /var/www/app038

# Stop semua containers
docker-compose -f docker-compose.prod.yml down

# Fix .env file (pastikan semua service names benar)
sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' .env
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env
sed -i 's/^RABBITMQ_HOST=.*/RABBITMQ_HOST=rabbitmq/' .env

# Verify .env
cat .env | grep -E "DB_HOST|REDIS_HOST|RABBITMQ_HOST"
# Expected:
# DB_HOST=postgres
# REDIS_HOST=redis
# RABBITMQ_HOST=rabbitmq

# Start dependencies dulu
docker-compose -f docker-compose.prod.yml up -d postgres redis rabbitmq

# Wait untuk dependencies ready (PENTING!)
sleep 30

# Verify dependencies ready
docker exec app038_postgres pg_isready -U postgres
docker exec app038_redis redis-cli ping

# Start Laravel
docker-compose -f docker-compose.prod.yml up -d laravel

# Wait untuk Laravel ready
sleep 15

# Verify environment variables di container
docker exec app038_laravel env | grep -E "DB_HOST|REDIS_HOST|RABBITMQ_HOST"
# Expected semua harus service names, bukan localhost

# Run migration
docker exec -it app038_laravel php artisan migrate --force
```

**Troubleshooting jika DB_HOST masih "localhost" di container:**

```bash
# Check apakah .env file benar-benar ter-update
cat .env | grep DB_HOST

# Check apakah ada multiple DB_HOST entries
grep -n DB_HOST .env
# Jika ada multiple, hapus yang salah dan keep yang benar

# Force recreate container dengan environment variables baru
docker-compose -f docker-compose.prod.yml up -d --force-recreate laravel

# Atau stop dan remove container, lalu start lagi
docker-compose -f docker-compose.prod.yml stop laravel
docker-compose -f docker-compose.prod.yml rm -f laravel
docker-compose -f docker-compose.prod.yml up -d laravel

# Verify lagi
docker exec app038_laravel env | grep DB_HOST
```

**Prevention:**
- Pastikan `.env` file menggunakan service names (postgres, redis, rabbitmq) bukan localhost
- Verify semua containers running sebelum run migration
- Check network connectivity sebelum troubleshooting lebih lanjut
- **Selalu verify environment variables di container setelah restart** dengan: `docker exec app038_laravel env | grep DB_HOST`

### Issue: Konfigurasi .env Salah untuk Production

**Error:** Konfigurasi `.env` masih menggunakan development values (APP_ENV=local, APP_URL=http://localhost:8000)

**Cek Konfigurasi .env yang Benar untuk Production:**

```bash
# Check current .env values
cd /var/www/app038
grep -E "APP_ENV|APP_URL|APP_DEBUG" .env
```

**Expected Values untuk Production:**
- ✅ `APP_ENV=production` (BUKAN `local`)
- ✅ `APP_DEBUG=false` (BUKAN `true`)
- ✅ `APP_URL=http://168.231.118.3` atau `APP_URL=https://yourdomain.com` (BUKAN `http://localhost:8000`)

**Fix Konfigurasi .env:**

```bash
# Navigate ke project directory
cd /var/www/app038

# Fix APP_ENV
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
# Atau jika baris tidak ada:
if ! grep -q "^APP_ENV=" .env; then
    echo "APP_ENV=production" >> .env
fi

# Fix APP_DEBUG
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
# Atau jika baris tidak ada:
if ! grep -q "^APP_DEBUG=" .env; then
    echo "APP_DEBUG=false" >> .env
fi

# Fix APP_URL (gunakan IP VPS atau domain Anda)
sed -i 's|^APP_URL=.*|APP_URL=http://168.231.118.3|' .env
# Atau jika punya domain:
# sed -i 's|^APP_URL=.*|APP_URL=https://yourdomain.com|' .env
# Atau jika baris tidak ada:
if ! grep -q "^APP_URL=" .env; then
    echo "APP_URL=http://168.231.118.3" >> .env
fi

# Verify fixes
echo "=== Verifikasi Konfigurasi .env ==="
grep -E "APP_ENV|APP_URL|APP_DEBUG" .env
echo ""
echo "Expected output:"
echo "APP_ENV=production"
echo "APP_DEBUG=false"
echo "APP_URL=http://168.231.118.3"
```

**Setelah Fix, Restart Container:**

```bash
# Stop Laravel container
docker-compose -f docker-compose.prod.yml stop laravel

# Start Laravel container lagi (akan load .env baru)
docker-compose -f docker-compose.prod.yml up -d laravel

# Wait untuk container ready
sleep 10

# Verify environment variables di container
docker exec app038_laravel env | grep -E "APP_ENV|APP_URL|APP_DEBUG"
# Expected:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=http://168.231.118.3
```

**Complete .env Template untuk Production (VPS Hostinger):**

```bash
# Buat backup .env lama
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Create .env baru dengan konfigurasi production
cat > .env << 'EOF'
# Application Configuration
APP_NAME=App038
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://168.231.118.3
# Atau jika punya domain: APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=info

# Database Configuration (PostgreSQL)
# PENTING: DB_HOST harus "postgres" (service name), BUKAN "localhost"
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password_here

# Redis Configuration
# PENTING: REDIS_HOST harus "redis" (service name), BUKAN "localhost"
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password_here
REDIS_DB=0

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration
QUEUE_CONNECTION=rabbitmq

# RabbitMQ Configuration
# PENTING: RABBITMQ_HOST harus "rabbitmq" (service name), BUKAN "localhost"
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=your_rabbitmq_password_here
RABBITMQ_VHOST=/

# Mail Configuration (sesuaikan dengan provider email Anda)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration (jika menggunakan API)
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,168.231.118.3
EOF

# Generate APP_KEY jika belum ada
if ! grep -q "^APP_KEY=base64:" .env; then
    APP_KEY_VALUE=$(openssl rand -base64 32)
    sed -i "s/^APP_KEY=.*/APP_KEY=base64:${APP_KEY_VALUE}/" .env
fi

# Verify .env
echo "=== Verifikasi .env File ==="
grep -E "APP_ENV|APP_URL|APP_DEBUG|DB_HOST|REDIS_HOST|RABBITMQ_HOST" .env
```

**Common Mistakes:**
- ❌ `APP_ENV=local` → ✅ `APP_ENV=production`
- ❌ `APP_DEBUG=true` → ✅ `APP_DEBUG=false`
- ❌ `APP_URL=http://localhost:8000` → ✅ `APP_URL=http://168.231.118.3` atau domain Anda
- ❌ `DB_HOST=localhost` → ✅ `DB_HOST=postgres`
- ❌ `REDIS_HOST=localhost` → ✅ `REDIS_HOST=redis`
- ❌ `RABBITMQ_HOST=localhost` → ✅ `RABBITMQ_HOST=rabbitmq`

### Issue: High Memory/CPU Usage

```bash
# Check resource usage
docker stats --no-stream

# Check system resources
free -h
top

# Identify container dengan high memory
# Restart container jika perlu
docker restart app038_laravel

# Check for memory leaks
docker logs app038_laravel | grep -i "memory\|fatal"
```

### Issue: Disk Space Full

```bash
# Check disk usage
df -h

# Clean Docker unused resources
docker system prune -a --volumes

# Clean old logs
sudo journalctl --vacuum-time=7d

# Remove old backups
find /var/backups/app038 -type f -mtime +30 -delete
```

---

## Checklist Deployment

### Pre-Deployment
- [x] ✅ VPS Hostinger sudah aktif dengan Ubuntu 24.04 LTS (Terverifikasi: srv1162366.hstgr.cloud)
- [x] ✅ VPS IP address sudah dicatat: `168.231.118.3`
- [ ] SSH access ke VPS sudah berfungsi (fix host key issue jika perlu: `ssh-keygen -R 168.231.118.3`)
- [ ] Domain name sudah terdaftar (jika menggunakan custom domain)
- [ ] DNS provider access sudah tersedia

### System Setup
- [ ] System sudah di-update (`apt update && apt upgrade`)
- [ ] Docker sudah terinstall dan running
- [ ] Docker Compose sudah terinstall
- [ ] Firewall (UFW) sudah dikonfigurasi (allow: 22, 80, 443)
- [ ] Port 80 dan 443 sudah tersedia

### Application Setup
- [ ] Personal Access Token (PAT) sudah dibuat di GitHub (jika menggunakan HTTPS)
- [ ] SSH key sudah ditambahkan ke GitHub (jika menggunakan SSH)
- [ ] Repository sudah di-clone ke `/var/www/app038`
- [ ] `.env` file sudah dibuat (dari .env.example atau template manual)
- [ ] `.env` file sudah dikonfigurasi dengan benar
- [ ] APP_KEY sudah di-generate
- [ ] Database password sudah di-generate dan di-set
- [ ] Redis password sudah di-generate dan di-set
- [ ] RabbitMQ password sudah di-generate dan di-set

### Docker Services
- [ ] Docker network `app038_network` sudah dibuat
- [ ] Docker images sudah di-build (laravel, svelte) - **Jika build error, lihat troubleshooting di Step 6.1**
- [ ] Docker containers sudah running (laravel, svelte, postgres, redis, rabbitmq)
- [ ] RabbitMQ container sudah ditambahkan di docker-compose.prod.yml (sudah diupdate)
- [ ] No critical errors di container logs
- [ ] All containers healthy (check dengan `docker ps`)

### Database
- [ ] Database migrations sudah dijalankan (`php artisan migrate --force`)
- [ ] Database seeders sudah dijalankan (jika diperlukan)
- [ ] Database connection sudah terverifikasi

### Nginx & SSL
- [ ] Nginx sudah terinstall
- [ ] Nginx configuration sudah dibuat
- [ ] Nginx site sudah di-enable
- [ ] Nginx configuration test passed (`nginx -t`)
- [ ] SSL certificate sudah terbit (jika menggunakan domain)
- [ ] SSL auto-renewal sudah dikonfigurasi

### DNS
- [ ] DNS A records sudah dibuat di DNS provider
- [ ] DNS sudah pointing ke VPS IP: `168.231.118.3`
- [ ] DNS propagation sudah selesai (verified dengan `dig` atau `nslookup`)

### Verification
- [ ] Website sudah bisa diakses via HTTPS: `https://yourdomain.com` (atau `http://168.231.118.3` jika belum setup domain)
- [ ] Health check endpoint berfungsi: `https://yourdomain.com/health` (atau `http://168.231.118.3/health`)
- [ ] SSL certificate valid (check di browser atau dengan `openssl s_client`)
- [ ] Application functionality tested (login, navigation, dll)
- [ ] All containers running (check dengan `docker ps` - harus ada: laravel, svelte, postgres, redis, rabbitmq)
- [ ] Nginx reverse proxy berfungsi (check dengan `curl -I http://168.231.118.3`)
- [ ] Database connection working (test dengan `php artisan tinker`)
- [ ] Redis cache working (test dengan `php artisan tinker` → `Cache::put('test', 'value', 60)`)

### Post-Deployment
- [ ] Auto-start on boot sudah dikonfigurasi (systemd service)
- [ ] Automated backups sudah setup (database & storage)
- [ ] Queue workers sudah setup (jika diperlukan)
- [ ] Scheduled tasks (cron) sudah setup (jika diperlukan)
- [ ] Monitoring sudah dikonfigurasi

**✅ Selesai!** Website sudah online di `https://yourdomain.com` atau `http://168.231.118.3`

---

## Catatan Penting

### Perbedaan dengan Dokploy Deployment

**Deployment Manual (File ini):**
- ✅ Full control atas semua konfigurasi
- ✅ Tidak perlu install Dokploy
- ✅ Setup Nginx manual
- ✅ Setup SSL manual dengan Certbot
- ⚠️ Lebih banyak langkah manual
- ⚠️ Tidak ada web UI untuk management

**Deployment dengan Dokploy:**
- ✅ Web UI untuk management
- ✅ Auto SSL dengan Traefik
- ✅ Git integration untuk auto-deploy
- ✅ Built-in monitoring
- ⚠️ Perlu install Dokploy terlebih dahulu

**Rekomendasi:** 
- Jika ingin kontrol penuh dan belajar setup manual → Gunakan panduan ini
- Jika ingin setup cepat dengan web UI → Gunakan `DEPLOYMENT_GUIDE.md` section "Opsi 0A: VPS Hostinger dengan Dokploy"

### Update docker-compose.prod.yml untuk RabbitMQ

**PENTING:** File `docker-compose.prod.yml` saat ini belum include RabbitMQ. Sebelum deployment, pastikan untuk menambahkan service RabbitMQ (lihat Post-Deployment section Step 1).

Atau update file `docker-compose.prod.yml` dengan menambahkan:

```yaml
  # RabbitMQ Message Queue
  rabbitmq:
    image: rabbitmq:3-management-alpine
    container_name: app038_rabbitmq
    restart: unless-stopped
    environment:
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
      RABBITMQ_DEFAULT_VHOST: /
    volumes:
      - rabbitmq_data:/var/lib/rabbitmq
    networks:
      - app038_network
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "ping"]
      interval: 30s
      timeout: 10s
      retries: 5
```

Dan tambahkan volume `rabbitmq_data` di section volumes:

```yaml
volumes:
  postgres_data:
  redis_data:
  rabbitmq_data:  # Tambahkan ini
```

**Setelah update docker-compose.prod.yml (jika diperlukan), restart services:**

```bash
cd /var/www/app038
docker-compose -f docker-compose.prod.yml up -d
```

**Catatan:** File `docker-compose.prod.yml` sudah diupdate dengan RabbitMQ, jadi jika Anda menggunakan file yang sudah diupdate, RabbitMQ akan otomatis running setelah `docker-compose up -d`.

---

## Quick Reference Commands

### Docker Compose Commands

```bash
# Start all services
cd /var/www/app038
docker-compose -f docker-compose.prod.yml up -d

# Stop all services
docker-compose -f docker-compose.prod.yml down

# View logs
docker-compose -f docker-compose.prod.yml logs -f

# View logs for specific service
docker-compose -f docker-compose.prod.yml logs -f laravel
docker-compose -f docker-compose.prod.yml logs -f postgres
docker-compose -f docker-compose.prod.yml logs -f redis

# Restart service
docker-compose -f docker-compose.prod.yml restart laravel

# Rebuild and restart
docker-compose -f docker-compose.prod.yml up -d --build --force-recreate

# Check status
docker-compose -f docker-compose.prod.yml ps
```

### Container Commands

```bash
# Execute command in container
docker exec -it app038_laravel php artisan migrate
docker exec -it app038_laravel php artisan tinker
docker exec -it app038_laravel bash

# Check container logs
docker logs app038_laravel --tail 50
docker logs app038_svelte --tail 50

# Check container stats
docker stats --no-stream
```

### Nginx Commands

```bash
# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# Check Nginx status
sudo systemctl status nginx

# View Nginx logs
sudo tail -f /var/log/nginx/app038-error.log
sudo tail -f /var/log/nginx/app038-access.log
```

### SSL Commands

```bash
# Check certificates
sudo certbot certificates

# Renew certificates manually
sudo certbot renew

# Test auto-renewal
sudo certbot renew --dry-run
```

### Database Commands

```bash
# Backup database
docker exec app038_postgres pg_dump -U postgres app038 > backup_$(date +%Y%m%d).sql

# Restore database
docker exec -i app038_postgres psql -U postgres app038 < backup_20240101.sql

# Connect to database
docker exec -it app038_postgres psql -U postgres -d app038
```

### System Commands

```bash
# Check resource usage
free -h      # RAM
df -h        # Disk
htop         # CPU & Memory

# Check Docker disk usage
docker system df

# Clean Docker unused resources
docker system prune
```

---

## Support & Resources

- **Hostinger Support:** https://www.hostinger.com/contact
- **Hostinger hPanel:** https://hpanel.hostinger.com
- **Hostinger Knowledge Base:** https://support.hostinger.com
- **Docker Documentation:** https://docs.docker.com
- **Docker Compose Documentation:** https://docs.docker.com/compose/
- **Nginx Documentation:** https://nginx.org/en/docs/
- **Let's Encrypt Documentation:** https://letsencrypt.org/docs/
- **Laravel Documentation:** https://laravel.com/docs
- **PostgreSQL Documentation:** https://www.postgresql.org/docs/
- **Redis Documentation:** https://redis.io/documentation
- **RabbitMQ Documentation:** https://www.rabbitmq.com/documentation.html

---

## Quick Start Summary

**Untuk VPS Anda (168.231.118.3):**

```bash
# 1. Connect ke VPS
ssh-keygen -R 168.231.118.3
ssh root@168.231.118.3

# 2. Install Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 3. Setup Firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# 4. Clone Repository
cd /var/www

# Opsi A: Clone dengan Personal Access Token
git clone https://YOUR_PERSONAL_ACCESS_TOKEN@github.com/rdeeanz/app038.git
cd app038

# Opsi B: Clone dengan SSH (setup SSH key terlebih dahulu)
# ssh-keygen -t ed25519 -C "vps-deployment"
# Copy ~/.ssh/id_ed25519.pub ke GitHub Settings → SSH keys
# git clone git@github.com:rdeeanz/app038.git
# cd app038

# 5. Setup Environment
# Copy .env.example ke .env
if [ -f .env.example ]; then
    cp .env.example .env
    echo "✅ .env created from .env.example"
else
    echo "⚠️ .env.example not found, creating .env from template"
    # Create .env (see Step 4.1 for full template)
fi

# Generate APP_KEY manual (tidak perlu PHP/Composer)
APP_KEY_VALUE=$(openssl rand -base64 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env

# Generate passwords
DB_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
REDIS_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
RABBITMQ_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Update .env dengan passwords
sed -i "s/DB_PASSWORD=$/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/REDIS_PASSWORD=$/REDIS_PASSWORD=${REDIS_PASS}/" .env
sed -i "s/RABBITMQ_PASSWORD=$/RABBITMQ_PASSWORD=${RABBITMQ_PASS}/" .env

# Update APP_URL (ganti dengan domain atau IP)
read -p "Enter domain (or press Enter for IP 168.231.118.3): " DOMAIN
if [ -z "$DOMAIN" ]; then
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=http://168.231.118.3|" .env
else
    sed -i "s|APP_URL=https://yourdomain.com|APP_URL=https://$DOMAIN|" .env
fi

# 6. Install Node.js 20.x (for Vite build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 7. Build Vite assets
npm install
npm run build

# 8. Create Network & Start Services
docker network create app038_network 2>/dev/null || true
docker-compose -f docker-compose.prod.yml build --no-cache laravel
docker-compose -f docker-compose.prod.yml up -d

# 9. Wait for containers
echo "⏳ Waiting 30 seconds for containers to start..."
sleep 30

# 10. Setup Laravel
docker exec app038_laravel php artisan key:generate --force
docker exec app038_laravel php artisan migrate --force
docker exec app038_laravel php artisan config:clear
docker exec app038_laravel php artisan cache:clear
docker exec app038_laravel php artisan config:cache
docker exec app038_laravel php artisan route:cache

# 11. Setup Nginx
sudo apt install nginx -y
sudo tee /etc/nginx/sites-available/app038 > /dev/null << 'NGINXEOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    location /health {
        proxy_pass http://127.0.0.1:8080/health;
        access_log off;
    }
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

# 12. Setup SSL (jika punya domain)
read -p "Setup SSL? (y/n): " SETUP_SSL
if [ "$SETUP_SSL" = "y" ]; then
    sudo apt install certbot python3-certbot-nginx -y
    read -p "Enter domain name: " DOMAIN_NAME
    sudo certbot --nginx -d $DOMAIN_NAME -d www.$DOMAIN_NAME
fi

# 13. Verify
echo "=== Container Status ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038
echo ""
echo "=== Health Check ==="
curl -s http://localhost:8080/health
echo ""
echo "=== Test via Nginx ==="
curl -I http://localhost/health
echo ""
echo "✅ Test: http://168.231.118.3"
```

**✅ Website sudah online!**

---

**Catatan:** File ini adalah panduan lengkap untuk deployment manual ke VPS Hostinger tanpa menggunakan Dokploy. 

**Perbandingan:**

| Aspek | Manual (File ini) | Dokploy |
|-------|------------------|---------|
| Setup Time | 1-2 jam | 30-60 menit |
| Web UI | ❌ Tidak ada | ✅ Ada |
| Auto SSL | ⚠️ Manual (Certbot) | ✅ Auto (Traefik) |
| Git Integration | ⚠️ Manual | ✅ Auto-deploy |
| Control | ✅ Full control | ⚠️ Terbatas ke Dokploy |
| Learning | ✅ Belajar lebih banyak | ⚠️ Kurang belajar |

**Rekomendasi:**
- **Untuk belajar dan full control:** Gunakan panduan ini (Manual Deployment)
- **Untuk setup cepat dan mudah:** Gunakan `DEPLOYMENT_GUIDE.md` section "Opsi 0A: VPS Hostinger dengan Dokploy"

**Quick Access untuk VPS Anda:**
- **SSH:** `ssh root@168.231.118.3`
- **Website (setelah deploy):** `http://168.231.118.3` atau `https://yourdomain.com`
- **Health Check:** `http://168.231.118.3/health`
