# 🚀 Deployment Guide ke VPS Hostinger dengan Ubuntu 24.04 & CloudPanel

Panduan lengkap untuk mendeploy aplikasi App038 ke VPS Hostinger menggunakan Ubuntu 24.04 dan CloudPanel dengan Docker Compose.

> **📌 Informasi VPS:** VPS Hostinger dengan Ubuntu 24.04 dan CloudPanel sudah terinstall. Panduan ini akan membantu Anda mendeploy aplikasi App038 dengan konfigurasi yang optimal.

---

## 🔴 STATUS DEPLOYMENT SAAT INI (Update: 10 December 2025, 17:30 UTC)

### 📊 Status VPS Hostinger:
| Item | Value |
|------|-------|
| OS | Ubuntu 24.04 LTS |
| Control Panel | CloudPanel |
| Web Server | Nginx |
| PHP Version | 8.2+ (via Docker) |
| Database | PostgreSQL 15 (via Docker) |
| State | ⏳ Ready for Deployment |

### 🔍 Arsitektur Aplikasi (PENTING!)

**Aplikasi ini menggunakan Laravel + Inertia.js + Svelte**, bukan standalone Svelte SPA.

Artinya:
- **Laravel** = serve semua (HTML + API + Assets)
- **Svelte** = hanya component UI yang di-render oleh Laravel via Inertia.js
- **Vite build** = menghasilkan assets ke `public/build/` (bukan standalone SPA)
- **Svelte container TIDAK diperlukan** untuk production

```
Arsitektur Deployment dengan CloudPanel:
Internet → Nginx (CloudPanel) → Reverse Proxy → Laravel Container (8080:80) → PostgreSQL/Redis/RabbitMQ
```

### 📦 Services yang Akan Dideploy:

| Service | Container | Port | Keterangan |
|---------|-----------|------|------------|
| Laravel | app038_laravel | 8080:80 | Backend Laravel (PHP-FPM + Nginx) |
| PostgreSQL | app038_postgres | 5432 | PostgreSQL Database |
| Redis | app038_redis | 6379 | Redis Cache |
| RabbitMQ | app038_rabbitmq | 5672 | RabbitMQ Queue |

### 📋 Technical Specifications:

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | 8.2+ | Required: `^8.2` (from composer.json) |
| **Laravel** | 11.0 LTS | Framework version |
| **Node.js** | 20.x | Recommended for Vite 5.x |
| **npm** | 9.x+ | Package manager |
| **PostgreSQL** | 15 | Database (via Docker) |
| **Redis** | 7 | Cache & Session (via Docker) |
| **RabbitMQ** | 3 | Message Queue (via Docker) |
| **Docker** | 20.10+ | Container runtime |
| **Docker Compose** | 2.0+ | Container orchestration |
| **Nginx** | Latest | Web server (via CloudPanel) |

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

---

## 📋 Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Langkah-langkah Deployment](#langkah-langkah-deployment)
3. [Konfigurasi CloudPanel & Nginx](#konfigurasi-cloudpanel--nginx)
4. [Setup Database PostgreSQL](#setup-database-postgresql)
5. [Setup SSL via CloudPanel](#setup-ssl-via-cloudpanel)
6. [Post-Deployment](#post-deployment)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)
9. [Checklist Deployment](#checklist-deployment)

---

## Prasyarat

### 1. VPS Hostinger dengan Ubuntu 24.04

**Spesifikasi Minimum:**
- OS: Ubuntu 24.04 LTS
- RAM: 4GB (recommended: 8GB)
- CPU: 2 cores (recommended: 4 cores)
- Storage: 40GB (recommended: 100GB)
- Root access atau sudo access

### 2. CloudPanel

**Status:** Sudah terinstall di VPS

**Verifikasi CloudPanel:**
```bash
# Check CloudPanel status
systemctl status cloudpanel

# Check Nginx status
systemctl status nginx

# Check CloudPanel version
clpctl --version

# Access CloudPanel UI
# URL: https://your-vps-ip:8443
# Default credentials: admin / (password set during installation)
```

**CloudPanel Features:**
- ✅ Nginx web server
- ✅ PHP-FPM management (multiple PHP versions)
- ✅ MySQL/MariaDB database management
- ✅ SSL certificate management (Let's Encrypt)
- ✅ Git deployment
- ✅ Docker support
- ✅ Reverse proxy configuration
- ✅ File manager
- ✅ Log viewer

### 3. Tools yang Diperlukan

- Docker >= 20.10
- Docker Compose >= 2.0
- Git
- Node.js >= 20.x (untuk build Vite assets)
- npm >= 9.x

---

## Langkah-langkah Deployment

### Step 1: Verifikasi Prasyarat

**1.1. Check OS Version**

```bash
# Check Ubuntu version
lsb_release -a

# Expected output:
# Distributor ID: Ubuntu
# Description:    Ubuntu 24.04 LTS
# Release:        24.04
# Codename:       noble
```

**1.2. Check CloudPanel Installation**

```bash
# Check CloudPanel service
systemctl status cloudpanel

# Check Nginx
systemctl status nginx
nginx -v

# Check CloudPanel CLI
clpctl --version

# Check CloudPanel UI access
curl -k https://localhost:8443
```

**1.3. Check Docker Installation**

```bash
# Check Docker
docker --version
docker-compose --version

# If not installed, install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Start Docker service
systemctl start docker
systemctl enable docker

# Add current user to docker group (if not root)
sudo usermod -aG docker $USER
newgrp docker

# Verify Docker works
docker run hello-world
```

**1.4. Install Docker Compose (if not installed)**

```bash
# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify
docker-compose --version
```

**1.5. Install Node.js & npm (for Vite build)**

```bash
# Install Node.js 20.x (LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify
node --version  # Should be v20.x.x
npm --version   # Should be 9.x.x or 10.x.x
```

---

### Step 2: Clone Repository

**2.1. Create Project Directory**

```bash
# Create directory (CloudPanel default: /home/cloudpanel)
mkdir -p /home/cloudpanel/app038
cd /home/cloudpanel/app038

# Or use /var/www if preferred
# mkdir -p /var/www/app038
# cd /var/www/app038
```

**2.2. Clone Repository**

```bash
# Clone from GitHub
git clone https://github.com/rdeeanz/app038.git .

# Or if repository is private, use SSH:
# git clone git@github.com:rdeeanz/app038.git .
```

**2.3. Set Permissions**

```bash
# Set ownership (CloudPanel usually uses 'clp' user)
chown -R clp:clp /home/cloudpanel/app038

# Set permissions
chmod -R 755 /home/cloudpanel/app038
```

---

### Step 3: Setup Environment Variables

**3.1. Create .env File**

```bash
cd /home/cloudpanel/app038

# Copy from example (if exists)
cp .env.example .env 2>/dev/null || true

# Or create new .env file
cat > .env << 'EOF'
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

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue Configuration
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=

# Inertia.js Configuration
INERTIA_SSR_ENABLED=false
EOF
```

**3.2. Generate Secure Passwords**

```bash
# Generate secure passwords
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
RABBITMQ_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Update .env file
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

**3.3. Update APP_URL**

```bash
# Update APP_URL with your domain
read -p "Enter your domain name (e.g., app038.yourdomain.com): " DOMAIN_NAME
sed -i "s|APP_URL=https://yourdomain.com|APP_URL=https://$DOMAIN_NAME|" .env

echo "✅ APP_URL updated to: https://$DOMAIN_NAME"
```

---

### Step 4: Build Vite Assets

**4.1. Install NPM Dependencies**

```bash
cd /home/cloudpanel/app038

# Install dependencies
npm install

# If npm install fails, try with legacy peer deps
npm install --legacy-peer-deps
```

**4.2. Build Production Assets**

```bash
# Build Vite assets for production
npm run build

# Verify build output
ls -la public/build/

# Expected output:
# - public/build/manifest.json
# - public/build/assets/ (directory with CSS and JS files)
```

**4.3. Verify Build Files**

```bash
# Check if build files exist
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Vite build successful"
    cat public/build/manifest.json | head -20
else
    echo "❌ Vite build failed - check npm run build output"
    exit 1
fi
```

---

### Step 5: Setup Docker & Docker Compose

**5.1. Verify Docker Compose File**

```bash
# Check docker-compose.prod.yml exists
if [ -f "docker-compose.prod.yml" ]; then
    echo "✅ docker-compose.prod.yml found"
    cat docker-compose.prod.yml | head -50
else
    echo "❌ docker-compose.prod.yml not found"
    exit 1
fi
```

**5.2. Create Docker Network (if needed)**

```bash
# Create custom network (optional, docker-compose will create automatically)
docker network create app038_network 2>/dev/null || echo "Network may already exist"
```

**5.3. Build Docker Images**

```bash
# Build Laravel container
docker-compose -f docker-compose.prod.yml build --no-cache laravel

# This will take several minutes
# Expected output: Successfully built [image-id]
```

**5.4. Start Docker Containers**

```bash
# Start all services
docker-compose -f docker-compose.prod.yml up -d

# Check container status
docker ps

# Expected output: All containers should be "Up" and "healthy"
```

**5.5. Verify Containers**

```bash
# Check all containers are running
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038

# Check container health
docker-compose -f docker-compose.prod.yml ps

# Check logs
docker-compose -f docker-compose.prod.yml logs --tail=50
```

---

### Step 6: Setup Laravel Application

**6.1. Generate APP_KEY**

```bash
# Generate application key
docker exec app038_laravel php artisan key:generate --force

# Update .env file (if needed)
docker exec app038_laravel php artisan config:clear
```

**6.2. Run Database Migrations**

```bash
# Run migrations
docker exec app038_laravel php artisan migrate --force

# If migrations fail, check database connection first:
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected!';"
```

**6.3. Run Seeders (Optional)**

```bash
# Run seeders to populate initial data
docker exec app038_laravel php artisan db:seed --force

# Or run specific seeders
docker exec app038_laravel php artisan db:seed --class=RolePermissionSeeder --force
docker exec app038_laravel php artisan db:seed --class=SuperAdminSeeder --force
```

**6.4. Clear and Cache Configuration**

```bash
# Clear all caches
docker exec app038_laravel php artisan config:clear
docker exec app038_laravel php artisan cache:clear
docker exec app038_laravel php artisan view:clear
docker exec app038_laravel php artisan route:clear

# Cache for production
docker exec app038_laravel php artisan config:cache
docker exec app038_laravel php artisan route:cache
docker exec app038_laravel php artisan view:cache
```

**6.5. Verify Application**

```bash
# Test health endpoint
curl http://localhost:8080/health

# Expected output: healthy

# Test application (should return HTML)
curl -I http://localhost:8080/

# Expected: HTTP/1.1 200 OK
```

---

## Konfigurasi CloudPanel & Nginx

### Step 7: Create Website di CloudPanel

**7.1. Login ke CloudPanel**

1. **Access CloudPanel UI:**
   - URL: `https://your-vps-ip:8443`
   - Username: `admin`
   - Password: (password yang di-set saat instalasi CloudPanel)

2. **Change Admin Password (IMPORTANT!):**
   - Go to: **Settings** → **Change Password**
   - Set strong password
   - Save

**7.2. Create Site**

1. **Navigate to:** Sites → **Create Site**

2. **Fill in details:**
   - **Domain:** `yourdomain.com` (or subdomain like `app038.yourdomain.com`)
   - **PHP Version:** Not needed (we use Docker)
   - **Create Database:** No (we use PostgreSQL in Docker)
   - **Create FTP:** Optional
   - **Create Email:** Optional

3. **Click:** Create Site

**7.3. Note Document Root**

After site is created, note the document root:
- Usually: `/home/cloudpanel/sites/yourdomain.com/public`

We'll configure Nginx to reverse proxy to Docker container instead of serving files from this directory.

---

### Step 8: Configure Nginx Reverse Proxy

**8.1. Via CloudPanel UI (Recommended)**

1. **Navigate to:** Sites → yourdomain.com → **Nginx Config**

2. **Edit Nginx Configuration:**
   - Click **Edit** button
   - Replace the default configuration with reverse proxy configuration

3. **Add Reverse Proxy Configuration:**

```nginx
server {
  listen 80;
  listen [::]:80;
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name yourdomain.com www.yourdomain.com;
  {{root}}

  {{nginx_access_log}}
  {{nginx_error_log}}

  # Redirect HTTP to HTTPS
  if ($scheme != "https") {
    rewrite ^ https://$host$uri permanent;
  }

  # Allow Let's Encrypt validation
  location ~ /.well-known {
    auth_basic off;
    allow all;
  }

  # Reverse proxy to Laravel container
  location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
    
    # WebSocket support (if needed)
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

  # Health check endpoint (bypass proxy)
  location /health {
    proxy_pass http://127.0.0.1:8080/health;
    access_log off;
  }

  # Static assets caching (optional - Laravel serves these)
  location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|webp|avif)$ {
    proxy_pass http://127.0.0.1:8080;
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
  }
}
```

4. **Save Configuration**

5. **Reload Nginx:**
   - CloudPanel will automatically reload Nginx
   - Or manually: `sudo systemctl reload nginx`

**8.2. Alternative: Manual Configuration via Config File**

If CloudPanel UI doesn't work, edit config file directly:

```bash
# Find Nginx config file for your site
# Usually located at: /home/cloudpanel/sites/yourdomain.com/nginx/conf.d/main.conf
# Or: /etc/nginx/sites-available/yourdomain.com

# Edit config file
sudo nano /home/cloudpanel/sites/yourdomain.com/nginx/conf.d/main.conf
```

**Replace with reverse proxy configuration:**

```nginx
server {
  listen 80;
  listen [::]:80;
  server_name yourdomain.com www.yourdomain.com;
  
  # Redirect HTTP to HTTPS
  return 301 https://$host$request_uri;
}

server {
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  server_name yourdomain.com www.yourdomain.com;
  
  # SSL certificates (will be set by CloudPanel)
  ssl_certificate /home/cloudpanel/ssl/yourdomain.com/fullchain.pem;
  ssl_certificate_key /home/cloudpanel/ssl/yourdomain.com/privkey.pem;
  
  # Logging
  access_log /home/cloudpanel/sites/yourdomain.com/logs/access.log;
  error_log /home/cloudpanel/sites/yourdomain.com/logs/error.log;
  
  # Allow Let's Encrypt validation
  location ~ /.well-known {
    auth_basic off;
    allow all;
  }
  
  # Reverse proxy to Laravel container
  location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
    
    # WebSocket support
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
  
  # Health check
  location /health {
    proxy_pass http://127.0.0.1:8080/health;
    access_log off;
  }
}
```

**Test and Reload Nginx:**

```bash
# Test Nginx configuration
sudo nginx -t

# If test passes, reload Nginx
sudo systemctl reload nginx

# Or restart if needed
sudo systemctl restart nginx
```

---

### Step 9: Configure Firewall

**9.1. Check Firewall Status**

```bash
# Check if UFW is active
sudo ufw status

# Or check iptables
sudo iptables -L -n
```

**9.2. Open Required Ports**

```bash
# If using UFW
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8443/tcp  # CloudPanel
sudo ufw allow 22/tcp    # SSH
sudo ufw reload

# Verify
sudo ufw status
```

**9.3. Verify Ports**

```bash
# Check listening ports
sudo netstat -tulpn | grep -E ':(80|443|8443|8080)'

# Or use ss
sudo ss -tulpn | grep -E ':(80|443|8443|8080)'
```

---

## Setup Database PostgreSQL

### Step 10: Verify PostgreSQL Container

**10.1. Check PostgreSQL Container**

```bash
# Check container status
docker ps | grep postgres

# Check logs
docker logs app038_postgres --tail=50

# Test connection from host
docker exec app038_postgres psql -U postgres -c "SELECT version();"
```

**10.2. Create Database (if needed)**

```bash
# Database should be created automatically via docker-compose
# But verify:
docker exec app038_postgres psql -U postgres -l

# If database doesn't exist, create it:
docker exec app038_postgres psql -U postgres -c "CREATE DATABASE app038;"
```

**10.3. Verify Database Connection from Laravel**

```bash
# Test database connection
docker exec app038_laravel php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo '✅ Database connected successfully!';
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage();
}
"
```

---

## Setup SSL via CloudPanel

### Step 11: Install SSL Certificate

**11.1. Via CloudPanel UI (Recommended)**

1. **Navigate to:** Sites → yourdomain.com → **SSL**

2. **Issue SSL Certificate:**
   - Click **Issue SSL**
   - Select **Let's Encrypt** (Free, recommended)
   - Enter your email address
   - Click **Issue SSL**

3. **Wait for SSL Installation:**
   - Usually takes 1-2 minutes
   - You'll see success message when done

4. **Enable Force HTTPS:**
   - After SSL is installed, enable **Force HTTPS**
   - This will redirect all HTTP traffic to HTTPS

**11.2. Verify SSL**

```bash
# Test SSL certificate
curl -I https://yourdomain.com

# Check certificate details
echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates

# Test from browser
# Visit: https://yourdomain.com
```

**11.3. Force HTTPS Redirect (if not enabled)**

If Force HTTPS is not enabled via UI, add to Nginx config:

```nginx
# Add to server block (port 80)
server {
  listen 80;
  listen [::]:80;
  server_name yourdomain.com www.yourdomain.com;
  
  return 301 https://$host$request_uri;
}
```

---

## Post-Deployment

### Step 12: Final Verification

**12.1. Test Application Endpoints**

```bash
# Test health endpoint
curl https://yourdomain.com/health

# Expected: healthy

# Test main page
curl -I https://yourdomain.com/

# Expected: HTTP/2 200

# Test from browser
# Visit: https://yourdomain.com
```

**12.2. Check Application Logs**

```bash
# Laravel logs
docker logs app038_laravel --tail=50

# Nginx error logs
sudo tail -50 /home/cloudpanel/sites/yourdomain.com/logs/error.log

# Nginx access logs
sudo tail -50 /home/cloudpanel/sites/yourdomain.com/logs/access.log
```

**12.3. Verify All Services**

```bash
# Check all containers
docker ps

# Check container health
docker-compose -f docker-compose.prod.yml ps

# Test database
docker exec app038_laravel php artisan tinker --execute="echo 'Users: ' . App\Models\User::count();"

# Test Redis
docker exec app038_laravel php artisan tinker --execute="Cache::put('test', 'value', 10); echo 'Redis: ' . Cache::get('test');"

# Test RabbitMQ
docker exec app038_rabbitmq rabbitmq-diagnostics ping
```

---

## Monitoring & Maintenance

### Daily Tasks

```bash
# Check application health
curl https://yourdomain.com/health

# Check container status
docker ps

# Check disk usage
df -h

# Check memory usage
free -h
```

### Weekly Tasks

```bash
# Review application logs
docker logs app038_laravel --since 7d | grep -i error

# Check database size
docker exec app038_postgres psql -U postgres -c "SELECT pg_size_pretty(pg_database_size('app038'));"

# Review Nginx logs
sudo tail -100 /home/cloudpanel/sites/yourdomain.com/logs/error.log | grep -i error
```

### Monthly Tasks

```bash
# Update Docker images
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d

# Update system packages
sudo apt update && sudo apt upgrade -y

# Review and rotate logs
# CloudPanel has built-in log rotation
```

---

## Troubleshooting

### Issue 1: Container Tidak Bisa Start

**Symptoms:**
- `docker ps` shows containers as "Restarting" or "Exited"
- Application tidak bisa diakses

**Solution:**

```bash
# Check container logs
docker logs app038_laravel --tail=100

# Check common issues:
# 1. Database connection failed
docker exec app038_laravel php artisan tinker --execute="DB::connection()->getPdo();"

# 2. Missing .env file
docker exec app038_laravel ls -la /app/.env

# 3. Permission issues
docker exec app038_laravel ls -la /app/storage

# Restart containers
docker-compose -f docker-compose.prod.yml restart
```

### Issue 2: Nginx 502 Bad Gateway

**Symptoms:**
- Browser shows "502 Bad Gateway"
- Application tidak bisa diakses via domain

**Solution:**

```bash
# 1. Check if Laravel container is running
docker ps | grep laravel

# 2. Test direct connection to container
curl http://127.0.0.1:8080/health

# 3. Check Nginx error logs
sudo tail -50 /home/cloudpanel/sites/yourdomain.com/logs/error.log

# 4. Verify reverse proxy configuration
sudo cat /home/cloudpanel/sites/yourdomain.com/nginx/conf.d/main.conf | grep proxy_pass

# 5. Test Nginx configuration
sudo nginx -t

# 6. Reload Nginx
sudo systemctl reload nginx
```

### Issue 3: SSL Certificate Tidak Terbit

**Symptoms:**
- SSL installation fails in CloudPanel
- Certificate tidak valid

**Solution:**

```bash
# 1. Check DNS pointing
dig yourdomain.com
nslookup yourdomain.com

# 2. Verify port 80 and 443 are open
sudo netstat -tulpn | grep -E ':(80|443)'

# 3. Check firewall
sudo ufw status

# 4. Check Nginx allows .well-known
sudo cat /home/cloudpanel/sites/yourdomain.com/nginx/conf.d/main.conf | grep well-known

# 5. Manual SSL installation via CloudPanel CLI (if available)
# Check CloudPanel documentation for CLI commands
```

### Issue 4: Database Connection Failed

**Symptoms:**
- Laravel error: "SQLSTATE[08006] [7] connection to server failed"
- Migrations fail

**Solution:**

```bash
# 1. Check PostgreSQL container
docker ps | grep postgres
docker logs app038_postgres --tail=50

# 2. Verify database credentials in .env
cat .env | grep DB_

# 3. Test connection from host
docker exec app038_postgres psql -U postgres -d app038 -c "SELECT 1;"

# 4. Check network connectivity
docker exec app038_laravel ping -c 3 postgres

# 5. Verify .env DB_HOST is "postgres" (Docker service name)
docker exec app038_laravel cat /app/.env | grep DB_HOST
```

### Issue 5: Vite Assets Tidak Muncul

**Symptoms:**
- Website loads but CSS/JS tidak muncul
- Browser console shows 404 for assets

**Solution:**

```bash
# 1. Verify build files exist
ls -la public/build/

# 2. Check if build files are in container
docker exec app038_laravel ls -la /app/public/build/

# 3. Rebuild if needed
npm run build
docker-compose -f docker-compose.prod.yml restart laravel

# 4. Check .dockerignore (should NOT exclude public/build)
cat .dockerignore | grep -v "^#" | grep build
```

### Issue 6: Nginx Configuration Tidak Berfungsi

**Symptoms:**
- Changes in CloudPanel UI tidak apply
- Reverse proxy tidak bekerja

**Solution:**

```bash
# 1. Verify configuration syntax
sudo nginx -t

# 2. Check configuration file location
sudo find /home/cloudpanel -name "main.conf" -o -name "*.conf" | grep yourdomain.com

# 3. Check if CloudPanel overwrote config
sudo cat /home/cloudpanel/sites/yourdomain.com/nginx/conf.d/main.conf

# 4. Manual reload
sudo systemctl reload nginx

# 5. Full restart (if reload doesn't work)
sudo systemctl restart nginx
```

### Issue 7: CloudPanel UI Tidak Bisa Diakses

**Symptoms:**
- Cannot access CloudPanel at port 8443
- Connection refused

**Solution:**

```bash
# 1. Check CloudPanel service
sudo systemctl status cloudpanel

# 2. Check if port 8443 is listening
sudo netstat -tulpn | grep 8443

# 3. Check firewall
sudo ufw status | grep 8443

# 4. Restart CloudPanel
sudo systemctl restart cloudpanel

# 5. Check CloudPanel logs
sudo journalctl -u cloudpanel -n 50
```

---

## Checklist Deployment

### Pre-Deployment

- [ ] VPS Hostinger dengan Ubuntu 24.04 sudah aktif
- [ ] CloudPanel sudah terinstall
- [ ] SSH access sudah berfungsi
- [ ] Root atau sudo access tersedia
- [ ] Domain name sudah terdaftar dan DNS pointing ke VPS IP

### Deployment Steps

- [ ] Docker dan Docker Compose sudah terinstall
- [ ] Node.js 20.x dan npm sudah terinstall
- [ ] Repository sudah di-clone
- [ ] `.env` file sudah dikonfigurasi dengan benar
- [ ] Passwords sudah di-generate dan disimpan dengan aman
- [ ] Vite assets sudah di-build (`npm run build`)
- [ ] Docker containers sudah di-build dan running
- [ ] APP_KEY sudah di-generate
- [ ] Database migrations sudah dijalankan
- [ ] Seeders sudah dijalankan (jika diperlukan)
- [ ] Laravel caches sudah di-clear dan di-cache ulang

### CloudPanel Configuration

- [ ] Site sudah dibuat di CloudPanel
- [ ] Nginx reverse proxy sudah dikonfigurasi
- [ ] Firewall ports sudah dibuka (80, 443, 8443, 22)
- [ ] SSL certificate sudah terinstall via CloudPanel
- [ ] HTTPS redirect sudah diaktifkan

### Verification

- [ ] Health endpoint (`/health`) return "healthy"
- [ ] Main page bisa diakses via HTTPS
- [ ] All assets (CSS/JS) loading dengan benar
- [ ] Database connection working
- [ ] Redis connection working
- [ ] RabbitMQ connection working
- [ ] Application logs tidak ada error
- [ ] Nginx logs tidak ada error

### Post-Deployment

- [ ] Admin password CloudPanel sudah diubah
- [ ] Passwords sudah disimpan dengan aman
- [ ] Monitoring sudah di-setup (opsional)
- [ ] Backup strategy sudah direncanakan
- [ ] Documentation sudah di-update

---

## Security Best Practices

### 1. Change Default Passwords

```bash
# Change CloudPanel admin password
# Via UI: CloudPanel → Settings → Change Password

# Change system root password (if needed)
sudo passwd root
```

### 2. Firewall Configuration

```bash
# Only allow necessary ports
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 8443/tcp  # CloudPanel
sudo ufw enable

# Verify
sudo ufw status verbose
```

### 3. Regular Updates

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker images
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d
```

### 4. Backup Strategy

```bash
# Create backup script
sudo nano /usr/local/bin/app038-backup.sh
```

**Add backup script:**

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/app038"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
docker exec app038_postgres pg_dump -U postgres app038 > $BACKUP_DIR/db_$DATE.sql

# Backup .env file
cp /home/cloudpanel/app038/.env $BACKUP_DIR/env_$DATE.txt

# Backup storage
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /home/cloudpanel/app038 storage

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/app038-backup.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/app038-backup.sh") | crontab -
```

---

## Performance Optimization

### 1. Nginx Tuning

Edit Nginx main configuration:

```bash
sudo nano /etc/nginx/nginx.conf
```

**Recommended settings:**

```nginx
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
gzip_vary on;
```

**Reload Nginx:**

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 2. PHP-FPM Tuning (in Docker)

Edit `docker/php/Dockerfile` PHP-FPM configuration:

```ini
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

### 3. Enable OPcache (already enabled in Dockerfile)

### 4. Redis Caching

Already configured in `.env`:
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 5. CloudPanel Performance Settings

1. **Navigate to:** Sites → yourdomain.com → **Settings**
2. **Enable:**
   - Gzip Compression
   - Browser Caching
   - Static File Caching

---

## Additional Resources

### CloudPanel Documentation

- **Official Website:** https://www.cloudpanel.io
- **Documentation:** https://www.cloudpanel.io/docs/
- **GitHub:** https://github.com/cloudpanel-io/cloudpanel

### Nginx Documentation

- **Official Website:** https://nginx.org
- **Documentation:** https://nginx.org/en/docs/

### Laravel Documentation

- **Official Website:** https://laravel.com
- **Documentation:** https://laravel.com/docs

---

## Support & Troubleshooting

### Get Help

1. **Check Logs:**
   - Laravel: `docker logs app038_laravel`
   - Nginx: `/home/cloudpanel/sites/yourdomain.com/logs/error.log`
   - CloudPanel: `sudo journalctl -u cloudpanel -n 50`

2. **Community Support:**
   - CloudPanel Forum: https://community.cloudpanel.io
   - Laravel Forums: https://laracasts.com/discuss

3. **Documentation:**
   - This guide: `DEPLOY_CLOUDPANEL.md`
   - General deployment: `DEPLOY_HOSTINGER.md`
   - Database setup: `DATABASE_SETUP.md`

---

**✅ Selesai!** Website Anda sekarang sudah online di `https://yourdomain.com`

**Next Steps:**
1. Setup monitoring alerts
2. Configure automated backups
3. Review security settings
4. Monitor resource usage
5. Regular updates

---

**Last Updated:** 10 December 2025, 17:30 UTC
