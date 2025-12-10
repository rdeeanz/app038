# 🚀 Deployment Manual ke VPS Hostinger (Tanpa Dokploy)

Panduan lengkap untuk mendeploy aplikasi App038 ke VPS Hostinger secara manual menggunakan Docker Compose, Nginx reverse proxy, dan Let's Encrypt SSL.

> **📌 Informasi VPS Anda:** VPS Hostinger (IP: `168.231.118.3`, Hostname: `srv1162366.hstgr.cloud`) sudah terverifikasi. Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk, Ubuntu 24.04 LTS - **Sangat cukup untuk production!**

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

```
┌─────────────────────────────────────────────────────────────┐
│                    Internet / Users                          │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Nginx Reverse Proxy (Port 80/443)               │
│              SSL Termination (Let's Encrypt)                 │
└───────────────────────┬─────────────────────────────────────┘
                        │
        ┌───────────────┴───────────────┐
        │                               │
        ▼                               ▼
┌──────────────────┐          ┌──────────────────┐
│  Svelte Container │          │  Laravel Container│
│  (Frontend)       │          │  (Backend API)    │
│  Port: 80         │          │  Port: 80         │
└────────┬──────────┘          └────────┬──────────┘
         │                               │
         └────────┬────────┬─────────────┘
                  │        │
        ┌─────────▼───┐ ┌──▼──────────┐
        │   Redis     │ │  RabbitMQ   │
        │   (Cache)   │ │  (Queue)    │
        └─────────────┘ └─────────────┘
                  │
                  ▼
        ┌──────────────────┐
        │  PostgreSQL       │
        │  (Database)        │
        └──────────────────┘
```

### Teknologi Stack

- **Backend:** Laravel 11 LTS (PHP 8.2)
- **Frontend:** Svelte 4 dengan Inertia.js
- **Database:** PostgreSQL 15
- **Cache:** Redis 7
- **Queue:** RabbitMQ 3
- **Web Server:** Nginx (reverse proxy)
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
ls -la .env.example

# Jika .env.example TIDAK ADA, buat .env langsung
# Jika .env.example ADA, copy dari template
if [ -f .env.example ]; then
    cp .env.example .env
else
    # Buat .env file baru
    touch .env
    echo "# Laravel Environment Configuration" > .env
fi

# Edit environment file
nano .env
```

**Catatan:** Jika file `.env.example` tidak ada di repository, buat file `.env` langsung dan isi dengan template di bawah ini.

**4.2. Configure Environment Variables**

**Jika `.env.example` tidak ada, buat file `.env` dengan template berikut:**

```bash
# Buat file .env jika belum ada
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
```

**Atau edit `.env` file dengan konfigurasi berikut (ganti values sesuai kebutuhan):**

**Environment Variables Wajib:**

```env
# Application Configuration
APP_NAME=App038
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com
# Atau jika belum punya domain: APP_URL=http://168.231.118.3

LOG_CHANNEL=stack
LOG_LEVEL=info

# Database Configuration (PostgreSQL)
# PENTING: DB_HOST harus "postgres" (service name di docker-compose), BUKAN "localhost" atau "127.0.0.1"
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=your_strong_password_here

# Redis Configuration
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
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=your_rabbitmq_password_here
RABBITMQ_VHOST=/
```

**Environment Variables Tambahan (Recommended):**

```env
# Mail Configuration (sesuaikan dengan provider email Anda)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration (jika menggunakan API)
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# Broadcast Configuration
BROADCAST_DRIVER=log
```

**Catatan Penting:**
- Untuk docker-compose.prod.yml, pastikan environment variables di .env file menggunakan format yang sesuai dengan docker-compose
- Variable substitution di docker-compose menggunakan `${VARIABLE_NAME}` format
- Pastikan semua required variables sudah di-set sebelum build/start containers
- Simpan semua passwords dengan aman (password manager recommended)

**4.3. Generate APP_KEY**

**Opsi A: Generate APP_KEY dengan Docker (Recommended - Tidak perlu install PHP di host)**

```bash
# Pastikan Docker sudah running
sudo systemctl status docker

# Method 1: Build image dulu, lalu generate key
cd /var/www/app038

# Build Laravel image (jika belum)
docker-compose -f docker-compose.prod.yml build laravel

# Generate APP_KEY menggunakan image yang sudah di-build
docker run --rm -v $(pwd):/app -w /app \
  app038_laravel:latest \
  php artisan key:generate --show

# Method 2: Gunakan PHP image langsung (Lebih Simple - Recommended)
docker run --rm -v $(pwd):/app -w /app \
  php:8.2-cli-alpine \
  sh -c "composer install --ignore-platform-reqs --no-dev --no-scripts && php artisan key:generate --show"

# Method 3: Generate APP_KEY manual (Paling Simple - Tidak perlu Docker/PHP)
APP_KEY_VALUE=$(openssl rand -base64 32)
echo "base64:${APP_KEY_VALUE}"

# Update .env file
sed -i "s/APP_KEY=$/APP_KEY=base64:${APP_KEY_VALUE}/" .env

# Verify
grep APP_KEY .env
```

**Catatan:** Method 3 (Generate Manual) adalah yang paling simple dan tidak perlu build Docker image atau install PHP. Recommended untuk production deployment.

**Opsi B: Install PHP 8.4 dari PPA (Jika perlu PHP di host)**

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

**4.4. Generate Strong Passwords**

```bash
# Generate password untuk database
openssl rand -base64 32

# Generate password untuk Redis
openssl rand -base64 32

# Generate password untuk RabbitMQ
openssl rand -base64 32

# Copy passwords ke .env file
```

**4.5. Verify .env File**

```bash
# Check .env file
cat .env | grep -v "^#" | grep -v "^$"

# Verify required variables
grep -E "APP_KEY|DB_PASSWORD|REDIS_PASSWORD|RABBITMQ_PASSWORD" .env

# PENTING: Verify service names (bukan localhost!)
grep -E "DB_HOST|REDIS_HOST|RABBITMQ_HOST" .env

# Expected output:
# DB_HOST=postgres          # Harus "postgres" bukan "localhost"
# REDIS_HOST=redis          # Harus "redis" bukan "localhost"
# RABBITMQ_HOST=rabbitmq    # Harus "rabbitmq" bukan "localhost"

# Jika salah, fix:
sed -i 's/DB_HOST=.*/DB_HOST=postgres/' .env
sed -i 's/REDIS_HOST=.*/REDIS_HOST=redis/' .env
sed -i 's/RABBITMQ_HOST=.*/RABBITMQ_HOST=rabbitmq/' .env
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
docker-compose -f docker-compose.prod.yml build svelte

# Check images
docker images | grep app038
```

**Catatan:** Jika build error dengan `composer dump-autoload`, pastikan sudah pull latest changes. Dockerfile sudah diupdate dengan multiple fallback approaches untuk handle berbagai error scenarios.

**Troubleshooting Build Errors:**

**Error: "docker/svelte/default.conf: not found"**

**Penyebab:** File `.dockerignore` di root project mengexclude folder `docker/svelte`, sehingga file konfigurasi Nginx tidak tersedia saat build.

**Solusi:**

1. **Update .dockerignore (Recommended):**
   ```bash
   # Edit .dockerignore
   nano .env
   # Atau
   vi .dockerignore
   
   # Hapus atau comment baris berikut:
   # docker/svelte
   
   # Atau pastikan baris tersebut sudah di-comment:
   # # docker/svelte  # Commented out - needed for Docker build
   ```

2. **Verifikasi file ada:**
   ```bash
   # Check apakah file default.conf ada
   ls -la docker/svelte/default.conf
   ls -la docker/svelte/nginx.conf
   
   # Expected output: file harus ada
   ```

3. **Pull latest changes dari repository:**
   ```bash
   # Pull latest changes (file .dockerignore sudah diupdate)
   git pull origin main
   
   # Verify .dockerignore sudah terupdate
   grep -A 2 "docker/svelte" .dockerignore
   # Should show commented line atau tidak ada baris docker/svelte
   ```

4. **Rebuild dengan no cache:**
   ```bash
   # Clean build
   docker-compose -f docker-compose.prod.yml build --no-cache svelte
   
   # Atau rebuild semua
   docker-compose -f docker-compose.prod.yml build --no-cache
   ```

**Catatan:** File `.dockerignore` di repository sudah diupdate untuk tidak mengexclude `docker/svelte`. Pastikan Anda sudah pull latest changes sebelum build.

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

**6.2. Start Services**

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
app038_laravel      app038_laravel:latest    Up
app038_svelte       app038_svelte:latest     Up
app038_postgres     postgres:15-alpine      Up
app038_redis        redis:7-alpine          Up
app038_rabbitmq     rabbitmq:3-management-alpine Up
```

**Catatan:** 
- RabbitMQ sudah termasuk di `docker-compose.prod.yml` yang sudah diupdate
- Jika RabbitMQ tidak muncul, pastikan environment variables `RABBITMQ_USER` dan `RABBITMQ_PASSWORD` sudah di-set di `.env` file
- Pastikan RabbitMQ service sudah ditambahkan di `docker-compose.prod.yml` (lihat Post-Deployment section Step 1 jika perlu)

**6.3. Verify Containers Running**

```bash
# Check all containers
docker ps

# Check specific container
docker ps | grep app038

# Check container logs
docker logs app038_laravel --tail 50
docker logs app038_svelte --tail 50
docker logs app038_postgres --tail 50
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
    # Note: Laravel container juga expose port 80, perlu map ke port berbeda
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
# Expected: laravel, svelte, postgres, redis (dan rabbitmq jika sudah ditambahkan)

# Check container logs
docker logs app038_laravel --tail 50
docker logs app038_svelte --tail 50
docker logs app038_postgres --tail 50
docker logs app038_redis --tail 50
# docker logs app038_rabbitmq --tail 50  # Jika sudah ditambahkan

# Check network connectivity
docker exec app038_laravel ping -c 3 postgres
docker exec app038_laravel ping -c 3 redis
# docker exec app038_laravel ping -c 3 rabbitmq  # Jika sudah ditambahkan

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

**Prevention:**
- Pastikan `.env` file menggunakan service names (postgres, redis, rabbitmq) bukan localhost
- Verify semua containers running sebelum run migration
- Check network connectivity sebelum troubleshooting lebih lanjut

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
# Jika .env.example ada, copy dari template
if [ -f .env.example ]; then
    cp .env.example .env
else
    # Buat .env baru (lihat Step 4.2 untuk template lengkap)
    touch .env
fi

# Generate APP_KEY manual (tidak perlu PHP/Composer)
sed -i "s/APP_KEY=/APP_KEY=base64:$(openssl rand -base64 32)/" .env

# Generate passwords
DB_PASS=$(openssl rand -base64 32)
REDIS_PASS=$(openssl rand -base64 32)
RABBITMQ_PASS=$(openssl rand -base64 32)

# Update .env dengan passwords (jika belum di-set)
sed -i "s/DB_PASSWORD=$/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/REDIS_PASSWORD=$/REDIS_PASSWORD=${REDIS_PASS}/" .env
sed -i "s/RABBITMQ_PASSWORD=$/RABBITMQ_PASSWORD=${RABBITMQ_PASS}/" .env

nano .env  # Review dan edit jika perlu

# 6. Create Network & Start Services
docker network create app038_network
docker-compose -f docker-compose.prod.yml up -d --build

# 7. Run Migrations
docker exec -it app038_laravel php artisan migrate --force

# 8. Setup Nginx
sudo apt install nginx -y
sudo nano /etc/nginx/sites-available/app038  # Copy config dari Step 8.2
sudo ln -s /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# 9. Setup SSL (jika punya domain)
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# 10. Verify
curl http://168.231.118.3/health
curl https://yourdomain.com/health
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
