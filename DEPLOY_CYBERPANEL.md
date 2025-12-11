# 🚀 Deployment Guide ke VPS Hostinger dengan AlmaLinux 9 & CyberPanel

Panduan lengkap untuk mendeploy aplikasi App038 ke VPS Hostinger menggunakan AlmaLinux 9 dan CyberPanel (Free Version) dengan Docker Compose.

> **📌 Informasi VPS:** VPS Hostinger dengan AlmaLinux 9 dan CyberPanel (Free Version) sudah terinstall. Panduan ini akan membantu Anda mendeploy aplikasi App038 dengan konfigurasi yang optimal.

---

## 🔴 STATUS DEPLOYMENT SAAT INI (Update: 10 December 2025, 17:00 UTC)

### 📊 Status VPS Hostinger:
| Item | Value |
|------|-------|
| OS | AlmaLinux 9 |
| Control Panel | CyberPanel (Free Version) |
| Web Server | OpenLiteSpeed |
| Database | PostgreSQL (via Docker) |
| State | ⏳ Ready for Deployment |

### 🔍 Arsitektur Aplikasi (PENTING!)

**Aplikasi ini menggunakan Laravel + Inertia.js + Svelte**, bukan standalone Svelte SPA.

Artinya:
- **Laravel** = serve semua (HTML + API + Assets)
- **Svelte** = hanya component UI yang di-render oleh Laravel via Inertia.js
- **Vite build** = menghasilkan assets ke `public/build/` (bukan standalone SPA)
- **Svelte container TIDAK diperlukan** untuk production

```
Arsitektur Deployment dengan CyberPanel:
Internet → OpenLiteSpeed (CyberPanel) → Reverse Proxy → Laravel Container (8080:80) → PostgreSQL/Redis/RabbitMQ
```

### 📦 Services yang Akan Dideploy:

| Service | Container | Port | Keterangan |
|---------|-----------|------|------------|
| Laravel | app038_laravel | 8080:80 | Backend Laravel (PHP-FPM + Nginx) |
| PostgreSQL | app038_postgres | 5432 | PostgreSQL Database |
| Redis | app038_redis | 6379 | Redis Cache |
| RabbitMQ | app038_rabbitmq | 5672 | RabbitMQ Queue |

---

## 📋 Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Langkah-langkah Deployment](#langkah-langkah-deployment)
3. [Konfigurasi CyberPanel & OpenLiteSpeed](#konfigurasi-cyberpanel--openlitespeed)
4. [Setup Database PostgreSQL](#setup-database-postgresql)
5. [Setup SSL via CyberPanel](#setup-ssl-via-cyberpanel)
6. [Post-Deployment](#post-deployment)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)
9. [Checklist Deployment](#checklist-deployment)

---

## Prasyarat

### 1. VPS Hostinger dengan AlmaLinux 9

**Spesifikasi Minimum:**
- OS: AlmaLinux 9
- RAM: 4GB (recommended: 8GB)
- CPU: 2 cores (recommended: 4 cores)
- Storage: 40GB (recommended: 100GB)
- Root access atau sudo access

### 2. CyberPanel (Free Version)

**Status:** Sudah terinstall di VPS

**Verifikasi CyberPanel:**
```bash
# Check CyberPanel status
systemctl status lscpd
systemctl status lsws

# Check CyberPanel version
cyberpanel version

# Access CyberPanel UI
# URL: https://your-vps-ip:8090
# Default credentials: admin / 1234567 (CHANGE IMMEDIATELY!)
```

### 3. Tools yang Diperlukan

- Docker >= 20.10
- Docker Compose >= 2.0
- Git
- Node.js >= 18.x (untuk build Vite assets)
- npm >= 9.x

---

## Langkah-langkah Deployment

### Step 1: Verifikasi Prasyarat

**1.1. Check OS Version**

```bash
# Check AlmaLinux version
cat /etc/almalinux-release

# Expected output: AlmaLinux release 9.x
```

**1.2. Check CyberPanel Installation**

```bash
# Check CyberPanel services
systemctl status lscpd
systemctl status lsws

# Check OpenLiteSpeed
/usr/local/lsws/bin/lswsctrl status

# Check CyberPanel UI access
curl -k https://localhost:8090
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
usermod -aG docker $USER
```

**1.4. Install Docker Compose (if not installed)**

```bash
# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Verify
docker-compose --version
```

**1.5. Install Node.js & npm (for Vite build)**

```bash
# Install Node.js 20.x (LTS)
curl -fsSL https://rpm.nodesource.com/setup_20.x | bash -
yum install -y nodejs

# Verify
node --version
npm --version
```

---

### Step 2: Clone Repository

**2.1. Create Project Directory**

```bash
# Create directory
mkdir -p /home/app038
cd /home/app038

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
# Set ownership (adjust user as needed)
chown -R root:root /home/app038
# Or if using specific user:
# chown -R username:username /home/app038

# Set permissions
chmod -R 755 /home/app038
```

---

### Step 3: Setup Environment Variables

**3.1. Create .env File**

```bash
cd /home/app038

# Copy from example (if exists)
cp .env.example .env

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

**3.2. Generate APP_KEY**

```bash
# Generate APP_KEY (will be done after container is running)
# For now, leave it empty or generate temporary key
php artisan key:generate --show 2>/dev/null || echo "Will generate after container setup"
```

**3.3. Generate Secure Passwords**

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

**3.4. Update APP_URL**

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
cd /home/app038

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

## Konfigurasi CyberPanel & OpenLiteSpeed

### Step 7: Create Website di CyberPanel

**7.1. Login ke CyberPanel**

1. **Access CyberPanel UI:**
   - URL: `https://your-vps-ip:8090`
   - Username: `admin`
   - Password: `1234567` (CHANGE IMMEDIATELY after first login!)

2. **Change Admin Password (IMPORTANT!):**
   - Go to: **Change Password** (top right)
   - Set strong password
   - Save

**7.2. Create Website**

1. **Navigate to:** Websites → Create Website

2. **Fill in details:**
   - **Domain Name:** `yourdomain.com` (or subdomain like `app038.yourdomain.com`)
   - **Email:** Your email address
   - **Package:** Select package or create new
   - **PHP Version:** Not needed (we use Docker)
   - **Create Database:** No (we use PostgreSQL in Docker)
   - **Create FTP:** Optional
   - **Create Email:** Optional

3. **Click:** Create Website

**7.3. Note Document Root**

After website is created, note the document root:
- Usually: `/home/yourdomain.com/public_html`

We'll configure OpenLiteSpeed to reverse proxy to Docker container instead of serving files from this directory.

---

### Step 8: Configure OpenLiteSpeed Reverse Proxy

**8.1. Access OpenLiteSpeed WebAdmin**

1. **Get WebAdmin Password:**
   ```bash
   # Get CyberPanel admin password
   cat /etc/cyberpanel/adminPass
   
   # Or reset if needed:
   # /usr/local/CyberCP/bin/python manage.py changepassword admin
   ```

2. **Access WebAdmin:**
   - URL: `https://your-vps-ip:7080`
   - Username: `admin`
   - Password: (from step above)

**8.2. Configure Virtual Host**

1. **Navigate to:** Virtual Hosts → yourdomain.com → Edit

2. **General Settings:**
   - **Document Root:** `/home/yourdomain.com/public_html` (keep default)
   - **Index Files:** `index.php, index.html`

3. **Script Handler:**
   - We'll configure reverse proxy instead of PHP handler

**8.3. Setup Reverse Proxy**

1. **Navigate to:** Virtual Hosts → yourdomain.com → Rewrite

2. **Enable Rewrite:**
   - Check: **Enable Rewrite**
   - **Rewrite Rules:** Add the following:

```
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/health
RewriteRule ^(.*)$ http://127.0.0.1:8080$1 [P,L]
```

3. **Alternative: Use External Application (Recommended)**

   **Navigate to:** Virtual Hosts → yourdomain.com → External App

   **Create External Application:**
   - **Name:** `app038_laravel`
   - **Address:** `http://127.0.0.1:8080`
   - **Max Connections:** `100`
   - **Initial Request Timeout (secs):** `60`
   - **Retry Timeout (secs):** `0`
   - **Response Buffering:** `No`
   - **Keep-Alive Timeout:** `60`
   - **Keep-Alive Max Request:** `100`

   **Navigate to:** Virtual Hosts → yourdomain.com → Script Handler

   **Add Script Handler:**
   - **Suffixes:** `*`
   - **Handler Type:** `External Application`
   - **Handler Name:** `app038_laravel`
   - **Note:** Leave other fields empty

4. **Save Configuration**

5. **Graceful Restart OpenLiteSpeed:**
   ```bash
   /usr/local/lsws/bin/lswsctrl restart
   ```

**8.4. Alternative: Manual Configuration via Config File**

If WebAdmin doesn't work, edit config file directly:

```bash
# Edit OpenLiteSpeed config
nano /usr/local/lsws/conf/vhosts/yourdomain.com/vhost.conf
```

**Add to Virtual Host Configuration:**

```xml
<virtualHost yourdomain.com>
  <member>
    yourdomain.com
    www.yourdomain.com
  </member>
  
  <docRoot>/home/yourdomain.com/public_html</docRoot>
  
  <index>
    index.php
    index.html
  </index>
  
  <!-- Reverse Proxy Configuration -->
  <rewrite>
    <enable>1</enable>
    <rewriteRule>
      <name>Proxy to Laravel</name>
      <from>^/(.*)$</from>
      <to>http://127.0.0.1:8080/$1</to>
      <type>P</type>
    </rewriteRule>
  </rewrite>
  
  <!-- Or use External Application -->
  <extProcessor>
    <type>proxy</type>
    <name>app038_laravel</name>
    <address>http://127.0.0.1:8080</address>
    <maxConns>100</maxConns>
    <initTimeout>60</initTimeout>
    <retryTimeout>0</retryTimeout>
    <respBuffer>0</respBuffer>
    <keepaliveTimeout>60</keepaliveTimeout>
    <keepaliveMax>100</keepaliveMax>
  </extProcessor>
  
  <scriptHandler>
    <suffix>*</suffix>
    <type>proxy</type>
    <handler>app038_laravel</handler>
  </scriptHandler>
</virtualHost>
```

**Reload OpenLiteSpeed:**

```bash
/usr/local/lsws/bin/lswsctrl reload
```

---

### Step 9: Configure Firewall

**9.1. Check Firewall Status**

```bash
# Check if firewall is active
systemctl status firewalld

# Or check iptables
iptables -L -n
```

**9.2. Open Required Ports**

```bash
# If using firewalld
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=8090/tcp  # CyberPanel
firewall-cmd --permanent --add-port=7080/tcp  # OpenLiteSpeed WebAdmin
firewall-cmd --reload

# If using iptables
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p tcp --dport 8090 -j ACCEPT
iptables -A INPUT -p tcp --dport 7080 -j ACCEPT
iptables-save > /etc/sysconfig/iptables
```

**9.3. Verify Ports**

```bash
# Check listening ports
netstat -tulpn | grep -E ':(80|443|8090|7080|8080)'

# Or use ss
ss -tulpn | grep -E ':(80|443|8090|7080|8080)'
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

## Setup SSL via CyberPanel

### Step 11: Install SSL Certificate

**11.1. Via CyberPanel UI (Recommended)**

1. **Navigate to:** SSL → Issue SSL

2. **Select Website:**
   - Choose your domain: `yourdomain.com`

3. **Select SSL Provider:**
   - **Let's Encrypt** (Free, recommended)
   - Or **Cloudflare** (if using Cloudflare DNS)

4. **Fill in Email:**
   - Enter your email address

5. **Click:** Issue SSL

6. **Wait for SSL Installation:**
   - Usually takes 1-2 minutes
   - You'll see success message when done

**11.2. Verify SSL**

```bash
# Test SSL certificate
curl -I https://yourdomain.com

# Check certificate details
echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates

# Test from browser
# Visit: https://yourdomain.com
```

**11.3. Force HTTPS Redirect**

1. **Via CyberPanel UI:**
   - Navigate to: Websites → yourdomain.com → SSL
   - Enable: **Force HTTPS**
   - Save

2. **Or via OpenLiteSpeed WebAdmin:**
   - Navigate to: Virtual Hosts → yourdomain.com → Rewrite
   - Add rule:
   ```
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
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

# OpenLiteSpeed error logs
tail -50 /usr/local/lsws/logs/yourdomain.com/error.log

# OpenLiteSpeed access logs
tail -50 /usr/local/lsws/logs/yourdomain.com/access.log
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

# Review OpenLiteSpeed logs
tail -100 /usr/local/lsws/logs/yourdomain.com/error.log | grep -i error
```

### Monthly Tasks

```bash
# Update Docker images
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d

# Update system packages
yum update -y

# Review and rotate logs
# CyberPanel has built-in log rotation
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

### Issue 2: OpenLiteSpeed 502 Bad Gateway

**Symptoms:**
- Browser shows "502 Bad Gateway"
- Application tidak bisa diakses via domain

**Solution:**

```bash
# 1. Check if Laravel container is running
docker ps | grep laravel

# 2. Test direct connection to container
curl http://127.0.0.1:8080/health

# 3. Check OpenLiteSpeed error logs
tail -50 /usr/local/lsws/logs/error.log

# 4. Verify reverse proxy configuration
cat /usr/local/lsws/conf/vhosts/yourdomain.com/vhost.conf | grep -A 10 "rewrite\|extProcessor"

# 5. Restart OpenLiteSpeed
/usr/local/lsws/bin/lswsctrl restart
```

### Issue 3: SSL Certificate Tidak Terbit

**Symptoms:**
- SSL installation fails in CyberPanel
- Certificate tidak valid

**Solution:**

```bash
# 1. Check DNS pointing
dig yourdomain.com
nslookup yourdomain.com

# 2. Verify port 80 and 443 are open
netstat -tulpn | grep -E ':(80|443)'

# 3. Check firewall
firewall-cmd --list-ports

# 4. Manual SSL installation via CyberPanel CLI
cyberpanel issueSSL --domainName yourdomain.com --email your@email.com
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

### Issue 6: OpenLiteSpeed Configuration Tidak Berfungsi

**Symptoms:**
- Changes in WebAdmin tidak apply
- Reverse proxy tidak bekerja

**Solution:**

```bash
# 1. Verify configuration syntax
/usr/local/lsws/bin/lswsctrl configtest

# 2. Check configuration file
cat /usr/local/lsws/conf/vhosts/yourdomain.com/vhost.conf

# 3. Manual reload
/usr/local/lsws/bin/lswsctrl reload

# 4. Full restart (if reload doesn't work)
/usr/local/lsws/bin/lswsctrl stop
/usr/local/lsws/bin/lswsctrl start
```

---

## Checklist Deployment

### Pre-Deployment

- [ ] VPS Hostinger dengan AlmaLinux 9 sudah aktif
- [ ] CyberPanel (Free Version) sudah terinstall
- [ ] SSH access sudah berfungsi
- [ ] Root atau sudo access tersedia
- [ ] Domain name sudah terdaftar dan DNS pointing ke VPS IP

### Deployment Steps

- [ ] Docker dan Docker Compose sudah terinstall
- [ ] Node.js dan npm sudah terinstall
- [ ] Repository sudah di-clone
- [ ] `.env` file sudah dikonfigurasi dengan benar
- [ ] Passwords sudah di-generate dan disimpan dengan aman
- [ ] Vite assets sudah di-build (`npm run build`)
- [ ] Docker containers sudah di-build dan running
- [ ] APP_KEY sudah di-generate
- [ ] Database migrations sudah dijalankan
- [ ] Seeders sudah dijalankan (jika diperlukan)
- [ ] Laravel caches sudah di-clear dan di-cache ulang

### CyberPanel Configuration

- [ ] Website sudah dibuat di CyberPanel
- [ ] OpenLiteSpeed reverse proxy sudah dikonfigurasi
- [ ] Firewall ports sudah dibuka (80, 443, 8090, 7080)
- [ ] SSL certificate sudah terinstall via CyberPanel
- [ ] HTTPS redirect sudah diaktifkan

### Verification

- [ ] Health endpoint (`/health`) return "healthy"
- [ ] Main page bisa diakses via HTTPS
- [ ] All assets (CSS/JS) loading dengan benar
- [ ] Database connection working
- [ ] Redis connection working
- [ ] RabbitMQ connection working
- [ ] Application logs tidak ada error
- [ ] OpenLiteSpeed logs tidak ada error

### Post-Deployment

- [ ] Admin password CyberPanel sudah diubah
- [ ] Passwords sudah disimpan dengan aman
- [ ] Monitoring sudah di-setup (opsional)
- [ ] Backup strategy sudah direncanakan
- [ ] Documentation sudah di-update

---

## Security Best Practices

### 1. Change Default Passwords

```bash
# Change CyberPanel admin password
# Via UI: CyberPanel → Change Password

# Change OpenLiteSpeed WebAdmin password
/usr/local/lsws/admin/misc/admpass.sh
```

### 2. Firewall Configuration

```bash
# Only allow necessary ports
firewall-cmd --permanent --remove-service=ssh  # If not needed
firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="YOUR_IP" port port="22" protocol="tcp" accept'
firewall-cmd --reload
```

### 3. Regular Updates

```bash
# Update system packages
yum update -y

# Update Docker images
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d
```

### 4. Backup Strategy

```bash
# Create backup script
cat > /usr/local/bin/app038-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/app038"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
docker exec app038_postgres pg_dump -U postgres app038 > $BACKUP_DIR/db_$DATE.sql

# Backup .env file
cp /home/app038/.env $BACKUP_DIR/env_$DATE.txt

# Backup storage
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /home/app038 storage

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
EOF

chmod +x /usr/local/bin/app038-backup.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/app038-backup.sh") | crontab -
```

---

## Performance Optimization

### 1. OpenLiteSpeed Tuning

```bash
# Edit OpenLiteSpeed configuration
nano /usr/local/lsws/conf/httpd_config.conf

# Recommended settings:
# - Max Connections: 1000
# - Initial Request Timeout: 60
# - Retry Timeout: 0
# - Keep-Alive Timeout: 60
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

---

## Additional Resources

### CyberPanel Documentation

- **Official Website:** https://cyberpanel.net
- **Documentation:** https://cyberpanel.net/docs/
- **Community Forum:** https://community.cyberpanel.net

### OpenLiteSpeed Documentation

- **Official Website:** https://openlitespeed.org
- **Documentation:** https://openlitespeed.org/kb/

### Laravel Documentation

- **Official Website:** https://laravel.com
- **Documentation:** https://laravel.com/docs

---

## Support & Troubleshooting

### Get Help

1. **Check Logs:**
   - Laravel: `docker logs app038_laravel`
   - OpenLiteSpeed: `/usr/local/lsws/logs/error.log`
   - CyberPanel: `/usr/local/CyberCP/logs/`

2. **Community Support:**
   - CyberPanel Forum: https://community.cyberpanel.net
   - Laravel Forums: https://laracasts.com/discuss

3. **Documentation:**
   - This guide: `DEPLOY_CYBERPANEL.md`
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

**Last Updated:** 10 December 2025, 17:00 UTC
