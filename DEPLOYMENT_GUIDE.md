# 🚀 Deployment Guide - App038

Panduan lengkap dan mudah diikuti untuk mendeploy aplikasi App038 ke internet menggunakan standar DevOps best practices.

> **📌 Quick Start untuk VPS Hostinger:** VPS Hostinger Anda (IP: `168.231.118.3`, Hostname: `srv1162366.hstgr.cloud`) sudah terverifikasi dan siap untuk deployment! Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk, Ubuntu 24.04 LTS - **Sangat cukup untuk production!** Langsung ke [Opsi 0A: VPS Hostinger dengan Dokploy](#-opsi-0a-vps-hostinger-dengan-dokploy-) - ini adalah cara TERMUDAH dan TERCEPAT untuk deployment!

## 📋 Daftar Isi

1. [Overview Project](#overview-project)
2. [Prasyarat & Tools](#prasyarat--tools)
3. [Strategi Deployment](#strategi-deployment)
4. [Langkah-langkah Deployment (Step-by-Step)](#langkah-langkah-deployment-step-by-step)
   - [Opsi 0A: VPS Hostinger dengan Dokploy](#-opsi-0a-vps-hostinger-dengan-dokploy-) ⭐ **RECOMMENDED**
   - [Opsi 0: VPS Hostinger Deployment Manual](#-opsi-0-vps-hostinger-deployment-manual-)
   - [Opsi 0B: Free Tier Deployment](#-opsi-0b-free-tier-deployment-100-gratis-)
   - [Opsi 1: Docker Compose Deployment](#-opsi-1-docker-compose-deployment-paling-mudah)
   - [Opsi 2: Kubernetes Deployment](#-opsi-2-kubernetes-deployment-recommended)
   - [Opsi 3: Full Infrastructure dengan Terraform](#-opsi-3-full-infrastructure-dengan-terraform-enterprise)
5. [Post-Deployment](#post-deployment)
6. [Monitoring & Maintenance](#monitoring--maintenance)
7. [Troubleshooting](#troubleshooting)
8. [Checklist Deployment](#checklist-deployment)

---

## Overview Project

### Teknologi Stack

**Backend:**
- Laravel 11 LTS (PHP 8.2+)
- PostgreSQL 16 (Database)
- Redis 7 (Cache & Session)
- RabbitMQ (Message Queue)
- Kafka (Event Streaming)

**Frontend:**
- Svelte 4 (Framework)
- Inertia.js (SPA Bridge)
- Vite (Build Tool)
- TailwindCSS (Styling)

**Infrastructure:**
- Docker & Docker Compose
- Kubernetes (EKS)
- Helm Charts
- Terraform (IaC)
- GitHub Actions (CI/CD)

### Arsitektur Aplikasi

```
┌─────────────────────────────────────────────────────────────┐
│                    Internet / Users                          │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Load Balancer / Ingress                        │
│              (SSL Termination)                              │
└───────────────────────┬─────────────────────────────────────┘
                        │
        ┌───────────────┴───────────────┐
        │                               │
        ▼                               ▼
┌──────────────────┐          ┌──────────────────┐
│  Svelte Pods     │          │  Laravel Pods    │
│  (Frontend)      │          │  (Backend API)   │
└────────┬─────────┘          └────────┬─────────┘
         │                             │
         └────────┬────────┬───────────┘
                  │        │
        ┌─────────▼───┐ ┌──▼──────────┐
        │   Redis     │ │  RabbitMQ   │
        │   (Cache)   │ │  (Queue)    │
        └─────────────┘ └─────────────┘
                  │
                  ▼
        ┌──────────────────┐
        │  PostgreSQL RDS  │
        │  (Database)      │
        └──────────────────┘
```

---

## Prasyarat & Tools

### 1. Tools yang Wajib Diinstall

```bash
# Version Control
- Git >= 2.30
- GitHub Account

# Infrastructure
- Terraform >= 1.0
- AWS CLI >= 2.0
- kubectl >= 1.28
- Helm >= 3.13

# Container
- Docker >= 20.10
- Docker Buildx

# Development (untuk testing lokal)
- PHP >= 8.2
- Composer >= 2.0
- Node.js >= 20.x
- npm >= 9.x
```

### 2. Cloud Provider Accounts

**AWS Account** dengan akses ke:
- EC2 (untuk EKS cluster)
- RDS (untuk PostgreSQL)
- S3 (untuk Terraform state & backups)
- Route53 (untuk DNS)
- ACM (untuk SSL certificates)
- IAM (untuk service accounts)

**GitHub Account** dengan:
- Repository access
- GitHub Actions enabled
- GitHub Container Registry (ghcr.io) access

### 3. Domain & DNS

- Domain name yang sudah terdaftar
- Akses ke DNS provider (Route53 atau provider lain)
- Kemampuan untuk membuat A/CNAME records

---

## Strategi Deployment

Kami menyediakan **5 opsi deployment** dari yang paling sederhana hingga enterprise:

### 🚀 Opsi 0A: VPS Hostinger dengan Dokploy (RECOMMENDED untuk VPS) ⭐⭐⭐⭐
**Cocok untuk:** Production deployment dengan kontrol penuh, budget terbatas, single server deployment dengan UI management
- ✅ **Kontrol penuh** - Full root access ke server
- ✅ **Biaya terjangkau** - Mulai dari ~$5-20/bulan
- ✅ **Setup cepat** - 30-60 menit untuk setup lengkap
- ✅ **Web UI Management** - Manage aplikasi via web interface
- ✅ **Auto SSL** - Automatic SSL certificates dengan Let's Encrypt via Traefik
- ✅ **Git Integration** - Auto-deploy dari GitHub/GitLab/Bitbucket
- ✅ **Docker Compose Support** - Full Docker Compose support dengan Traefik
- ✅ **Database Management** - Built-in database management
- ✅ **Monitoring & Logs** - Built-in monitoring dan log viewer
- ✅ **Custom domain** - Support custom domain dengan SSL otomatis
- ⚠️ Single server (tidak high availability)
- ⚠️ Manual scaling (perlu upgrade VPS untuk scale)

**Rekomendasi:** Jika Anda sudah punya VPS Hostinger dan ingin management yang mudah, gunakan **Opsi 0A (Dokploy)** - lihat section detail di bawah.

### 🖥️ Opsi 0: VPS Hostinger Deployment Manual (Alternatif) ⭐⭐⭐
**Cocok untuk:** Production deployment dengan kontrol penuh, budget terbatas, single server deployment
- ✅ **Kontrol penuh** - Full root access ke server
- ✅ **Biaya terjangkau** - Mulai dari ~$5-20/bulan
- ✅ **Setup cepat** - 1-2 jam untuk setup lengkap
- ✅ **Cocok untuk production** - Stable dan reliable
- ✅ **Docker support** - Full Docker & Docker Compose support
- ✅ **Custom domain** - Support custom domain dengan SSL
- ⚠️ Single server (tidak high availability)
- ⚠️ Manual scaling (perlu upgrade VPS untuk scale)
- ⚠️ Manual management (tidak ada web UI)

**Rekomendasi:** Jika Anda sudah punya VPS Hostinger dan lebih suka manual setup, gunakan **Opsi 0 (VPS Hostinger Manual)** - lihat section detail di bawah.

### 🆓 Opsi 0B: Free Tier Deployment (GRATIS) ⭐⭐
**Cocok untuk:** MVP, testing, personal projects, budget-conscious deployments
- ✅ **100% GRATIS** - Tidak ada biaya bulanan
- ✅ Setup cepat (30-60 menit)
- ✅ Cocok untuk low-to-medium traffic
- ✅ Perfect untuk testing dan development
- ⚠️ Limited resources (sesuai free tier limits)
- ⚠️ Mungkin ada downtime jika melebihi quota

**Opsi Free Tier yang Tersedia:**
1. **Fly.io Free Tier** - 3 shared-cpu-1x VMs gratis, PostgreSQL gratis
2. **Railway Free Tier** - $5 credit per bulan, PostgreSQL gratis
3. **Render Free Tier** - Web service gratis, PostgreSQL gratis
4. **Oracle Cloud Free Tier** - 2 VMs gratis (selamanya), PostgreSQL gratis
5. **Docker Compose di VPS Gratis** - Oracle Cloud / AWS Free Tier

**Rekomendasi untuk GRATIS:** Mulai dengan **Opsi 0B (Free Tier)** - lihat section detail di bawah.

### Opsi 1: Docker Compose (Paling Mudah) ⭐
**Cocok untuk:** MVP, testing, development environment
- ✅ Setup cepat (15-30 menit)
- ✅ Tidak perlu Kubernetes
- ✅ Cocok untuk single server
- ❌ Tidak scalable
- ❌ Tidak high availability
- 💰 Biaya: VPS server (~$5-10/bulan)

### Opsi 2: Kubernetes dengan Helm (Recommended untuk Production) ⭐⭐
**Cocok untuk:** Production, scalable applications
- ✅ High availability
- ✅ Auto-scaling
- ✅ Zero-downtime deployment
- ✅ Production-ready
- ❌ Setup lebih kompleks (1-2 jam)
- 💰 Biaya: ~$120-250/bulan (AWS EKS + RDS)

### Opsi 3: Full Infrastructure dengan Terraform (Enterprise) ⭐⭐⭐
**Cocok untuk:** Enterprise, multi-region, compliance
- ✅ Infrastructure as Code
- ✅ Multi-AZ deployment
- ✅ Automated provisioning
- ✅ Disaster recovery
- ❌ Setup paling kompleks (2-4 jam)
- 💰 Biaya: ~$120-250/bulan (AWS EKS + RDS)

**Rekomendasi:** 
- **Untuk VPS Hostinger dengan UI Management:** Gunakan **Opsi 0A (Dokploy)** - Lihat detail di bawah ⬇️ ⭐ **RECOMMENDED**
- **Untuk VPS Hostinger Manual:** Gunakan **Opsi 0 (VPS Hostinger Manual)** - Lihat detail di bawah ⬇️
- **Untuk GRATIS:** Gunakan **Opsi 0B (Free Tier Deployment)** - Lihat detail di bawah ⬇️
- **Untuk Production dengan Budget:** Mulai dengan **Opsi 2 (Kubernetes)** untuk production.

---

## Langkah-langkah Deployment (Step-by-Step)

### 🚀 Opsi 0A: VPS Hostinger dengan Dokploy ⭐⭐⭐⭐

**Perfect untuk:** Production deployment dengan web UI management, auto SSL, dan Git integration

> **🎯 Quick Start untuk VPS Anda:** VPS Hostinger Anda (IP: `168.231.118.3`) sudah terverifikasi dan siap untuk deployment! Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk, Ubuntu 24.04 LTS - **Sangat cukup untuk production!** Langsung ke [Step 0: Verifikasi VPS Hostinger](#step-0-verifikasi-vps-hostinger-penting---lakukan-sebelum-deployment) untuk memulai.

#### Prasyarat VPS Hostinger

1. **VPS Hostinger sudah aktif** dengan:
   - OS: Ubuntu 22.04 LTS atau 20.04 LTS (recommended)
   - Minimum: 2GB RAM, 2 CPU cores, 40GB storage
   - Recommended: 4GB RAM, 4 CPU cores, 80GB storage untuk production
   - Root access atau sudo access
   - IP address publik

2. **Domain name** (opsional tapi recommended):
   - Domain sudah terdaftar
   - Akses ke DNS management
   - A record bisa di-set ke IP VPS

#### ✅ Informasi VPS Hostinger Anda (Sudah Terverifikasi)

Berdasarkan data dari Hostinger API, VPS Anda sudah siap untuk deployment:

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

**Catatan:** Spesifikasi VPS Anda sudah sangat baik untuk production deployment dengan Dokploy!

#### Step 0: Verifikasi VPS Hostinger (PENTING - Lakukan Sebelum Deployment)

> **✅ VPS Anda Sudah Terverifikasi!** 
> - IP: `168.231.118.3`
> - Status: Running (Active)
> - Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk, Ubuntu 24.04 LTS
> - **Siap untuk deployment!**

**0.1. Cek VPS Hostinger yang Tersedia**

Sebelum memulai deployment, pastikan VPS Hostinger Anda sudah aktif dan siap digunakan:

1. **Login ke Hostinger hPanel:**
   - Buka https://hpanel.hostinger.com
   - Login dengan akun Hostinger Anda
   - Navigate ke **VPS** section

2. **Verifikasi VPS Status:**
   - Pastikan VPS status: **Active/Running**
   - Catat **IP Address** VPS (akan digunakan untuk DNS setup)
   - Catat **Username** dan pastikan Anda punya akses SSH

3. **Cek Spesifikasi VPS:**
   - Minimum requirement: 2GB RAM, 2 CPU cores, 40GB storage
   - Recommended untuk production: 4GB RAM, 4 CPU cores, 80GB storage
   - Jika kurang, pertimbangkan upgrade VPS sebelum deployment

**0.2. Test Koneksi SSH ke VPS**

```bash
# Test SSH connection
# Ganti your-vps-ip dengan IP VPS Anda (contoh: 168.231.118.3)
ssh root@your-vps-ip
# Atau jika menggunakan username lain
ssh username@your-vps-ip

# Contoh dengan IP spesifik:
# ssh root@168.231.118.3

# Jika berhasil, Anda akan masuk ke VPS
# Test basic commands untuk verifikasi
whoami        # Harus return: root (atau username Anda)
hostname      # Menampilkan hostname VPS
free -h       # Check RAM usage
df -h         # Check disk space
uname -a      # Check OS version
```

**Troubleshooting SSH Connection:**

Jika tidak bisa connect, check:

1. **Host Key Verification Failed (WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED):**
   
   **Masalah:** Host key untuk VPS sudah berubah (biasanya setelah VPS di-reinstall atau diubah).
   
   **Solusi - Opsi 1 (Recommended):**
   ```bash
   # Hapus old host key untuk IP tersebut
   ssh-keygen -R 168.231.118.3
   # Atau untuk semua IP
   ssh-keygen -R [IP_VPS_ANDA]
   ```
   
   **Solusi - Opsi 2 (Manual):**
   ```bash
   # Edit known_hosts dan hapus line yang mengandung IP tersebut
   # Line yang error biasanya ditunjukkan di pesan error
   # Contoh: "Offending ECDSA key in /Users/fyi/.ssh/known_hosts:3"
   # Hapus line 3 (atau line yang disebutkan)
   nano ~/.ssh/known_hosts
   # Atau menggunakan sed (untuk macOS)
   sed -i '' '3d' ~/.ssh/known_hosts
   # Atau untuk Linux
   sed -i '3d' ~/.ssh/known_hosts
   ```
   
   **Setelah fix, connect lagi:**
   ```bash
   ssh root@168.231.118.3
   # Akan muncul prompt untuk accept new host key
   # Ketik: yes
   ```
   
   **Catatan:** Ini aman dilakukan jika Anda yakin VPS memang di-reinstall atau diubah. Host key berubah adalah normal setelah VPS di-reinstall.

2. **Firewall blocking SSH:**
   ```bash
   # Di VPS (via console atau hPanel)
   sudo ufw status
   sudo ufw allow 22/tcp
   ```

3. **SSH service tidak running:**
   ```bash
   # Di VPS
   sudo systemctl status ssh
   sudo systemctl start ssh
   ```

4. **Wrong credentials:**
   - Pastikan username benar (biasanya `root` untuk VPS Hostinger)
   - Reset password via Hostinger hPanel jika perlu

5. **IP address blocked:**
   - Check Hostinger hPanel untuk IP restrictions
   - Whitelist IP Anda jika ada IP whitelist enabled

**0.3. Verifikasi Port yang Tersedia**

Pastikan port berikut tidak digunakan oleh aplikasi lain:
- Port 80 (HTTP)
- Port 443 (HTTPS)
- Port 3000 (Dokploy UI - bisa diubah)
- Port 22 (SSH - harus selalu terbuka)

```bash
# Check port yang sedang digunakan
sudo netstat -tulpn | grep -E ':(80|443|3000|22)'
# Atau menggunakan ss
sudo ss -tulpn | grep -E ':(80|443|3000|22)'
```

**0.4. Catat Informasi Penting**

**Informasi VPS Anda (Sudah Terverifikasi):**
- ✅ VPS IP Address: `168.231.118.3`
- ✅ VPS Hostname: `srv1162366.hstgr.cloud`
- ✅ VPS Username: `root` (default untuk VPS Hostinger)
- ✅ VPS Plan: KVM 2 (2 CPUs, 8GB RAM, 100GB Disk)
- ✅ OS: Ubuntu 24.04 LTS
- ✅ Domain name (jika ada): `_________________`
- ✅ DNS Provider: `_________________`

**Quick Connect Command:**
```bash
# SSH command dengan IP VPS Anda
ssh root@168.231.118.3

# Setelah connect, verifikasi informasi:
whoami        # Harus return: root
hostname      # Harus return: srv1162366.hstgr.cloud
uname -a      # Harus show: Ubuntu 24.04
free -h       # Harus show: ~8GB RAM
df -h         # Harus show: ~100GB disk
hostname -I   # Harus show: 168.231.118.3
```

**Catatan:** Jika mengalami error "REMOTE HOST IDENTIFICATION HAS CHANGED", jalankan:
```bash
ssh-keygen -R 168.231.118.3
# Lalu connect lagi
ssh root@168.231.118.3
```

**0.5. Persiapan Domain (Jika Menggunakan Custom Domain)**

Jika Anda menggunakan custom domain, siapkan DNS records berikut di DNS provider:

```
A Record:
- Name: @ (atau kosong)
- Value: [IP VPS Anda]
- TTL: 3600

A Record untuk www:
- Name: www
- Value: [IP VPS Anda]
- TTL: 3600

A Record untuk api (opsional):
- Name: api
- Value: [IP VPS Anda]
- TTL: 3600
```

**Catatan:** DNS propagation bisa memakan waktu 5 menit sampai 24 jam. Setup DNS sekarang akan menghemat waktu nanti.

#### Step 1: Install Dokploy di VPS Hostinger

**1.1. Login ke VPS via SSH**

```bash
# SSH ke VPS Hostinger
ssh root@your-vps-ip
# Atau jika menggunakan username lain
ssh username@your-vps-ip

# Update system
sudo apt update && sudo apt upgrade -y
```

**1.2. Install Docker (jika belum terinstall)**

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

**1.3. Install Dokploy**

**Metode 1: Install via Git Clone (Recommended)**

```bash
# Clone Dokploy repository
git clone https://github.com/dokploy/dokploy.git
cd dokploy

# Copy environment file
cp .env.example .env

# Edit environment file (opsional, default sudah cukup)
# Minimal yang perlu diubah:
# - PORT (default: 3000) - ubah jika port 3000 sudah digunakan
# - JWT_SECRET - generate random string untuk security
nano .env

# Generate JWT_SECRET (jika belum ada)
# Di terminal lain atau sebelum edit .env:
JWT_SECRET=$(openssl rand -base64 32)
echo "JWT_SECRET=$JWT_SECRET" >> .env

# Start Dokploy
docker-compose up -d

# Wait for Dokploy to start (sekitar 30-60 detik)
sleep 60

# Check status
docker-compose ps

# Check logs jika ada masalah
docker-compose logs -f
```

**Metode 2: Install via Automated Script (Alternatif)**

Dokploy menyediakan automated installation script:

```bash
# Download dan run installation script
curl -fsSL https://get.dokploy.com | sh

# Script akan otomatis:
# - Install Docker (jika belum ada)
# - Install Docker Compose
# - Setup Dokploy
# - Start services

# Setelah selesai, akses Dokploy di http://your-vps-ip:3000
```

**Verifikasi Installasi:**

```bash
# Check Docker containers
docker ps | grep dokploy

# Check Dokploy network
docker network ls | grep dokploy

# Check port 3000 listening
sudo netstat -tulpn | grep 3000
# Atau
sudo ss -tulpn | grep 3000
```

**1.4. Setup Firewall (UFW)**

```bash
# Install UFW jika belum ada
sudo apt install ufw -y

# Allow SSH (penting! jangan skip ini)
sudo ufw allow 22/tcp

# Allow HTTP dan HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow Dokploy port (default: 3000, bisa diubah di .env)
sudo ufw allow 3000/tcp

# Enable firewall
sudo ufw --force enable

# Check status
sudo ufw status
```

**1.5. Akses Dokploy Web UI**

```bash
# Get VPS IP address
curl ifconfig.me

# Akses Dokploy di browser:
# http://168.231.118.3:3000
# atau jika sudah setup domain:
# http://dokploy.yourdomain.com:3000
```

**1.6. Setup Initial Admin User**

1. Buka browser dan akses `http://168.231.118.3:3000`
   - Jika tidak bisa akses, check firewall dan pastikan port 3000 sudah di-allow
   - Tunggu beberapa detik jika Dokploy masih starting up

2. **Setup Wizard akan muncul:**
   - **Create Admin Account:**
     - Username: (pilih username yang aman)
     - Email: (email Anda)
     - Password: (password yang kuat, minimal 8 karakter)
   - Klik **"Create Account"**

3. **Login dengan credentials yang sudah dibuat:**
   - Setelah account dibuat, Anda akan otomatis login
   - Atau login manual dengan username/password yang baru dibuat

**Troubleshooting jika Setup Wizard tidak muncul:**
```bash
# Check Dokploy logs
cd /path/to/dokploy
docker-compose logs dokploy

# Restart Dokploy
docker-compose restart dokploy

# Check database connection
docker-compose exec postgres psql -U dokploy -d dokploy -c "SELECT 1;"
```

#### Step 2: Setup Project di Dokploy

**2.1. Create New Project**

1. Di Dokploy dashboard, klik **"New Project"**
2. Isi:
   - **Name**: `app038`
   - **Description**: `Laravel 11 + Svelte Application`
3. Klik **"Create"**

**2.2. Connect GitHub Repository (Recommended)**

1. Di project `app038`, klik **"Settings"** → **"Source"**
2. Pilih **"GitHub"** (atau GitLab/Bitbucket)
3. Authorize Dokploy untuk akses repository
4. Pilih repository: `YOUR_USERNAME/app038`
5. Pilih branch: `main` (atau branch yang diinginkan)
6. Klik **"Connect"**

**Atau Manual Upload:**

Jika tidak menggunakan Git, Anda bisa:

1. **Clone Repository Langsung di VPS:**
   ```bash
   # SSH ke VPS
   ssh root@your-vps-ip
   
   # Clone repository
   cd /opt  # atau directory lain yang sesuai
   git clone https://github.com/YOUR_USERNAME/app038.git
   cd app038
   ```

2. **Upload via SCP/SFTP:**
   ```bash
   # Dari local machine
   scp -r /path/to/app038 root@your-vps-ip:/opt/app038
   ```

3. **Manual Upload via Dokploy UI:**
   - Di Dokploy, pilih **"Upload Files"** (jika tersedia)
   - Upload project files sebagai archive (zip/tar.gz)
   - Extract di VPS

**Catatan:** Metode Git integration lebih recommended karena memudahkan auto-deploy dan version control.

#### Step 3: Setup Docker Compose di Dokploy

**3.1. Create Docker Compose Application**

1. Di project `app038`, klik **"New Application"**
2. Pilih **"Docker Compose"**
3. Isi:
   - **Name**: `app038-production`
   - **Description**: `Production deployment`

**3.2. Configure Docker Compose File**

1. Di section **"Docker Compose"**, paste isi dari `docker-compose.dokploy.yml`:

```yaml
version: '3.8'

services:
  laravel:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    restart: always
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
      - ../files/app038/storage:/app/storage
      - ../files/app038/bootstrap/cache:/app/bootstrap/cache
    networks:
      - dokploy-network
    depends_on:
      - postgres
      - redis
      - rabbitmq
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app038-api.rule=Host(`api.yourdomain.com`)"
      - "traefik.http.routers.app038-api.entrypoints=websecure"
      - "traefik.http.routers.app038-api.tls.certResolver=letsencrypt"
      - "traefik.http.services.app038-api.loadbalancer.server.port=80"

  svelte:
    build:
      context: .
      dockerfile: docker/svelte/Dockerfile
    restart: always
    ports:
      - "80"
    networks:
      - dokploy-network
    depends_on:
      - laravel
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 5s
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app038-web.rule=Host(`yourdomain.com`) || Host(`www.yourdomain.com`)"
      - "traefik.http.routers.app038-web.entrypoints=websecure"
      - "traefik.http.routers.app038-web.tls.certResolver=letsencrypt"
      - "traefik.http.services.app038-web.loadbalancer.server.port=80"

  postgres:
    image: postgres:15-alpine
    restart: always
    environment:
      - POSTGRES_DB=${DB_DATABASE}
      - POSTGRES_USER=${DB_USERNAME}
      - POSTGRES_PASSWORD=${DB_PASSWORD}
    volumes:
      - ../files/app038/postgres:/var/lib/postgresql/data
    networks:
      - dokploy-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    restart: always
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - ../files/app038/redis:/data
    networks:
      - dokploy-network
    healthcheck:
      test: ["CMD", "redis-cli", "--raw", "incr", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5

  rabbitmq:
    image: rabbitmq:3-management-alpine
    restart: always
    environment:
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
      RABBITMQ_DEFAULT_VHOST: /
    volumes:
      - ../files/app038/rabbitmq:/var/lib/rabbitmq
    networks:
      - dokploy-network
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "ping"]
      interval: 30s
      timeout: 10s
      retries: 5
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app038-rabbitmq.rule=Host(`rabbitmq.yourdomain.com`)"
      - "traefik.http.routers.app038-rabbitmq.entrypoints=websecure"
      - "traefik.http.routers.app038-rabbitmq.tls.certResolver=letsencrypt"
      - "traefik.http.services.app038-rabbitmq.loadbalancer.server.port=15672"

networks:
  dokploy-network:
    external: true
```

**PENTING:** Ganti `yourdomain.com` dengan domain Anda di semua Traefik labels!

**3.3. Configure Environment Variables**

1. Di section **"Environment Variables"** di Dokploy UI (`http://168.231.118.3:3000`), tambahkan variables berikut:

**Environment Variables Wajib (Minimum):**
```env
# Application Configuration
APP_NAME=App038
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com
# Atau jika belum punya domain: APP_URL=http://168.231.118.3

# Database Configuration
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=your_strong_password_here

# Redis Configuration
REDIS_PASSWORD=your_redis_password_here

# RabbitMQ Configuration
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=your_rabbitmq_password_here
```

**Catatan Penting:**
- Ganti `yourdomain.com` dengan domain Anda (atau gunakan IP `168.231.118.3` jika belum punya domain)
- Generate strong passwords untuk DB_PASSWORD, REDIS_PASSWORD, dan RABBITMQ_PASSWORD
- APP_KEY harus di-generate (lihat Step 3.2)

**Environment Variables Tambahan (Recommended):**
```env
# Mail Configuration (jika diperlukan)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging Configuration
LOG_CHANNEL=stack
LOG_LEVEL=info

# Session Configuration
SESSION_LIFETIME=120
SESSION_DRIVER=redis

# Cache Configuration
CACHE_DRIVER=redis

# Sanctum Configuration (jika menggunakan API)
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

**Cara Generate Strong Passwords:**

```bash
# Generate password untuk database
openssl rand -base64 32

# Generate password untuk Redis
openssl rand -base64 32

# Generate password untuk RabbitMQ
openssl rand -base64 32

# Generate APP_KEY (sudah dijelaskan di Step 3.2)
```

**Catatan Penting:**
- Simpan semua passwords dengan aman (password manager recommended)
- Jangan commit passwords ke Git
- Gunakan password yang berbeda untuk setiap service
- Minimal 32 karakter untuk production passwords

**Generate APP_KEY:**

```bash
# Opsi 1: Generate di local machine (jika sudah install PHP)
php artisan key:generate --show
# Copy output (format: base64:xxxxx) ke APP_KEY di Dokploy

# Opsi 2: Generate di VPS (install PHP temporary)
# SSH ke VPS
ssh root@your-vps-ip

# Install PHP CLI (temporary, untuk generate key saja)
sudo apt install php-cli php-mbstring -y

# Clone atau copy project ke VPS
cd /tmp
git clone https://github.com/YOUR_USERNAME/app038.git
cd app038

# Generate APP_KEY
php artisan key:generate --show
# Copy output ke Dokploy environment variables

# Cleanup (opsional)
sudo apt remove php-cli php-mbstring -y

# Opsi 3: Generate manual (tidak recommended, kurang secure)
# Gunakan openssl untuk generate random key
openssl rand -base64 32
# Tambahkan prefix "base64:" di depan hasil
# Contoh: base64:YOUR_GENERATED_KEY_HERE
```

**PENTING:** Simpan APP_KEY dengan aman! Key ini digunakan untuk encrypt session dan data sensitif.

**3.4. Verify dokploy-network (PENTING - Lakukan Sebelum Deployment)**

**PENTING:** Dokploy otomatis membuat network `dokploy-network` saat install. Pastikan network ini sudah ada sebelum deployment:

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Check apakah dokploy-network sudah ada
docker network ls | grep dokploy-network

# Expected output:
# dokploy-network   bridge    local

# Jika network tidak ada, Dokploy mungkin belum fully started
# Tunggu beberapa detik dan check lagi, atau restart Dokploy:
cd /path/to/dokploy
docker-compose restart

# Verify network details
docker network inspect dokploy-network
```

**Troubleshooting jika dokploy-network tidak ada:**

1. **Dokploy belum fully started:**
   ```bash
   # Check Dokploy containers
   cd /path/to/dokploy
   docker-compose ps
   
   # Semua containers harus "Up" atau "Up (healthy)"
   # Jika ada yang "Restarting" atau "Exited", check logs:
   docker-compose logs
   ```

2. **Network tidak dibuat otomatis:**
   ```bash
   # Create network manually (jika diperlukan)
   docker network create dokploy-network
   
   # Verify
   docker network ls | grep dokploy-network
   ```

**Catatan:** Network `dokploy-network` adalah external network yang dibuat oleh Dokploy. Semua services di docker-compose harus menggunakan network ini untuk komunikasi dengan Traefik dan services lain.

**3.5. Configure Volumes (Opsional)**

Dokploy akan otomatis membuat volumes di `../files/app038/`. Path `../files/` adalah relative path dari project directory di Dokploy.

**Penjelasan Volume Paths:**

- `../files/app038/storage` → Dokploy akan membuat directory di `/path/to/dokploy/files/app038/storage`
- `../files/app038/postgres` → Dokploy akan membuat directory di `/path/to/dokploy/files/app038/postgres`
- `../files/app038/redis` → Dokploy akan membuat directory di `/path/to/dokploy/files/app038/redis`
- `../files/app038/rabbitmq` → Dokploy akan membuat directory di `/path/to/dokploy/files/app038/rabbitmq`

**Mengapa menggunakan `../files/`?**

- ✅ Dokploy otomatis manage directory ini
- ✅ Data persistent across deployments
- ✅ Backup lebih mudah (semua data di satu tempat)
- ✅ Relative path memudahkan portability

**Jika perlu custom volumes:**

1. Klik **"Advanced"** → **"Volumes"** di Dokploy UI
2. Tambahkan volume mounts jika diperlukan
3. Atau edit docker-compose langsung di Dokploy UI

**3.6. File Mount Feature (Opsional - Untuk Config Files)**

Jika Anda perlu mount config files dari Dokploy File Manager:

1. Di Dokploy UI, buat file via **"File Mount"** feature
2. File akan tersimpan di `/files/` directory
3. Reference file di docker-compose:

```yaml
volumes:
  - ../files/my-config.json:/etc/my-app/config.json
```

**Contoh penggunaan:**
- Mount `.env` file (jika tidak menggunakan environment variables)
- Mount SSL certificates (jika custom certificates)
- Mount config files untuk services

#### Step 4: Setup Domain di Dokploy

**PENTING:** Setup domain di Dokploy SEBELUM deployment untuk memastikan SSL certificates terbit dengan benar.

**4.1. Add Domain untuk Web (Svelte Frontend)**

1. Di application `app038-production`, klik tab **"Domains"** atau **"Settings"** → **"Domains"**
2. Klik **"Add Domain"** atau **"New Domain"**
3. Isi form:
   - **Domain**: `yourdomain.com` (ganti dengan domain Anda)
   - **Port**: `80` (port internal dari service svelte, bukan port host)
   - **Service**: `svelte` (nama service dari docker-compose)
   - **Enable SSL**: ✅ (centang untuk auto SSL)
4. Klik **"Add"** atau **"Save"**

**4.2. Add Domain untuk www (Opsional tapi Recommended)**

1. Klik **"Add Domain"** lagi
2. Isi:
   - **Domain**: `www.yourdomain.com`
   - **Port**: `80`
   - **Service**: `svelte`
   - **Enable SSL**: ✅
3. Klik **"Add"**

**4.3. Add Domain untuk API (Laravel Backend) - Opsional**

Jika Anda ingin memisahkan API dengan subdomain terpisah:

1. Klik **"Add Domain"** lagi
2. Isi:
   - **Domain**: `api.yourdomain.com`
   - **Port**: `80` (port internal dari service laravel)
   - **Service**: `laravel`
   - **Enable SSL**: ✅
3. Klik **"Add"**

**4.4. Add Domain untuk RabbitMQ Management - Opsional**

Untuk akses RabbitMQ Management UI:

1. Klik **"Add Domain"** lagi
2. Isi:
   - **Domain**: `rabbitmq.yourdomain.com`
   - **Port**: `15672` (port management UI dari service rabbitmq)
   - **Service**: `rabbitmq`
   - **Enable SSL**: ✅
3. Klik **"Add"**

**Catatan Penting:**
- Dokploy akan otomatis generate SSL certificates via Let's Encrypt untuk semua domain yang ditambahkan
- Pastikan DNS sudah pointing ke VPS IP sebelum deployment (untuk SSL certificate generation)
- SSL certificate generation memakan waktu 1-5 menit setelah deployment
- Jika SSL gagal, check DNS propagation dan pastikan domain sudah pointing ke VPS

**Verifikasi Domain Setup:**

Setelah menambahkan domain, verifikasi di Dokploy UI:
1. Klik **"Domains"** tab
2. Pastikan semua domain muncul dengan status **"Active"** atau **"Pending SSL"**
3. Status akan berubah ke **"Active"** setelah SSL certificate terbit

#### Step 5: Setup DNS Records

**Di DNS provider Anda (Hostinger, Cloudflare, dll):**

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

3. **A Record untuk api (jika menggunakan subdomain):**
   ```
   Type: A
   Name: api
   Value: 168.231.118.3
   TTL: 3600 (atau auto)
   ```

4. **A Record untuk rabbitmq (jika menggunakan subdomain):**
   ```
   Type: A
   Name: rabbitmq
   Value: 168.231.118.3
   TTL: 3600 (atau auto)
   ```

**Catatan:** Ganti `168.231.118.3` dengan IP VPS Anda jika berbeda.

**Verifikasi DNS:**

```bash
# Check DNS propagation dari local machine
dig yourdomain.com
nslookup yourdomain.com

# Check dari VPS
ssh root@168.231.118.3
dig yourdomain.com
nslookup yourdomain.com

# Check apakah domain pointing ke IP VPS yang benar
dig +short yourdomain.com
# Output harus sama dengan: 168.231.118.3

# Test HTTP connection (sebelum SSL)
curl -I http://yourdomain.com
# Harus return HTTP response (bisa 301 redirect ke HTTPS jika SSL sudah aktif)

# Tunggu beberapa menit untuk DNS propagate (bisa sampai 24 jam)
# Biasanya 5-30 menit untuk sebagian besar DNS provider
```

**Tips DNS Propagation:**
- Gunakan DNS checker online: https://dnschecker.org
- Set TTL ke nilai rendah (300-600) sebelum setup untuk faster propagation
- Setelah setup selesai, bisa naikkan TTL ke 3600 untuk better performance

#### Step 6: Deploy Application

**6.1. Deploy via Dokploy UI**

1. Di application `app038-production`, klik **"Deploy"**
2. Dokploy akan:
   - Clone repository (jika menggunakan Git)
   - Build Docker images
   - Start containers
   - Setup Traefik routing
   - Generate SSL certificates
3. Tunggu deployment selesai (bisa 5-15 menit tergantung build time)
4. Monitor progress di **"Deployments"** tab

**6.2. Verify dokploy-network Before Deployment (PENTING)**

Sebelum deployment, pastikan dokploy-network sudah tersedia:

```bash
# Via SSH ke VPS (jika perlu verify manual)
ssh root@168.231.118.3

# Check network
docker network ls | grep dokploy-network

# Expected: dokploy-network harus ada
# Jika tidak ada, deployment akan gagal dengan error:
# "network dokploy-network declared as external, but could not be found"
```

**Jika network tidak ada:**

1. **Dokploy belum fully started:**
   - Tunggu beberapa menit setelah install Dokploy
   - Check Dokploy containers: `docker ps | grep dokploy`
   - Restart Dokploy jika perlu: `cd /path/to/dokploy && docker-compose restart`

2. **Create network manually (last resort):**
   ```bash
   docker network create dokploy-network
   ```

**Catatan:** Dokploy otomatis membuat network ini saat install. Jika network tidak ada, ada kemungkinan Dokploy belum fully started atau ada issue dengan installasi.

**6.3. Check Deployment Status**

1. Klik **"Deployments"** tab atau **"Logs"** tab
2. Monitor deployment progress:
   - **Status akan berubah:**
     - `Pending` → `Building` → `Deploying` → `Running` (success)
     - Atau `Failed` (jika ada error)
   
3. **Monitor Logs Real-time:**
   - Klik pada deployment untuk melihat detailed logs
   - Logs akan menampilkan:
     - Docker build progress
     - Container startup
     - Health check status
     - Error messages (jika ada)

4. **Common Deployment Status:**
   - ✅ **Running**: Deployment berhasil, aplikasi online
   - ⏳ **Building**: Docker images sedang di-build
   - ⏳ **Deploying**: Containers sedang di-start
   - ❌ **Failed**: Ada error, check logs untuk detail

**Troubleshooting Deployment:**

Jika deployment gagal, check:
1. **Build Errors:**
   - **Error: "docker/svelte/default.conf: not found"**
     - **Penyebab:** File `.dockerignore` mengexclude folder `docker/svelte`
     - **Solusi:** 
       1. Pull latest changes: `git pull origin main` (file sudah diupdate)
       2. Verify `.dockerignore` tidak mengexclude `docker/svelte`
       3. Rebuild: `docker-compose build --no-cache svelte`
   - Check Dockerfile syntax
   - Verify build context dan paths
   - Check disk space: `df -h`
   - Check Docker build logs di Dokploy UI

2. **Network Errors:**
   - **Error: "network dokploy-network declared as external, but could not be found"**
     - **Solusi:** Verify dokploy-network sudah dibuat (lihat Step 6.2)
     - Check: `docker network ls | grep dokploy-network`
     - Jika tidak ada, restart Dokploy atau create network manually
   
   - **Error: "Cannot connect to dokploy-network"**
     - **Solusi:** Check Dokploy containers running
     - Restart Dokploy: `cd /path/to/dokploy && docker-compose restart`

3. **Container Startup Errors:**
   - Check environment variables (semua required vars sudah di-set?)
   - Check network connectivity (dokploy-network exists?)
   - Check port conflicts
   - Verify volumes paths (../files/app038/...)
   - Check container logs di Dokploy UI

4. **Health Check Failures:**
   - Verify health endpoint exists: `/health`
   - Check application logs
   - Verify dependencies (database, redis, dll) sudah running
   - Check health check configuration di docker-compose

#### Step 7: Setup Database

**7.1. Run Migrations**

Setelah deployment selesai, run migrations:

1. Di Dokploy UI, klik **"Terminal"** tab
2. Pilih service: `laravel`
3. Run command:

```bash
php artisan migrate --force
```

**7.2. Run Seeders (Opsional)**

```bash
php artisan db:seed --force
```

**7.3. Verify Database Connection**

```bash
php artisan tinker
# Di dalam tinker:
DB::connection()->getPdo();
exit
```

#### Step 8: Verify Deployment

**8.1. Check Health Endpoint**

```bash
# Via terminal di Dokploy Terminal tab
# Pilih service: laravel atau svelte
curl http://localhost/health

# Expected output: HTTP 200 OK dengan JSON response
# Contoh: {"status":"ok","timestamp":"..."}

# Atau via browser
https://yourdomain.com/health
https://api.yourdomain.com/health  # jika menggunakan subdomain API
```

**8.2. Check Application Status**

1. **Via Dokploy UI:**
   - Klik **"Applications"** → `app038-production`
   - Check semua services status: harus **Running** (green)
   - Check resource usage (CPU, Memory)

2. **Via Browser:**
   - Buka: `https://yourdomain.com`
   - Website harus sudah bisa diakses dengan SSL (HTTPS)
   - Check SSL certificate: klik lock icon di browser
   - Test login dan functionality

3. **Via Command Line (SSH ke VPS):**
   ```bash
   # Check running containers
   docker ps | grep app038
   
   # Check container logs
   docker logs app038_laravel --tail 50
   docker logs app038_svelte --tail 50
   
   # Check network connectivity
   docker exec app038_laravel ping -c 3 postgres
   docker exec app038_laravel ping -c 3 redis
   ```

**8.3. Check Logs**

1. **Via Dokploy UI:**
   - Klik **"Logs"** tab
   - Pilih service untuk melihat logs:
     - `laravel`: Application logs
     - `svelte`: Frontend logs
     - `postgres`: Database logs
     - `redis`: Cache logs
     - `rabbitmq`: Queue logs
   - Monitor untuk errors, warnings, atau critical messages

2. **Via SSH (Advanced):**
   ```bash
   # Real-time logs
   docker logs -f app038_laravel
   
   # Last 100 lines
   docker logs --tail 100 app038_laravel
   
   # Logs dengan timestamp
   docker logs -f --timestamps app038_laravel
   ```

**8.4. Verify SSL Certificates**

```bash
# Check SSL certificate via command line
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Atau menggunakan curl
curl -vI https://yourdomain.com

# Expected: Certificate should be valid dan issued by Let's Encrypt
```

**8.5. Performance Check**

```bash
# Check response time
time curl -s https://yourdomain.com/health

# Check database connection
# Via Dokploy Terminal, pilih service: laravel
php artisan tinker
# Di dalam tinker:
DB::connection()->getPdo();
Cache::put('test', 'value', 60);
Cache::get('test');
exit
```

**8.6. Final Verification Checklist**

- [ ] ✅ Health endpoint return 200 OK
- [ ] ✅ Website accessible via HTTPS
- [ ] ✅ SSL certificate valid (Let's Encrypt)
- [ ] ✅ All services running (laravel, svelte, postgres, redis, rabbitmq)
- [ ] ✅ Database connection working
- [ ] ✅ Cache (Redis) working
- [ ] ✅ No critical errors in logs
- [ ] ✅ Application functionality tested (login, dll)

#### Step 9: Setup Auto-Deploy dari Git (Opsional)

**9.1. Enable Webhook**

1. Di application settings, klik **"Settings"** → **"Webhooks"**
2. Enable **"Auto Deploy on Push"**
3. Copy webhook URL

**9.2. Setup GitHub Webhook**

1. Go to GitHub repository → **Settings** → **Webhooks**
2. Click **"Add webhook"**
3. Paste webhook URL dari Dokploy
4. Content type: `application/json`
5. Events: `Just the push event`
6. Click **"Add webhook"**

Sekarang setiap push ke branch `main` akan otomatis trigger deployment!

#### Step 10: Post-Deployment Setup

**10.1. Setup Queue Workers (jika diperlukan)**

Jika aplikasi menggunakan queue workers, tambahkan service di docker-compose:

```yaml
queue-worker:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  restart: always
  command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
  environment:
    # Same as laravel service
  volumes:
    - ../files/app038/storage:/app/storage
  networks:
    - dokploy-network
  depends_on:
    - postgres
    - redis
    - rabbitmq
```

**10.2. Setup Scheduled Tasks (Cron)**

Tambahkan service untuk Laravel scheduler:

```yaml
scheduler:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  restart: always
  command: php artisan schedule:work
  environment:
    # Same as laravel service
  volumes:
    - ../files/app038/storage:/app/storage
  networks:
    - dokploy-network
  depends_on:
    - postgres
    - redis
```

#### Troubleshooting Dokploy

**Issue: Cannot access Dokploy UI**

```bash
# Check Dokploy container
cd /path/to/dokploy
docker-compose ps

# Check logs
docker-compose logs

# Restart Dokploy
docker-compose restart
```

**Issue: Deployment failed**

1. Check logs di **"Deployments"** tab
2. Common issues:
   - **Build errors:** Check Dockerfile, build context, disk space
   - **Environment variables missing:** Add di Dokploy UI
   - **Port conflicts:** Check port configuration (gunakan `ports: - "80"` format, bukan `80:80`)
   - **Network issues:** 
     - Error: "network dokploy-network declared as external, but could not be found"
     - **Solusi:** Verify dokploy-network exists: `docker network ls | grep dokploy-network`
     - Jika tidak ada, restart Dokploy atau create manually: `docker network create dokploy-network`
   - **Volume path errors:** Pastikan menggunakan `../files/app038/...` format
   - **Health check failures:** Verify `/health` endpoint exists dan accessible

**Issue: SSL certificate tidak terbit**

1. Verify DNS sudah pointing ke VPS
2. Check Traefik logs:
   ```bash
   docker logs dokploy-traefik
   ```
3. Wait 10-30 detik setelah deployment untuk certificate generation

**Issue: Container Restarting (Crash Loop)**

**Error:** `Error response from daemon: Container ... is restarting, wait until the container is running.`

**Penyebab:** Container terus-menerus crash dan restart, biasanya karena:
- Missing APP_KEY
- Database connection error
- Missing environment variables
- Storage permissions
- Missing dependencies

**Solusi:**

1. **Check logs (PENTING!):**
   ```bash
   docker logs app038_laravel --tail 100
   # Look for error messages
   ```

2. **Check APP_KEY:**
   ```bash
   grep APP_KEY .env
   # Jika kosong, generate:
   APP_KEY_VALUE=$(openssl rand -base64 32)
   sed -i "s/APP_KEY=.*/APP_KEY=base64:${APP_KEY_VALUE}/" .env
   docker-compose restart laravel
   ```

3. **Check dependencies running:**
   ```bash
   docker ps | grep -E "postgres|redis|rabbitmq"
   # Semua harus "Up"
   ```

4. **Check environment variables:**
   ```bash
   cat .env | grep -E "APP_KEY|DB_|REDIS_|RABBITMQ_"
   # Pastikan semua required variables ada
   ```

5. **Stop container dan check logs detail:**
   ```bash
   docker stop app038_laravel
   docker logs app038_laravel --tail 200
   # Fix issues berdasarkan error di logs
   ```

**Lihat `DEPLOY_HOSTINGER.md` section "Issue: Container Restarting" untuk troubleshooting lengkap.**

**Issue: Supervisor Directory Not Found**

**Error:** `Error: The directory named as part of the path /var/log/supervisor/supervisord.log does not exist`

**Solusi:**

1. **Pull latest changes:**
   ```bash
   git pull origin main
   # Dockerfile sudah diupdate untuk create directory
   ```

2. **Rebuild container:**
   ```bash
   docker-compose build --no-cache laravel
   docker-compose up -d laravel
   ```

3. **Verify:**
   ```bash
   docker logs app038_laravel --tail 50
   # Seharusnya tidak ada error supervisor
   ```

**Lihat `DEPLOY_HOSTINGER.md` section "Issue: Supervisor Directory Not Found" untuk troubleshooting lengkap.**

**Issue: CollisionServiceProvider Not Found**

**Error:** `Class "NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider" not found`

**Penyebab:** Bootstrap cache files masih mengandung referensi ke dev dependencies.

**Solusi:**

1. **Clear bootstrap cache:**
   ```bash
   docker exec app038_laravel rm -f bootstrap/cache/services.php bootstrap/cache/packages.php
   docker-compose restart laravel
   ```

2. **Rebuild container:**
   ```bash
   git pull origin main
   docker-compose build --no-cache laravel
   docker-compose up -d laravel
   ```

**Lihat `DEPLOY_HOSTINGER.md` section "Issue: CollisionServiceProvider Not Found" untuk troubleshooting lengkap.**

**Issue: Database connection failed**

**Error:** `SQLSTATE[08006] [7] connection to server at "localhost" (::1), port 5432 failed: Connection refused`

**Penyebab:** Laravel mencoba connect ke `localhost` bukan ke service name `postgres`.

**Quick Fix:**

1. **Check DB_HOST di .env:**
   ```bash
   grep DB_HOST .env
   # Harus: DB_HOST=postgres (bukan localhost)
   ```

2. **Fix .env jika salah:**
   ```bash
   sed -i 's/DB_HOST=.*/DB_HOST=postgres/' .env
   docker-compose restart laravel
   ```

3. **Check PostgreSQL running:**
   ```bash
   docker ps | grep postgres
   docker-compose up -d postgres
   ```

4. **Test connection:**
   ```bash
   docker exec app038_laravel env | grep DB_HOST
   # Harus: DB_HOST=postgres
   ```

**Lihat `DEPLOY_HOSTINGER.md` section "Issue: Database Connection Failed" untuk troubleshooting lengkap.**

#### Performance Optimization untuk Dokploy

**1. Enable Build Cache:**

Di Dokploy settings, enable build cache untuk faster deployments.

**2. Resource Limits:**

Set resource limits untuk containers di docker-compose:

```yaml
services:
  laravel:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
```

**3. Database Optimization:**

- Regular VACUUM untuk PostgreSQL
- Connection pooling
- Monitor slow queries

#### Security Best Practices untuk Dokploy

1. **✅ Change default Dokploy port** (jika perlu)
2. **✅ Use strong passwords** untuk semua services
3. **✅ Enable firewall** (UFW)
4. **✅ Regular updates:**
   ```bash
   cd /path/to/dokploy
   git pull
   docker-compose pull
   docker-compose up -d
   ```
5. **✅ Backup volumes** secara berkala
6. **✅ Monitor logs** untuk suspicious activity

#### Checklist Deployment Dokploy

**Pre-Deployment:**
- [x] ✅ VPS Hostinger sudah aktif dengan Ubuntu 24.04 LTS (Terverifikasi: srv1162366.hstgr.cloud)
- [x] ✅ VPS IP address sudah dicatat: `168.231.118.3`
- [ ] SSH access ke VPS sudah berfungsi (fix host key issue jika perlu: `ssh-keygen -R 168.231.118.3`)
- [ ] Domain name sudah terdaftar (jika menggunakan custom domain)
- [ ] DNS provider access sudah tersedia
- [x] ✅ VPS Spesifikasi: 2 CPUs, 8GB RAM, 100GB Disk (Sangat cukup untuk production!)

**Dokploy Installation:**
- [ ] Docker dan Docker Compose sudah terinstall
- [ ] Port 80, 443, 3000 sudah tersedia (check dengan: `sudo ss -tulpn | grep -E ':(80|443|3000|22)'`)
- [ ] Dokploy sudah terinstall dan running
- [ ] Dokploy UI sudah bisa diakses di `http://168.231.118.3:3000`
- [ ] Admin user sudah dibuat
- [ ] Firewall (UFW) sudah dikonfigurasi (allow: 22, 80, 443, 3000)

**Project Setup:**
- [ ] Project `app038` sudah dibuat di Dokploy
- [ ] GitHub repository sudah di-connect (atau manual upload)
- [ ] Docker Compose file (`docker-compose.dokploy.yml`) sudah dikonfigurasi
- [ ] Domain names sudah diganti di Traefik labels (ganti `yourdomain.com`)
- [ ] **dokploy-network sudah terverifikasi ada** (check dengan: `docker network ls | grep dokploy-network`)

**Configuration:**
- [ ] Environment variables sudah di-set (APP_KEY, DB_PASSWORD, dll)
- [ ] APP_KEY sudah di-generate dan di-set
- [ ] Database credentials sudah dikonfigurasi
- [ ] Redis password sudah di-set
- [ ] RabbitMQ credentials sudah di-set
- [ ] Mail configuration sudah di-set (jika diperlukan)

**Domain & DNS:**
- [ ] Domains sudah ditambahkan di Dokploy (yourdomain.com, www.yourdomain.com)
- [ ] DNS A records sudah dibuat di DNS provider
- [ ] DNS sudah pointing ke VPS IP: `168.231.118.3`
- [ ] DNS propagation sudah selesai (verified dengan `dig` atau `nslookup`)

**Deployment:**
- [ ] Deployment sudah berhasil (status: Running)
- [ ] Semua containers running (laravel, svelte, postgres, redis, rabbitmq)
- [ ] No critical errors di deployment logs

**Database:**
- [ ] Database migrations sudah dijalankan (`php artisan migrate --force`)
- [ ] Database seeders sudah dijalankan (jika diperlukan)
- [ ] Database connection sudah terverifikasi

**SSL & Security:**
- [ ] SSL certificates sudah terbit (Let's Encrypt)
- [ ] SSL certificates valid (check di browser)
- [ ] HTTPS redirect sudah berfungsi

**Verification:**
- [ ] Website sudah bisa diakses via HTTPS: `https://yourdomain.com` (atau `http://168.231.118.3` jika belum setup domain)
- [ ] Health check endpoint berfungsi: `https://yourdomain.com/health` (atau `http://168.231.118.3/health` jika belum setup domain)
- [ ] Application functionality tested (login, navigation, dll)
- [ ] API endpoint accessible (jika menggunakan subdomain API)
- [ ] RabbitMQ Management UI accessible (jika setup subdomain)
- [ ] SSL certificate valid (check di browser atau dengan `openssl s_client`)
- [ ] All containers running (check di Dokploy UI: `http://168.231.118.3:3000` atau `docker ps`)

**Post-Deployment (Opsional):**
- [ ] Auto-deploy dari Git sudah setup (webhook configured)
- [ ] Monitoring dan logs sudah dikonfigurasi
- [ ] Backup strategy sudah direncanakan
- [ ] Queue workers sudah setup (jika diperlukan)
- [ ] Scheduled tasks (cron) sudah setup (jika diperlukan)

**✅ Selesai!** Website sudah online di `https://yourdomain.com`

**Quick Verification Commands:**

Setelah deployment selesai, verifikasi dari local machine:

```bash
# Test health endpoint
curl https://yourdomain.com/health
# Expected: HTTP 200 OK dengan response "healthy"

# Test website
curl -I https://yourdomain.com
# Expected: HTTP 200 OK atau 301/302 redirect

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com | grep "Verify return code"
# Expected: Verify return code: 0 (ok)

# Atau test langsung dengan IP (jika belum setup domain)
curl http://168.231.118.3/health
```

**Next Steps (Recommended):**
1. **Setup Monitoring:**
   - Enable monitoring di Dokploy untuk resource usage
   - Setup alerts untuk high CPU/Memory usage
   - Monitor application logs untuk errors
   - Akses Dokploy UI: `http://168.231.118.3:3000`

2. **Configure Backups:**
   - Setup automated backups untuk database (PostgreSQL)
   - Backup volumes secara berkala
   - Store backups di external storage (S3, Google Drive, dll)

3. **Security Hardening:**
   - Change default Dokploy port (jika perlu)
   - Setup fail2ban untuk SSH protection
   - Regular security updates: `apt update && apt upgrade`
   - Review firewall rules

4. **Performance Optimization:**
   - Enable OPcache (sudah enabled di Dockerfile)
   - Setup Redis caching strategy
   - Monitor slow queries di database
   - Optimize Nginx configuration

5. **Documentation:**
   - Document deployment process untuk team
   - Create runbook untuk common issues
   - Document credentials dengan aman (password manager)

**Support & Resources:**
- **Dokploy Documentation:** https://docs.dokploy.com
- **Hostinger Support:** https://www.hostinger.com/contact
- **Laravel Documentation:** https://laravel.com/docs
- **Project Issues:** GitHub Issues di repository

---

### 🖥️ Opsi 0: VPS Hostinger Deployment Manual ⭐⭐⭐

**Perfect untuk:** Production deployment dengan kontrol penuh, budget terbatas, single server deployment

#### Prasyarat VPS Hostinger

1. **VPS Hostinger sudah aktif** dengan:
   - OS: Ubuntu 22.04 LTS atau 20.04 LTS (recommended)
   - Minimum: 2GB RAM, 2 CPU cores, 40GB storage
   - Recommended: 4GB RAM, 4 CPU cores, 80GB storage untuk production
   - Root access atau sudo access
   - IP address publik

2. **Domain name** (opsional tapi recommended):
   - Domain sudah terdaftar
   - Akses ke DNS management
   - A record bisa di-set ke IP VPS

#### Step 1: Persiapan VPS Hostinger

**1.1. Login ke VPS via SSH**

```bash
# SSH ke VPS Hostinger
ssh root@your-vps-ip
# Atau jika menggunakan username lain
ssh username@your-vps-ip

# Update system
sudo apt update && sudo apt upgrade -y
```

**1.2. Install Dependencies**

```bash
# Install tools dasar
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

**1.3. Setup Firewall (UFW)**

```bash
# Install UFW jika belum ada
sudo apt install ufw -y

# Allow SSH (penting! jangan skip ini)
sudo ufw allow 22/tcp

# Allow HTTP dan HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw --force enable

# Check status
sudo ufw status
```

#### Step 2: Clone Repository

```bash
# Buat directory untuk aplikasi
sudo mkdir -p /var/www
cd /var/www

# Clone repository (ganti dengan URL repository Anda)
git clone https://github.com/YOUR_USERNAME/app038.git
cd app038

# Atau jika menggunakan private repo, setup SSH key terlebih dahulu
# ssh-keygen -t ed25519 -C "your-email@example.com"
# Copy public key ke GitHub/GitLab
```

#### Step 3: Setup Environment Variables

```bash
# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

**Konfigurasi minimal untuk `.env`:**

```env
APP_NAME=App038
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration (PostgreSQL di Docker)
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

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=rabbitmq

# RabbitMQ Configuration
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=your_rabbitmq_password_here

# Mail Configuration (sesuaikan dengan provider email Anda)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Generate APP_KEY:**

```bash
# Install PHP dan Composer untuk generate key (atau generate di local)
# Opsi 1: Install PHP di VPS (temporary, untuk generate key saja)
sudo apt install php-cli php-mbstring -y
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Generate APP_KEY
php artisan key:generate --show
# Copy output ke .env file

# Opsi 2: Generate di local machine, lalu copy ke VPS
# php artisan key:generate --show
```

#### Step 4: Build & Start Docker Services

```bash
# Buat Docker network (jika belum ada)
docker network create app038_network || true

# Build dan start semua services
docker-compose -f docker-compose.prod.yml up -d --build

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

**Troubleshooting jika ada error:**

```bash
# Check logs untuk service tertentu
docker-compose -f docker-compose.prod.yml logs laravel
docker-compose -f docker-compose.prod.yml logs postgres
docker-compose -f docker-compose.prod.yml logs redis

# Restart service
docker-compose -f docker-compose.prod.yml restart laravel

# Rebuild jika ada perubahan
docker-compose -f docker-compose.prod.yml up -d --build --force-recreate
```

#### Step 5: Setup Database

```bash
# Tunggu beberapa detik untuk database siap
sleep 10

# Run migrations
docker exec -it app038_laravel php artisan migrate --force

# Run seeders (opsional)
docker exec -it app038_laravel php artisan db:seed --force

# Verify database connection
docker exec -it app038_laravel php artisan tinker
# Di dalam tinker:
# DB::connection()->getPdo();
# exit
```

#### Step 6: Setup Nginx Reverse Proxy & SSL

**6.1. Install Nginx (jika belum ada)**

```bash
sudo apt install nginx -y
```

**6.2. Create Nginx Configuration**

```bash
sudo nano /etc/nginx/sites-available/app038
```

**Isi dengan konfigurasi berikut (ganti `yourdomain.com` dengan domain Anda):**

```nginx
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect semua HTTP ke HTTPS
    return 301 https://$server_name$request_uri;
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

    # Proxy to Laravel container
    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support (jika diperlukan)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Health check endpoint
    location /health {
        proxy_pass http://127.0.0.1:80/health;
        access_log off;
    }
}
```

**6.3. Enable Site**

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

**6.4. Setup SSL dengan Let's Encrypt**

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate (ganti dengan domain Anda)
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Follow prompts:
# - Enter email address
# - Agree to terms
# - Choose redirect HTTP to HTTPS (option 2)

# Test auto-renewal
sudo certbot renew --dry-run

# Certbot akan otomatis update Nginx config dengan SSL
```

**6.5. Setup Auto-renewal SSL (sudah otomatis, tapi verify)**

```bash
# Check cron job (sudah otomatis dibuat oleh Certbot)
sudo systemctl status certbot.timer

# Manual renewal test
sudo certbot renew --dry-run
```

#### Step 7: Setup Domain DNS

**Di DNS provider Anda (Hostinger, Cloudflare, dll):**

1. **A Record:**
   ```
   Type: A
   Name: @ (atau kosong)
   Value: [IP VPS Hostinger Anda]
   TTL: 3600 (atau auto)
   ```

2. **A Record untuk www:**
   ```
   Type: A
   Name: www
   Value: [IP VPS Hostinger Anda]
   TTL: 3600 (atau auto)
   ```

**Verifikasi DNS:**

```bash
# Check DNS propagation
dig yourdomain.com
nslookup yourdomain.com

# Tunggu beberapa menit untuk DNS propagate (bisa sampai 24 jam)
```

#### Step 8: Verify Deployment

```bash
# Check health endpoint
curl http://localhost/health
curl https://yourdomain.com/health

# Check dari browser
# https://yourdomain.com

# Check logs jika ada error
docker-compose -f docker-compose.prod.yml logs laravel
sudo tail -f /var/log/nginx/app038-error.log
```

#### Step 9: Setup Auto-start on Boot

```bash
# Docker sudah auto-start, tapi pastikan
sudo systemctl enable docker

# Nginx sudah auto-start, verify
sudo systemctl enable nginx
sudo systemctl status nginx

# Setup auto-start untuk Docker Compose (opsional)
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

```bash
# Enable service
sudo systemctl daemon-reload
sudo systemctl enable app038.service

# Test
sudo systemctl start app038.service
sudo systemctl status app038.service
```

#### Step 10: Setup Monitoring & Maintenance

**10.1. Setup Log Rotation**

```bash
sudo nano /etc/logrotate.d/app038
```

**Isi:**

```
/var/www/app038/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        docker exec app038_laravel php artisan log:clear || true
    endscript
}
```

**10.2. Setup Backup Script**

```bash
# Buat backup script
sudo nano /usr/local/bin/app038-backup.sh
```

**Isi:**

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/app038"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
docker exec app038_postgres pg_dump -U postgres app038 > $BACKUP_DIR/db_$DATE.sql

# Backup storage
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /var/www/app038 storage

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/app038-backup.sh

# Add to crontab (daily backup at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/app038-backup.sh
```

**10.3. Setup Monitoring (Opsional)**

```bash
# Install monitoring tools (opsional)
# Contoh: Netdata untuk real-time monitoring
bash <(curl -Ss https://my-netdata.io/kickstart.sh)
# Access: http://your-vps-ip:19999
```

#### Troubleshooting VPS Hostinger

**Issue: Cannot connect via SSH**

```bash
# Check firewall
sudo ufw status
sudo ufw allow 22/tcp

# Check SSH service
sudo systemctl status ssh
```

**Issue: Docker containers tidak start**

```bash
# Check Docker service
sudo systemctl status docker
sudo systemctl restart docker

# Check disk space
df -h

# Check Docker logs
docker-compose -f docker-compose.prod.yml logs
```

**Issue: Nginx 502 Bad Gateway**

```bash
# Check Laravel container running
docker ps | grep laravel

# Check Nginx error log
sudo tail -f /var/log/nginx/app038-error.log

# Restart Laravel container
docker-compose -f docker-compose.prod.yml restart laravel
```

**Issue: SSL certificate tidak terbit**

```bash
# Check domain pointing ke VPS
dig yourdomain.com

# Check firewall (port 80 dan 443 harus open)
sudo ufw status

# Manual certbot
sudo certbot certonly --standalone -d yourdomain.com
```

**Issue: Database connection failed**

```bash
# Check PostgreSQL container
docker ps | grep postgres
docker logs app038_postgres

# Test connection
docker exec -it app038_laravel php artisan tinker
# DB::connection()->getPdo();
```

#### Performance Optimization untuk VPS Hostinger

**1. Optimize PHP-FPM:**

Edit `docker/php/Dockerfile` atau create custom PHP config:

```ini
pm = dynamic
pm.max_children = 20  # Sesuaikan dengan RAM VPS
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
```

**2. Enable OPcache (sudah enabled di Dockerfile)**

**3. Setup Redis untuk cache:**

```bash
# Verify Redis running
docker exec -it app038_redis redis-cli ping
```

**4. Optimize Nginx:**

```nginx
# Di /etc/nginx/nginx.conf
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
gzip on;
gzip_types text/plain text/css application/json application/javascript;
```

#### Security Best Practices untuk VPS Hostinger

1. **✅ Firewall sudah setup** (UFW)
2. **✅ SSL sudah setup** (Let's Encrypt)
3. **✅ Strong passwords** untuk database dan services
4. **✅ Regular updates:**

```bash
# Setup auto-updates (opsional)
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades
```

5. **✅ Fail2Ban (opsional tapi recommended):**

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

6. **✅ Disable root login via SSH (recommended):**

```bash
# Create new user dengan sudo
sudo adduser deploy
sudo usermod -aG sudo deploy

# Setup SSH key untuk user baru
# Copy public key ke ~/.ssh/authorized_keys

# Disable root login
sudo nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
sudo systemctl restart ssh
```

#### Checklist Deployment VPS Hostinger

- [ ] VPS Hostinger sudah aktif dengan Ubuntu 22.04/20.04
- [ ] SSH access sudah berfungsi
- [ ] Docker dan Docker Compose sudah terinstall
- [ ] Firewall (UFW) sudah dikonfigurasi
- [ ] Repository sudah di-clone ke `/var/www/app038`
- [ ] `.env` file sudah dikonfigurasi dengan benar
- [ ] APP_KEY sudah di-generate
- [ ] Docker services sudah running
- [ ] Database migrations sudah dijalankan
- [ ] Nginx sudah dikonfigurasi
- [ ] SSL certificate sudah terbit (Let's Encrypt)
- [ ] DNS sudah pointing ke VPS IP
- [ ] Website sudah bisa diakses via HTTPS
- [ ] Health check endpoint berfungsi
- [ ] Auto-start on boot sudah dikonfigurasi
- [ ] Backup script sudah dibuat
- [ ] Monitoring sudah setup (opsional)

**✅ Selesai!** Website sudah online di `https://yourdomain.com`

**Next Steps:**
1. Setup monitoring alerts
2. Configure automated backups
3. Setup log rotation
4. Regular security updates
5. Monitor resource usage

---

### 🆓 Opsi 0B: Free Tier Deployment (100% GRATIS) ⭐⭐

**Perfect untuk:** MVP, testing, personal projects, budget-conscious deployments

#### Opsi 0A: Fly.io Free Tier (Recommended untuk Gratis)

**Free Tier Includes:**
- ✅ 3 shared-cpu-1x VMs gratis
- ✅ 3GB persistent volume storage gratis
- ✅ 160GB outbound data transfer gratis
- ✅ PostgreSQL database gratis (3GB storage)
- ✅ Global edge network
- ✅ Automatic SSL certificates

**Step 1: Install Fly CLI**

```bash
# macOS
curl -L https://fly.io/install.sh | sh

# Linux
curl -L https://fly.io/install.sh | sh

# Windows (PowerShell)
iwr https://fly.io/install.ps1 -useb | iex

# Verify
fly version
```

**Step 2: Login ke Fly.io**

```bash
fly auth login
# Akan membuka browser untuk login
```

**Step 3: Create Fly App**

```bash
# Initialize Fly app
fly launch --name app038 --region ams --no-deploy

# Atau gunakan region terdekat:
# ams (Amsterdam), iad (Washington), sjc (San Jose), sin (Singapore)
```

**Step 4: Create PostgreSQL Database**

```bash
# Create PostgreSQL database (gratis)
fly postgres create --name app038-db --region ams --vm-size shared-cpu-1x --volume-size 3

# Attach database ke app
fly postgres attach app038-db --app app038
```

**Step 5: Create Redis (Opsional)**

```bash
# Create Redis
fly redis create --name app038-redis --region ams

# Attach Redis
fly redis attach app038-redis --app app038
```

**Step 6: Configure fly.toml**

Edit `fly.toml` yang sudah dibuat:

```toml
app = "app038"
primary_region = "ams"

[build]
  dockerfile = "docker/php/Dockerfile"

[env]
  APP_ENV = "production"
  APP_DEBUG = "false"

[[services]]
  http_checks = []
  internal_port = 80
  processes = ["app"]
  protocol = "tcp"
  script_checks = []

  [services.concurrency]
    hard_limit = 25
    soft_limit = 20
    type = "connections"

  [[services.ports]]
    force_https = true
    handlers = ["http"]
    port = 80

  [[services.ports]]
    handlers = ["tls", "http"]
    port = 443

  [[services.tcp_checks]]
    grace_period = "1s"
    interval = "15s"
    restart_limit = 0
    timeout = "2s"

[deploy]
  release_command = "php artisan migrate --force"
```

**Step 7: Set Secrets**

```bash
# Generate APP_KEY
APP_KEY=$(php artisan key:generate --show)

# Set secrets
fly secrets set APP_KEY="$APP_KEY" --app app038
fly secrets set APP_ENV=production --app app038
fly secrets set APP_DEBUG=false --app app038

# Database credentials sudah otomatis di-set dari PostgreSQL attachment
```

**Step 8: Deploy**

```bash
# Deploy aplikasi
fly deploy --app app038

# Check status
fly status --app app038

# View logs
fly logs --app app038
```

**Step 9: Setup Custom Domain (Opsional)**

```bash
# Add custom domain
fly certs add yourdomain.com --app app038

# Verify DNS
fly certs show yourdomain.com --app app038
```

**✅ Selesai!** Website sudah online di `https://app038.fly.dev` atau custom domain Anda.

**Troubleshooting:**
```bash
# Check app status
fly status --app app038

# View logs
fly logs --app app038

# SSH ke VM
fly ssh console --app app038

# Scale up jika perlu (akan ada biaya)
fly scale count 2 --app app038
```

**Tips:**
- Free tier cukup untuk low-to-medium traffic
- Jika traffic tinggi, pertimbangkan upgrade ke paid plan
- Monitor usage di Fly.io dashboard

---

#### Opsi 0B: Railway Free Tier

**Free Tier Includes:**
- ✅ $5 credit per bulan (cukup untuk small app)
- ✅ PostgreSQL database gratis
- ✅ Automatic deployments dari GitHub
- ✅ Custom domains gratis

**Step 1: Sign Up Railway**

1. Go to https://railway.app
2. Sign up dengan GitHub account
3. Create new project

**Step 2: Add PostgreSQL Database**

1. Click "New" → "Database" → "PostgreSQL"
2. Railway akan otomatis generate `DATABASE_URL`

**Step 3: Deploy Laravel App**

1. Click "New" → "GitHub Repo"
2. Select repository `app038`
3. Railway akan auto-detect Laravel

**Step 4: Configure Environment Variables**

Di Railway dashboard, add environment variables:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY
DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}
```

**Step 5: Setup Build & Deploy**

Railway akan otomatis:
- Build Docker image dari `docker/php/Dockerfile`
- Run migrations (jika ada `railway.json`)
- Deploy aplikasi

**Step 6: Add Custom Domain**

1. Settings → Domains
2. Add custom domain
3. Update DNS records

**✅ Selesai!** Website sudah online.

**Tips:**
- $5 credit biasanya cukup untuk 1-2 small apps
- Monitor usage di Railway dashboard
- Upgrade ke paid plan jika melebihi free tier

---

#### Opsi 0C: Render Free Tier

**Free Tier Includes:**
- ✅ Web service gratis (sleeps after 15 min inactivity)
- ✅ PostgreSQL database gratis (90 days trial, lalu $7/bulan)
- ✅ Automatic SSL
- ✅ Custom domains

**Step 1: Sign Up Render**

1. Go to https://render.com
2. Sign up dengan GitHub account

**Step 2: Create PostgreSQL Database**

1. New → PostgreSQL
2. Name: `app038-db`
3. Plan: Free (trial 90 days)

**Step 3: Create Web Service**

1. New → Web Service
2. Connect GitHub repository
3. Build Command: `docker build -f docker/php/Dockerfile -t app038 .`
4. Start Command: `docker run -p 10000:80 app038`

**Step 4: Configure Environment Variables**

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY
DB_CONNECTION=pgsql
DB_HOST=<from-postgres-service>
DB_DATABASE=<from-postgres-service>
DB_USERNAME=<from-postgres-service>
DB_PASSWORD=<from-postgres-service>
```

**Step 5: Deploy**

Render akan otomatis deploy dari GitHub.

**✅ Selesai!** Website sudah online.

**Tips:**
- Free tier sleeps setelah 15 menit inactivity (untuk web service)
- PostgreSQL trial 90 hari, lalu $7/bulan
- Pertimbangkan upgrade jika perlu 24/7 uptime

---

#### Opsi 0D: Oracle Cloud Free Tier + Docker Compose

**Free Tier Includes:**
- ✅ 2 VMs gratis (selamanya) - AMD atau ARM
- ✅ 200GB block storage gratis
- ✅ 10TB outbound data transfer gratis
- ✅ PostgreSQL bisa diinstall di VM

**Step 1: Sign Up Oracle Cloud**

1. Go to https://www.oracle.com/cloud/free/
2. Sign up (perlu credit card, tapi tidak akan di-charge)
3. Create Always Free VM instance

**Step 2: Create VM Instance**

1. Compute → Instances → Create Instance
2. Select "Always Free Eligible" shape
3. OS: Ubuntu 22.04
4. Create instance

**Step 3: Setup Server**

```bash
# SSH ke server
ssh opc@<your-server-ip>

# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

**Step 4: Clone & Deploy**

```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/app038.git
cd app038

# Setup .env
cp .env.example .env
nano .env  # Edit dengan konfigurasi yang benar

# Build & Start
docker-compose -f docker-compose.prod.yml up -d --build

# Run migrations
docker exec -it app038_laravel php artisan migrate --force
```

**Step 5: Setup Nginx & SSL**

```bash
# Install Nginx
sudo apt install nginx certbot python3-certbot-nginx -y

# Setup Nginx config (lihat Opsi 1 Step 6)

# Setup SSL
sudo certbot --nginx -d yourdomain.com
```

**✅ Selesai!** Website sudah online.

**Tips:**
- VMs gratis selamanya (tidak expire)
- Perfect untuk self-hosted deployment
- Setup firewall untuk security
- Regular backups recommended

---

### 📊 Perbandingan Free Tier Options

| Platform | Free Tier | PostgreSQL | Sleep Mode | SSL | Setup Time | Best For |
|----------|-----------|------------|------------|-----|------------|----------|
| **Fly.io** ⭐ | 3 VMs, 3GB storage | ✅ Gratis (3GB) | ❌ No | ✅ Auto | 30-45 min | Production-ready apps |
| **Railway** | $5 credit/month | ✅ Gratis | ❌ No | ✅ Auto | 20-30 min | Quick deployment |
| **Render** | Web service | ⚠️ Trial 90d | ✅ Yes (15min) | ✅ Auto | 20-30 min | Low traffic apps |
| **Oracle Cloud** | 2 VMs forever | ⚠️ Self-hosted | ❌ No | ⚠️ Manual | 45-60 min | Full control |

**💡 Rekomendasi:**
- **Best Overall:** Fly.io (paling mudah, tidak ada sleep mode, PostgreSQL included)
- **Quickest Setup:** Railway (auto-deploy dari GitHub)
- **Most Control:** Oracle Cloud (full VPS control)

---

### 🎯 Opsi 1: Docker Compose Deployment (Alternatif VPS)

#### Step 1: Persiapan Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify
docker --version
docker-compose --version
```

#### Step 2: Clone Repository

```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/app038.git
cd app038

# Atau jika sudah ada, pull latest
git pull origin main
```

#### Step 3: Setup Environment Variables

```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

**Isi minimal yang diperlukan:**

```env
APP_NAME=App038
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=your_strong_password_here

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=your_rabbitmq_password
```

**Generate APP_KEY:**
```bash
php artisan key:generate --show
# Copy output ke .env file
```

#### Step 4: Build & Start Services

```bash
# Build dan start semua services
docker-compose -f docker-compose.prod.yml up -d --build

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

#### Step 5: Setup Database

```bash
# Run migrations
docker exec -it app038_laravel php artisan migrate --force

# Run seeders
docker exec -it app038_laravel php artisan db:seed --force

# Verify
docker exec -it app038_laravel php artisan tinker
# DB::connection()->getPdo();
```

#### Step 6: Setup Nginx Reverse Proxy (Opsional)

```bash
# Install Nginx
sudo apt install nginx -y

# Create Nginx config
sudo nano /etc/nginx/sites-available/app038
```

**Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Step 7: Setup SSL dengan Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (sudah otomatis)
sudo certbot renew --dry-run
```

#### Step 8: Verify Deployment

```bash
# Check health
curl http://localhost/health

# Check dari browser
# https://yourdomain.com

# Check logs jika ada error
docker-compose -f docker-compose.prod.yml logs laravel
docker-compose -f docker-compose.prod.yml logs svelte
```

**✅ Selesai!** Website sudah online di `https://yourdomain.com`

---

### 🎯 Opsi 2: Kubernetes Deployment (Recommended)

> **📊 Status Deployment Kubernetes:** Lihat [Status Deployment Kubernetes](#status-deployment-kubernetes-opsi-2) untuk melihat progress deployment dan langkah selanjutnya.

#### Status Deployment Kubernetes (Opsi 2)

**✅ Completed Steps:**

##### Phase 1: Infrastructure Setup

**✅ Step 1: Tools Installation**
- ✅ AWS CLI: `aws-cli/2.32.10` - **INSTALLED**
- ✅ Terraform: `v1.5.7` - **INSTALLED**
- ✅ Helm: `v4.0.1` - **INSTALLED**
- ✅ kubectl: `v1.34.1` - **INSTALLED**
- ✅ Docker: **INSTALLED**
- ✅ jq: **INSTALLED**

**✅ Step 2: Setup AWS Credentials**
- ✅ AWS CLI configured
- ✅ Account: `040681451912`
- ✅ User: `idmobstic`
- ✅ Region: `us-west-2`
- ✅ Output format: `json`

**✅ Step 3: Setup Terraform Backend**
- ✅ S3 Bucket: `app038-terraform-state` - **Created**
- ✅ S3 Versioning: **Enabled**
- ⚠️ S3 Encryption: Not enabled (permission needed, optional)
- ✅ DynamoDB Table: `terraform-state-lock` - **Active**

**⏳ Next Critical Steps (Urutan Penting):**

1. ⏳ **Step 4: Configure Terraform** - Uncomment backend block & create terraform.tfvars
2. ⏳ **Step 5: Provision Infrastructure** - Create EKS, RDS, VPC (15-30 min, akan ada biaya)
3. ⏳ **Step 6: Configure kubectl** - Connect ke EKS cluster
4. ⏳ **Step 7: Setup GitHub Container Registry** - Login ke GHCR
5. ⏳ **Step 8: Build & Push Docker Images** - Build Laravel & Svelte images
6. ⏳ **Step 9: Install Ingress-Nginx Controller** - **CRITICAL untuk akses online**
7. ⏳ **Step 10: Install cert-manager** - Untuk SSL/TLS
8. ⏳ **Step 11-13: Deploy aplikasi** - Create namespace, secrets, deploy dengan Helm
9. ⏳ **Step 14: Setup Database** - Run migrations
10. ⏳ **Step 15: Configure DNS** - **CRITICAL untuk akses online**
11. ⏳ **Step 16: Verify** - Test aplikasi bisa diakses

**⚠️ Important Notes:**

1. **Costs:** Infrastructure provisioning (Step 5) akan memakan biaya bulanan ~$120-250
2. **Time:** Full deployment memakan waktu 1-2 jam
3. **Critical Steps:** Step 9 (Ingress) dan Step 15 (DNS) adalah CRITICAL - tanpa ini aplikasi tidak bisa diakses online
4. **Credentials:** Simpan semua passwords dengan aman (deployment-secrets.txt)

**🆓 Opsi Deployment GRATIS (Free Tier):**

Jika Anda ingin deployment dengan biaya **GRATIS**, gunakan opsi berikut:

1. **Fly.io Free Tier** ⭐ (Recommended)
   - 3 shared-cpu-1x VMs gratis
   - PostgreSQL gratis (3GB)
   - 160GB data transfer gratis
   - Setup: 30-45 menit
   - **Guide:** Lihat [Opsi 0B: Free Tier Deployment](#-opsi-0b-free-tier-deployment-100-gratis-) → "Opsi 0A: Fly.io"

2. **Railway Free Tier**
   - $5 credit per bulan
   - PostgreSQL gratis
   - Auto-deploy dari GitHub
   - Setup: 20-30 menit

3. **Render Free Tier**
   - Web service gratis (sleeps after inactivity)
   - PostgreSQL trial 90 hari
   - Setup: 20-30 menit

4. **Oracle Cloud Free Tier**
   - 2 VMs gratis selamanya
   - 200GB storage gratis
   - Setup: 45-60 menit

**💡 Rekomendasi untuk GRATIS:** Gunakan **Fly.io Free Tier** - paling mudah dan reliable.

#### Phase 1: Infrastructure Setup

##### Step 1: Setup AWS Credentials

```bash
# Install AWS CLI
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
sudo ./aws/install

# Configure
aws configure
# AWS Access Key ID: [your-access-key]
# AWS Secret Access Key: [your-secret-key]
# Default region: us-west-2
# Default output: json

# Verify
aws sts get-caller-identity
```

##### Step 2: Setup Terraform Backend

```bash
# Create S3 bucket untuk Terraform state
aws s3 mb s3://app038-terraform-state --region us-west-2

# Enable versioning
aws s3api put-bucket-versioning \
  --bucket app038-terraform-state \
  --versioning-configuration Status=Enabled

# Enable encryption
aws s3api put-bucket-encryption \
  --bucket app038-terraform-state \
  --server-side-encryption-configuration '{
    "Rules": [{
      "ApplyServerSideEncryptionByDefault": {
        "SSEAlgorithm": "AES256"
      }
    }]
  }'

# Create DynamoDB untuk state locking
aws dynamodb create-table \
  --table-name terraform-state-lock \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region us-west-2
```

##### Step 3: Configure Terraform

**⚠️ PENTING:** Step ini WAJIB dilakukan sebelum provision infrastructure!

**Action Required:**

1. **Edit `terraform/main.tf` dan uncomment backend block:**

```hcl
terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    # ... other providers
  }

  # Uncomment block di bawah ini:
  backend "s3" {
    bucket         = "app038-terraform-state"
    key            = "app038/terraform.tfstate"
    region         = "us-west-2"
    encrypt        = true
    dynamodb_table = "terraform-state-lock"
  }
}
```

**Cara:**
- Buka file `terraform/main.tf`
- Hapus tanda `#` di depan `backend "s3"` dan semua baris di dalamnya
- Simpan file

2. **Create `terraform/terraform.tfvars`:**

**⚠️ IMPORTANT:** Ganti `yourdomain.com` dengan domain yang sebenarnya!

```bash
cd terraform

cat > terraform.tfvars <<EOF
project_name = "app038"
environment = "production"
aws_region = "us-west-2"
db_password = "$(openssl rand -base64 32)"
domain_name = "yourdomain.com"  # Ganti dengan domain Anda
EOF
```

##### Step 4: Provision Infrastructure

**⚠️ CRITICAL WARNING:** 
- Step ini akan membuat biaya AWS bulanan ~$120-250
- Infrastructure provisioning memakan waktu 15-30 menit
- Review plan dengan teliti sebelum apply!

**Action Required:**

```bash
cd terraform

# Initialize Terraform dengan backend (setelah Step 3)
terraform init

# Review plan (PENTING: Review semua resources yang akan dibuat)
terraform plan -out=tfplan

# Review output plan dengan teliti:
# - VPC dengan subnets
# - EKS Cluster
# - RDS PostgreSQL
# - NAT Gateway
# - Security Groups
# - Route Tables

# Jika sudah yakin, apply:
terraform apply tfplan

# Save outputs untuk langkah selanjutnya
terraform output -json > ../terraform-outputs.json
```

**Resources yang akan dibuat:**
- VPC dengan public/private subnets (3 availability zones)
- EKS Cluster (Kubernetes) dengan node groups
- RDS PostgreSQL Database
- NAT Gateway (untuk outbound internet dari private subnets)
- Security Groups
- Route Tables
- Internet Gateway

**⚠️ Estimated Costs:**
- EKS cluster: ~$70-150/bulan
- RDS instance (db.t3.micro): ~$15-20/bulan
- NAT Gateway: ~$32/bulan + data transfer
- EC2 nodes (t3.medium x2): ~$60/bulan
- Total estimasi: ~$120-250/bulan

**💡 Tip:** 
- Review `terraform plan` output dengan teliti
- Pastikan semua resources yang akan dibuat sesuai kebutuhan
- Simpan `terraform-outputs.json` dengan aman (berisi informasi penting)

**Output yang dihasilkan:**
- EKS Cluster Name
- RDS Endpoint
- VPC ID
- Security Group IDs

##### Step 5: Configure kubectl

```bash
# Get cluster name dari Terraform output
cd terraform
EKS_CLUSTER_NAME=$(terraform output -json | jq -r '.kubernetes_cluster_name.value // .eks_cluster_name.value // "app038-eks-cluster"')
cd ..

# Update kubeconfig
aws eks update-kubeconfig \
  --region us-west-2 \
  --name $EKS_CLUSTER_NAME

# Verify connection
kubectl cluster-info
kubectl get nodes

# Expected output: 2+ nodes should be in Ready state
```

**⚠️ Troubleshooting:**
- Jika `kubectl get nodes` tidak menampilkan nodes, tunggu 5-10 menit untuk EKS cluster selesai provisioning
- Pastikan AWS credentials memiliki permission untuk EKS

#### Phase 2: Container Registry Setup

##### Step 6: Setup GitHub Container Registry

**Prerequisites:**
- GitHub Personal Access Token dengan permission: `write:packages`, `read:packages`
- GitHub Username

**Action Required:**

```bash
# Set GitHub credentials
export GITHUB_TOKEN="your_github_personal_access_token"
export GITHUB_USERNAME="your_github_username"

# Login
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_USERNAME --password-stdin

# Verify
docker info | grep Username
```

**Membuat GitHub Personal Access Token:**
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token
3. Select scopes: `write:packages`, `read:packages`, `delete:packages`
4. Copy token (hanya muncul sekali!)

**Atau setup GitHub Secrets untuk CI/CD:**
1. GitHub Repository → Settings → Secrets and variables → Actions
2. Add secret: `GITHUB_TOKEN` dengan Personal Access Token

##### Step 7: Build & Push Docker Images

**Action Required (setelah Step 6 selesai):**

```bash
# Build Laravel image
docker buildx build \
  --platform linux/amd64 \
  -f docker/php/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/laravel:latest \
  --push .

# Build Svelte image (jika diperlukan - untuk production, Svelte di-build oleh Laravel via Vite)
docker buildx build \
  --platform linux/amd64 \
  -f docker/svelte/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/svelte:latest \
  --push .
```

**Note:** Untuk production, gunakan CI/CD pipeline yang sudah dikonfigurasi.

#### Phase 3: Kubernetes Deployment

##### Step 8: Install Ingress-Nginx Controller

**⚠️ PENTING:** Ingress controller WAJIB diinstall sebelum deploy aplikasi agar aplikasi bisa diakses dari internet.

```bash
# Install ingress-nginx menggunakan Helm (Recommended)
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update

# Install ingress-nginx controller
helm upgrade --install ingress-nginx ingress-nginx/ingress-nginx \
  --namespace ingress-nginx \
  --create-namespace \
  --set controller.service.type=LoadBalancer \
  --set controller.service.annotations."service\.beta\.kubernetes\.io/aws-load-balancer-type"=nlb \
  --set controller.replicaCount=2 \
  --set controller.nodeSelector."kubernetes\.io/os"=linux \
  --set controller.admissionWebhooks.enabled=true \
  --set controller.metrics.enabled=true \
  --wait \
  --timeout 5m

# Verify installation
kubectl get pods -n ingress-nginx
kubectl get svc -n ingress-nginx

# Get Load Balancer hostname/IP (akan digunakan untuk DNS)
kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}'
# atau
kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].ip}'

# Simpan hostname/IP untuk langkah DNS setup
INGRESS_HOSTNAME=$(kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
echo "Ingress Load Balancer: $INGRESS_HOSTNAME"
echo "Simpan hostname ini untuk konfigurasi DNS nanti!"
```

**Catatan:**
- Load Balancer provisioning memakan waktu 2-5 menit
- Hostname/IP akan muncul di `status.loadBalancer.ingress` setelah provisioning selesai
- Untuk AWS, akan dibuat Network Load Balancer (NLB) otomatis

##### Step 9: Create Namespace

```bash
kubectl create namespace app038-production

# Verify
kubectl get namespaces | grep app038
```

##### Step 10: Setup Secrets

```bash
# Generate passwords
DB_PASSWORD=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)
RABBITMQ_PASSWORD=$(openssl rand -base64 32)
APP_KEY="base64:$(openssl rand -base64 32)"

# Create secrets
kubectl create secret generic app038-secrets \
  --from-literal=DB_PASSWORD="$DB_PASSWORD" \
  --from-literal=REDIS_PASSWORD="$REDIS_PASSWORD" \
  --from-literal=RABBITMQ_PASSWORD="$RABBITMQ_PASSWORD" \
  --from-literal=APP_KEY="$APP_KEY" \
  --namespace=app038-production

# Verify
kubectl get secrets -n app038-production

# ⚠️ SIMPAN PASSWORD INI DENGAN AMAN!
echo "DB_PASSWORD: $DB_PASSWORD" > deployment-secrets.txt
echo "REDIS_PASSWORD: $REDIS_PASSWORD" >> deployment-secrets.txt
echo "RABBITMQ_PASSWORD: $RABBITMQ_PASSWORD" >> deployment-secrets.txt
echo "APP_KEY: $APP_KEY" >> deployment-secrets.txt
```

##### Step 11: Setup Image Pull Secret

```bash
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=$GITHUB_USERNAME \
  --docker-password=$GITHUB_TOKEN \
  --namespace=app038-production

# Verify
kubectl get secrets -n app038-production | grep ghcr
```

##### Step 12: Install Helm Chart

```bash
cd helm/app038

# Update dependencies
helm dependency update

# Install chart
helm upgrade --install app038 . \
  --namespace app038-production \
  --set laravel.image.repository=ghcr.io/$GITHUB_USERNAME/app038/laravel \
  --set laravel.image.tag=latest \
  --set svelte.image.repository=ghcr.io/$GITHUB_USERNAME/app038/svelte \
  --set svelte.image.tag=latest \
  --set ingress.hosts[0].host=app038.yourdomain.com \
  --set secrets.create=false \
  --set secrets.dbPassword=$DB_PASSWORD \
  --wait \
  --timeout 10m

# Verify
helm list -n app038-production
kubectl get pods -n app038-production
```

**⚠️ IMPORTANT:** Ganti `app038.yourdomain.com` dengan domain yang sebenarnya!

##### Step 13: Setup Database & Environment Variables

**⚠️ PENTING:** Pastikan database connection string sudah benar di ConfigMap/Secrets.

```bash
# Get RDS endpoint dari Terraform output
cd terraform
RDS_ENDPOINT=$(terraform output -json | jq -r '.database_endpoint.value // .rds_endpoint.value')
RDS_PORT=$(terraform output -json | jq -r '.database_port.value // "5432"')
cd ..

echo "RDS Endpoint: $RDS_ENDPOINT:$RDS_PORT"

# Update ConfigMap dengan RDS endpoint
kubectl create configmap app038-config \
  --from-literal=DB_HOST="$RDS_ENDPOINT" \
  --from-literal=DB_PORT="$RDS_PORT" \
  --from-literal=DB_DATABASE="app038_production" \
  --from-literal=DB_USERNAME="app038_user" \
  --namespace=app038-production \
  --dry-run=client -o yaml | kubectl apply -f -

# Get DB password dari secret
DB_PASSWORD=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d)

# Create database dan user (jika belum ada)
PGPASSWORD=$DB_PASSWORD psql -h $RDS_ENDPOINT -U postgres -c "CREATE DATABASE app038_production;" || echo "Database mungkin sudah ada"
PGPASSWORD=$DB_PASSWORD psql -h $RDS_ENDPOINT -U postgres -c "CREATE USER app038_user WITH PASSWORD '$DB_PASSWORD';" || echo "User mungkin sudah ada"
PGPASSWORD=$DB_PASSWORD psql -h $RDS_ENDPOINT -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE app038_production TO app038_user;"

# Wait for Laravel pod to be ready
kubectl wait --for=condition=ready pod \
  -l app.kubernetes.io/name=app038,app.kubernetes.io/component=laravel \
  -n app038-production \
  --timeout=300s

# Run migrations
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan migrate --force

# Run seeders (jika diperlukan)
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan db:seed --force

# Verify database connection
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected!';"
```

##### Step 14: Install cert-manager untuk SSL/TLS

**⚠️ PENTING:** cert-manager diperlukan untuk mendapatkan SSL certificate dari Let's Encrypt secara otomatis.

```bash
# Install cert-manager CRDs dan controller
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Wait for cert-manager pods to be ready (2-3 menit)
echo "Waiting for cert-manager to be ready..."
kubectl wait --for=condition=ready pod \
  -l app.kubernetes.io/instance=cert-manager \
  -n cert-manager \
  --timeout=300s

# Verify cert-manager installation
kubectl get pods -n cert-manager
kubectl get crd | grep cert-manager

# Create ClusterIssuer untuk Let's Encrypt Production
# GANTI EMAIL dengan email Anda yang valid!
read -p "Masukkan email untuk Let's Encrypt certificate: " LETSENCRYPT_EMAIL

kubectl apply -f - <<EOF
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: ${LETSENCRYPT_EMAIL}
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF

# Verify ClusterIssuer
kubectl get clusterissuer letsencrypt-prod

# Update Helm values untuk SSL (jika belum di-set)
cd helm/app038
helm upgrade app038 . \
  --namespace app038-production \
  --reuse-values \
  --set ingress.annotations."cert-manager\.io/cluster-issuer"=letsencrypt-prod \
  --set ingress.tls[0].secretName=app038-tls \
  --wait

cd ../..

# Verify certificate creation (akan memakan waktu 1-2 menit)
echo "Waiting for certificate to be issued..."
kubectl get certificate -n app038-production -w

# Check certificate status
kubectl describe certificate app038-tls -n app038-production
```

##### Step 15: Setup DNS

**⚠️ PENTING:** DNS harus dikonfigurasi agar aplikasi bisa diakses dari internet.

```bash
# Get Ingress Load Balancer hostname/IP
INGRESS_HOSTNAME=$(kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
INGRESS_IP=$(kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].ip}')

echo "Ingress Load Balancer Hostname: $INGRESS_HOSTNAME"
echo "Ingress Load Balancer IP: $INGRESS_IP"

# Jika menggunakan Route53 (AWS)
read -p "Masukkan domain name (e.g., app038.yourdomain.com): " DOMAIN_NAME
read -p "Apakah domain menggunakan Route53? (y/n): " USE_ROUTE53

if [ "$USE_ROUTE53" = "y" ]; then
    # Get hosted zone ID
    read -p "Masukkan hosted zone ID: " HOSTED_ZONE_ID
    
    # Create A record (jika menggunakan IP)
    if [ -n "$INGRESS_IP" ]; then
        aws route53 change-resource-record-sets \
          --hosted-zone-id $HOSTED_ZONE_ID \
          --change-batch '{
            "Changes": [{
              "Action": "UPSERT",
              "ResourceRecordSet": {
                "Name": "'$DOMAIN_NAME'",
                "Type": "A",
                "TTL": 300,
                "ResourceRecords": [{"Value": "'$INGRESS_IP'"}]
              }
            }]
          }'
        echo "✅ A record created: $DOMAIN_NAME -> $INGRESS_IP"
    fi
    
    # Atau create CNAME record (jika menggunakan hostname)
    if [ -n "$INGRESS_HOSTNAME" ] && [ -z "$INGRESS_IP" ]; then
        aws route53 change-resource-record-sets \
          --hosted-zone-id $HOSTED_ZONE_ID \
          --change-batch '{
            "Changes": [{
              "Action": "UPSERT",
              "ResourceRecordSet": {
                "Name": "'$DOMAIN_NAME'",
                "Type": "CNAME",
                "TTL": 300,
                "ResourceRecords": [{"Value": "'$INGRESS_HOSTNAME'"}]
              }
            }]
          }'
        echo "✅ CNAME record created: $DOMAIN_NAME -> $INGRESS_HOSTNAME"
    fi
else
    echo ""
    echo "⚠️  Manual DNS Configuration Required:"
    echo "Update DNS records di provider DNS Anda:"
    if [ -n "$INGRESS_IP" ]; then
        echo "  A record: $DOMAIN_NAME -> $INGRESS_IP"
    fi
    if [ -n "$INGRESS_HOSTNAME" ]; then
        echo "  CNAME record: $DOMAIN_NAME -> $INGRESS_HOSTNAME"
    fi
    echo ""
    echo "Tunggu DNS propagation (5-30 menit) sebelum melanjutkan..."
fi

# Update Ingress dengan domain yang benar
cd helm/app038
helm upgrade app038 . \
  --namespace app038-production \
  --reuse-values \
  --set ingress.hosts[0].host=$DOMAIN_NAME \
  --set ingress.tls[0].hosts[0]=$DOMAIN_NAME \
  --wait

cd ../..

# Verify DNS propagation
echo "Testing DNS resolution..."
nslookup $DOMAIN_NAME || dig $DOMAIN_NAME
```

##### Step 16: Verify Deployment

**Comprehensive Verification:**

```bash
# 1. Check semua pods status (harus Running)
echo "=== Pods Status ==="
kubectl get pods -n app038-production
kubectl get pods -n ingress-nginx
kubectl get pods -n cert-manager

# 2. Check services
echo "=== Services ==="
kubectl get services -n app038-production
kubectl get services -n ingress-nginx

# 3. Check ingress
echo "=== Ingress ==="
kubectl get ingress -n app038-production -o wide
kubectl describe ingress -n app038-production

# 4. Check certificates
echo "=== Certificates ==="
kubectl get certificate -n app038-production
kubectl describe certificate app038-tls -n app038-production

# 5. Check certificate orders/challenges
kubectl get order -n app038-production
kubectl get challenge -n app038-production

# 6. Test application endpoints
DOMAIN_NAME=$(kubectl get ingress -n app038-production -o jsonpath='{.items[0].spec.rules[0].host}')
echo "=== Testing Application ==="
echo "Testing: http://$DOMAIN_NAME/health"
curl -v http://$DOMAIN_NAME/health || echo "HTTP test failed, trying HTTPS..."
echo ""
echo "Testing: https://$DOMAIN_NAME/health"
curl -v https://$DOMAIN_NAME/health || echo "HTTPS test failed (certificate mungkin masih dalam proses)"

# 7. Check application logs
echo "=== Laravel Logs (last 20 lines) ==="
kubectl logs deployment/app038-laravel -n app038-production --tail=20

# 8. Test database connection dari pod
echo "=== Database Connection Test ==="
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker --execute="try { DB::connection()->getPdo(); echo '✅ Database connected!'; } catch(Exception \$e) { echo '❌ Database error: ' . \$e->getMessage(); }"

# 9. Test Redis connection
echo "=== Redis Connection Test ==="
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker --execute="try { Cache::put('test', 'value', 10); echo '✅ Redis connected!'; } catch(Exception \$e) { echo '❌ Redis error: ' . \$e->getMessage(); }"

# 10. Check ingress controller logs (jika ada masalah)
echo "=== Ingress Controller Logs (last 10 lines) ==="
kubectl logs -n ingress-nginx deployment/ingress-nginx-controller --tail=10
```

**Expected Results:**

✅ **Semua pods harus Running:**
- `app038-laravel-*` pods: Running (3 replicas)
- `app038-svelte-*` pods: Running (2 replicas) - jika diperlukan
- `redis-*` pod: Running
- `rabbitmq-*` pod: Running
- `ingress-nginx-controller-*` pods: Running (2 replicas)
- `cert-manager-*` pods: Running

✅ **Ingress harus memiliki:**
- Address: Load Balancer hostname/IP
- TLS: Certificate status Ready

✅ **Application harus accessible:**
- HTTP: `http://$DOMAIN_NAME/health` → 200 OK
- HTTPS: `https://$DOMAIN_NAME/health` → 200 OK (setelah certificate ready)

#### Phase 4: CI/CD Setup

##### Step 17: Configure GitHub Actions

1. **Setup GitHub Secrets:**

GitHub Repository → Settings → Secrets and variables → Actions

Tambahkan secrets berikut:

```
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_REGION=us-west-2
KUBECONFIG (base64 encoded)
GITHUB_TOKEN
DB_PASSWORD
APP_KEY
REDIS_PASSWORD
RABBITMQ_PASSWORD
```

2. **Verify Workflow:**

```bash
# Check workflow file
cat .github/workflows/ci-cd.yml

# Workflow akan otomatis:
# - Run tests
# - Build Docker images
# - Push to GitHub Container Registry
# - Deploy to Kubernetes (hanya pada push ke main)
```

3. **Test CI/CD:**

```bash
# Create test branch
git checkout -b test-deployment

# Make small change
echo "# Test" >> README.md

# Commit and push
git add README.md
git commit -m "Test CI/CD pipeline"
git push origin test-deployment

# Create Pull Request
# CI/CD akan run tests dan validate
```

---

### 🎯 Opsi 3: Full Infrastructure dengan Terraform (Enterprise)

Ikuti semua langkah dari **Opsi 2**, plus:

#### Additional Steps:

##### Step 16: Setup HashiCorp Vault

```bash
# Vault sudah di-provision oleh Terraform
# Get Vault endpoint
VAULT_ENDPOINT=$(terraform output -json | jq -r '.vault_endpoint.value')

# Initialize Vault (first time only)
kubectl exec -it vault-0 -n vault -- vault operator init

# Save unseal keys dan root token (SECURE!)

# Unseal Vault
kubectl exec -it vault-0 -n vault -- vault operator unseal <unseal-key-1>
kubectl exec -it vault-0 -n vault -- vault operator unseal <unseal-key-2>
kubectl exec -it vault-0 -n vault -- vault operator unseal <unseal-key-3>

# Configure Kubernetes auth
kubectl exec -it vault-0 -n vault -- vault auth enable kubernetes

# Store secrets
kubectl exec -it vault-0 -n vault -- vault kv put secret/app038/database \
  password="your-db-password"
```

##### Step 17: Setup Monitoring

```bash
# Install Prometheus & Grafana
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update

helm install monitoring prometheus-community/kube-prometheus-stack \
  --namespace monitoring \
  --create-namespace \
  --set prometheus.prometheusSpec.retention=30d \
  --set grafana.adminPassword=admin \
  --wait

# Get Grafana password
kubectl get secret --namespace monitoring monitoring-grafana \
  -o jsonpath="{.data.admin-password}" | base64 -d

# Port forward untuk akses
kubectl port-forward svc/monitoring-grafana 3000:80 -n monitoring
# Access: http://localhost:3000 (admin / [password])
```

---

## Post-Deployment

### 1. Health Checks

```bash
# Application health
curl https://app038.yourdomain.com/health

# Laravel health
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan health:check

# Check all pods
kubectl get pods -n app038-production -o wide
```

### 2. Functional Tests

```bash
# Test login
curl -X POST https://app038.yourdomain.com/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Test API
curl https://app038.yourdomain.com/api/health
```

### 3. Database Verification

```bash
# Test connection
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker

# In tinker:
DB::connection()->getPdo();
Cache::put('test', 'value', 60);
Cache::get('test');
```

### 4. Queue Verification

```bash
# Check queue workers
kubectl get pods -l app=queue-worker -n app038-production

# Test queue
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan queue:work --once
```

---

## Monitoring & Maintenance

### Daily Tasks

```bash
# Check application health
curl https://app038.yourdomain.com/health

# Check pod status
kubectl get pods -n app038-production

# Check resource usage
kubectl top pods -n app038-production
```

### Weekly Tasks

```bash
# Review logs for errors
kubectl logs -l app=app038 -n app038-production --since=7d | grep -i error

# Check database size
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker
# DB::select("SELECT pg_size_pretty(pg_database_size('app038_production'));");

# Review monitoring dashboards
```

### Monthly Tasks

```bash
# Update dependencies
composer update
npm update

# Security patches
kubectl get pods -n app038-production -o json | \
  jq '.items[].spec.containers[].image'

# Review and optimize costs
aws ce get-cost-and-usage \
  --time-period Start=2024-01-01,End=2024-01-31 \
  --granularity MONTHLY \
  --metrics BlendedCost
```

---

## Troubleshooting

### Issue: Pods Tidak Bisa Start

```bash
# Check pod events
kubectl describe pod <pod-name> -n app038-production

# Check logs
kubectl logs <pod-name> -n app038-production --previous

# Common issues:
# - Image pull errors: Check image pull secrets
# - Resource limits: Check resource requests/limits
# - Health check failures: Check health endpoint
```

### Issue: Database Connection Failed

```bash
# Check database service
kubectl get svc -n app038-production | grep postgres

# Test connection from pod
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan tinker
# DB::connection()->getPdo();

# Check security groups (AWS)
aws ec2 describe-security-groups --group-ids <sg-id>
```

### Issue: SSL Certificate Tidak Terbit

```bash
# Check certificate status
kubectl describe certificate app038-tls -n app038-production

# Check certificate request
kubectl get certificaterequest -n app038-production

# Check ingress
kubectl describe ingress app038 -n app038-production

# Common issues:
# - DNS not pointing to load balancer
# - Ingress class not configured
# - ClusterIssuer not found
```

### Issue: High Memory/CPU Usage

```bash
# Check resource usage
kubectl top pods -n app038-production

# Check HPA (Horizontal Pod Autoscaler)
kubectl get hpa -n app038-production

# Scale manually if needed
kubectl scale deployment app038-laravel --replicas=5 -n app038-production
```

### Issue: Queue Workers Tidak Berjalan

```bash
# Check queue worker pods
kubectl get pods -l app=queue-worker -n app038-production

# Check queue worker logs
kubectl logs -l app=queue-worker -n app038-production

# Restart queue workers
kubectl rollout restart deployment/queue-worker -n app038-production
```

---

## Rollback Procedures

### Rollback Helm Release

```bash
# List Helm releases
helm list -n app038-production

# View release history
helm history app038 -n app038-production

# Rollback to previous revision
helm rollback app038 <revision-number> -n app038-production --wait

# Verify rollback
kubectl get pods -n app038-production
```

### Emergency Rollback

```bash
# Scale down current deployment
kubectl scale deployment app038-laravel --replicas=0 -n app038-production
kubectl scale deployment app038-svelte --replicas=0 -n app038-production

# Deploy previous version
helm upgrade app038 ./helm/app038 \
  --namespace app038-production \
  --set laravel.image.tag=<previous-tag> \
  --set svelte.image.tag=<previous-tag> \
  --reuse-values \
  --wait
```

### Database Rollback

```bash
# Rollback migrations
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan migrate:rollback --step=1

# Atau rollback ke specific batch
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan migrate:rollback --batch=<batch-number>
```

---

## Best Practices

### 1. Security ✅

- ✅ Gunakan secrets management (Vault/Kubernetes Secrets)
- ✅ Enable network policies
- ✅ Use least privilege IAM roles
- ✅ Enable encryption at rest dan in transit
- ✅ Regular security scans (Trivy, Snyk)
- ✅ Rotate credentials secara berkala

### 2. High Availability ✅

- ✅ Multi-AZ deployment
- ✅ Auto-scaling (HPA)
- ✅ Health checks & readiness probes
- ✅ Circuit breakers
- ✅ Fallback services

### 3. Monitoring ✅

- ✅ Application metrics (Prometheus)
- ✅ Log aggregation (ELK/CloudWatch)
- ✅ Alerting (PagerDuty/Slack)
- ✅ Distributed tracing (Jaeger)
- ✅ Uptime monitoring

### 4. Backup & Recovery ✅

- ✅ Database backups (automated)
- ✅ Volume snapshots
- ✅ Disaster recovery plan
- ✅ Regular restore tests

### 5. CI/CD ✅

- ✅ Automated testing
- ✅ Code quality checks
- ✅ Security scanning
- ✅ Blue-green deployments
- ✅ Canary releases

---

## Checklist Deployment

### Pre-Deployment
- [ ] Tools sudah terinstall (Docker, kubectl, Helm, Terraform)
- [ ] AWS account sudah setup
- [ ] GitHub repository sudah ready
- [ ] Domain name sudah terdaftar
- [ ] DNS access sudah tersedia

### Infrastructure
- [ ] Terraform backend (S3) sudah dibuat
- [ ] Infrastructure sudah di-provision (VPC, EKS, RDS)
- [ ] kubectl sudah terhubung ke cluster
- [ ] Security groups sudah dikonfigurasi

### Container Registry
- [ ] GitHub Container Registry sudah diakses
- [ ] Docker images sudah di-build dan di-push
- [ ] Image pull secrets sudah dibuat

### Kubernetes
- [ ] Namespace sudah dibuat
- [ ] Secrets sudah dibuat
- [ ] Helm chart sudah di-install
- [ ] Pods sudah running

### Database
- [ ] Database sudah dibuat
- [ ] Migrations sudah dijalankan
- [ ] Seeders sudah dijalankan
- [ ] Database connection sudah terverifikasi

### SSL/TLS
- [ ] cert-manager sudah di-install
- [ ] ClusterIssuer sudah dibuat
- [ ] SSL certificate sudah terbit
- [ ] HTTPS sudah berfungsi

### DNS
- [ ] DNS records sudah dikonfigurasi
- [ ] Domain sudah pointing ke Load Balancer
- [ ] Website sudah bisa diakses via domain

### CI/CD
- [ ] GitHub Secrets sudah dikonfigurasi
- [ ] CI/CD workflow sudah berjalan
- [ ] Automated deployment sudah berfungsi

### Post-Deployment
- [ ] Health checks passing
- [ ] Functional tests passing
- [ ] Monitoring sudah setup
- [ ] Logging sudah berfungsi
- [ ] Backup sudah dikonfigurasi

### Documentation
- [ ] Deployment guide sudah diupdate
- [ ] Runbook sudah dibuat
- [ ] Team sudah di-notify
- [ ] Access credentials sudah didistribusikan

---

## Support & Resources

- **Documentation**: Lihat file-file di folder root project
- **Monitoring**: Grafana dashboard
- **Logs**: CloudWatch Logs atau ELK Stack
- **Issues**: GitHub Issues di repository
- **Emergency**: Contact DevOps team

---

## Quick Reference Commands

### VPS Hostinger / Docker Compose
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

# Restart service
docker-compose -f docker-compose.prod.yml restart laravel

# Rebuild and restart
docker-compose -f docker-compose.prod.yml up -d --build --force-recreate

# Check status
docker-compose -f docker-compose.prod.yml ps

# Execute command in container
docker exec -it app038_laravel php artisan migrate
docker exec -it app038_laravel php artisan tinker
docker exec -it app038_laravel bash

# Update code from Git
cd /var/www/app038
git pull origin main
docker-compose -f docker-compose.prod.yml up -d --build

# Backup database
docker exec app038_postgres pg_dump -U postgres app038 > backup_$(date +%Y%m%d).sql

# Restore database
docker exec -i app038_postgres psql -U postgres app038 < backup_20240101.sql
```

### Kubernetes
```bash
# Get pods
kubectl get pods -n app038-production

# Get logs
kubectl logs -f deployment/app038-laravel -n app038-production

# Execute command
kubectl exec -it deployment/app038-laravel -n app038-production -- bash

# Scale
kubectl scale deployment app038-laravel --replicas=3 -n app038-production
```

### Helm
```bash
# List releases
helm list -n app038-production

# Upgrade
helm upgrade app038 ./helm/app038 -n app038-production

# Rollback
helm rollback app038 <revision> -n app038-production
```

### Terraform
```bash
# Plan
terraform plan

# Apply
terraform apply

# Destroy (HATI-HATI!)
terraform destroy
```

---

**🎉 Selamat! Website Anda sekarang sudah online dan dapat diakses di internet! 🚀**

**URL:** 
- **VPS Hostinger dengan Dokploy:** `https://yourdomain.com` (atau `http://168.231.118.3` jika belum setup domain)
- **Dokploy UI:** `http://168.231.118.3:3000`
- Kubernetes: `https://app038.yourdomain.com`
- Free Tier: `https://app038.fly.dev` atau sesuai platform

**Quick Access untuk VPS Anda:**
- **SSH:** `ssh root@168.231.118.3`
- **Dokploy Dashboard:** `http://168.231.118.3:3000`
- **Website (jika sudah deploy):** `http://168.231.118.3` atau `https://yourdomain.com`

**Next Steps:**
1. Setup monitoring alerts
2. Configure automated backups
3. Setup disaster recovery plan
4. Document runbooks
5. Train team members

---

## 📝 Catatan Penting untuk VPS Hostinger dengan Dokploy

### ✅ Informasi VPS Hostinger Anda

**VPS Details (Terverifikasi via Hostinger API):**
- **IP Address:** `168.231.118.3`
- **Hostname:** `srv1162366.hstgr.cloud`
- **Plan:** KVM 2
- **CPUs:** 2 cores
- **RAM:** 8GB (8192 MB) - **Sangat cukup untuk production!**
- **Disk:** 100GB (102400 MB) - **Cukup untuk aplikasi + data**
- **OS:** Ubuntu 24.04 LTS
- **Status:** Running (Active)
- **State:** Unlocked (siap untuk deployment)

**Quick Commands:**
```bash
# SSH ke VPS
ssh root@168.231.118.3

# Fix SSH key issue (jika perlu)
ssh-keygen -R 168.231.118.3

# Akses Dokploy UI
# http://168.231.118.3:3000
```

### Langkah-Langkah Selanjutnya Setelah Deployment Berhasil

Setelah aplikasi sudah online di `https://yourdomain.com`, berikut langkah-langkah penting yang harus dilakukan:

#### 1. Setup Automated Backups

**Database Backup (PostgreSQL):**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Buat backup script
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

# Add to crontab (daily backup at 2 AM)
sudo crontab -e
# Add line:
0 2 * * * /usr/local/bin/app038-backup-db.sh >> /var/log/app038-backup.log 2>&1
```

**Volume Backup (Storage Files):**

```bash
# Backup script untuk volumes
sudo nano /usr/local/bin/app038-backup-volumes.sh
```

**Isi script:**
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/app038/volumes"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup storage directory
# Path default Dokploy: /root/dokploy/files/app038 atau /opt/dokploy/files/app038
# Adjust path sesuai lokasi Dokploy Anda
DOKPLOY_PATH="/root/dokploy"  # Atau /opt/dokploy
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C $DOKPLOY_PATH/files/app038 storage

# Keep only last 7 days
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +7 -delete

echo "Volumes backup completed: $DATE"
```

**Catatan:** Untuk mengetahui path Dokploy files, check di Dokploy UI atau:
```bash
# SSH ke VPS
ssh root@168.231.118.3

# Find Dokploy files directory
find / -name "dokploy" -type d 2>/dev/null | grep files
# Atau check di Dokploy UI → Settings → File System
```

#### 2. Setup Monitoring & Alerts

**Resource Monitoring:**

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs -y

# Monitor real-time
htop          # CPU & Memory
iotop         # Disk I/O
nethogs       # Network usage
```

**Setup Dokploy Monitoring:**

1. Di Dokploy UI (`http://168.231.118.3:3000`), enable monitoring untuk:
   - CPU usage alerts (threshold: 80%)
   - Memory usage alerts (threshold: 85%)
   - Disk usage alerts (threshold: 90%)

2. Setup email notifications untuk alerts

3. **Monitor via Command Line:**
   ```bash
   # SSH ke VPS
   ssh root@168.231.118.3
   
   # Check resource usage
   free -h      # RAM usage
   df -h        # Disk usage
   htop         # CPU & Memory (install: apt install htop)
   
   # Check Docker resource usage
   docker stats --no-stream
   ```

#### 3. Security Hardening

**Setup Fail2Ban (Protection dari brute force):**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Install fail2ban
sudo apt install fail2ban -y

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local

# Enable SSH protection
# Set: enabled = true untuk [sshd]

# Start fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Check status
sudo fail2ban-client status

# Check banned IPs
sudo fail2ban-client status sshd
```

**Disable Root Login via SSH (Recommended):**

```bash
# Create new user dengan sudo
sudo adduser deploy
sudo usermod -aG sudo deploy

# Setup SSH key untuk user baru
# Copy public key ke ~/.ssh/authorized_keys

# Disable root login
sudo nano /etc/ssh/sshd_config
# Set: PermitRootLogin no

# Restart SSH
sudo systemctl restart sshd
```

**Regular Security Updates:**

```bash
# Setup automatic security updates
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades

# Manual update
sudo apt update && sudo apt upgrade -y
```

#### 4. Performance Optimization

**Database Optimization:**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Connect ke PostgreSQL
docker exec -it app038_postgres psql -U postgres -d app038

# Run VACUUM (cleanup) - lakukan secara berkala
VACUUM ANALYZE;

# Check database size
SELECT pg_size_pretty(pg_database_size('app038'));

# Check table sizes
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

# Check slow queries (enable jika perlu)
# SET log_min_duration_statement = 1000;  # Log queries > 1 second

# Exit PostgreSQL
\q
```

**PHP-FPM Optimization:**

PHP-FPM sudah dioptimasi di Dockerfile, tapi bisa adjust sesuai kebutuhan:

```bash
# Check current PHP-FPM config
docker exec app038_laravel cat /usr/local/etc/php-fpm.d/www.conf | grep pm

# Adjust jika perlu (edit Dockerfile dan rebuild)
```

**Redis Optimization:**

```bash
# Check Redis memory usage
docker exec app038_redis redis-cli INFO memory

# Set max memory jika perlu
docker exec app038_redis redis-cli CONFIG SET maxmemory 256mb
docker exec app038_redis redis-cli CONFIG SET maxmemory-policy allkeys-lru
```

#### 5. Setup Queue Workers (Jika Diperlukan)

Jika aplikasi menggunakan Laravel queues, tambahkan queue worker service:

**Update docker-compose.dokploy.yml:**

Tambahkan service berikut:

```yaml
queue-worker:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  restart: always
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
    - ../files/app038/storage:/app/storage
    - ../files/app038/bootstrap/cache:/app/bootstrap/cache
  networks:
    - dokploy-network
  depends_on:
    - postgres
    - redis
    - rabbitmq
```

**Deploy ulang di Dokploy** setelah menambahkan service baru.

#### 6. Setup Scheduled Tasks (Laravel Scheduler)

Tambahkan scheduler service:

```yaml
scheduler:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  restart: always
  command: php artisan schedule:work
  environment:
    # Same as laravel service
  volumes:
    - ../files/app038/storage:/app/storage
  networks:
    - dokploy-network
  depends_on:
    - postgres
    - redis
```

#### 7. Regular Maintenance Tasks

**Weekly Tasks:**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Check disk usage
df -h
# Monitor: pastikan tidak melebihi 80% usage

# Check memory usage
free -h
# Monitor: pastikan available memory masih cukup

# Review application logs
# Via Dokploy UI: http://168.231.118.3:3000 → Logs tab
# Atau via command:
docker logs app038_laravel --tail 100
docker logs app038_svelte --tail 100

# Check for security updates
sudo apt list --upgradable

# Check Docker disk usage
docker system df

# Clean unused Docker resources (jika perlu)
docker system prune -a --volumes  # HATI-HATI: ini akan hapus unused images/volumes
```

**Monthly Tasks:**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Update Dokploy
# Find Dokploy directory (biasanya /root/dokploy atau /opt/dokploy)
cd /root/dokploy  # Atau /opt/dokploy
git pull
docker-compose pull
docker-compose up -d

# Update application dependencies (jika perlu)
# Via Dokploy UI: http://168.231.118.3:3000 → Redeploy dengan updated code
# Atau manual:
cd /path/to/app038
git pull origin main
# Redeploy via Dokploy UI

# Review and optimize database
docker exec app038_postgres psql -U postgres -d app038 -c "VACUUM ANALYZE;"

# Review backup files
ls -lh /var/backups/app038/

# Check backup integrity
# Test restore dari backup terbaru (di test environment)

# Review security logs
sudo journalctl -u ssh -n 100
sudo fail2ban-client status
```

### Tips Optimasi VPS Hostinger

1. **Resource Monitoring:**
   ```bash
   # Install htop untuk monitoring
   sudo apt install htop -y
   htop
   
   # Check disk usage
   df -h
   
   # Check memory usage
   free -h
   ```

2. **Database Optimization:**
   - Regular VACUUM untuk PostgreSQL
   - Setup connection pooling jika traffic tinggi
   - Monitor slow queries

3. **Cache Strategy:**
   - Gunakan Redis untuk cache dan session
   - Enable OPcache untuk PHP (sudah enabled)
   - Setup Nginx cache untuk static assets

4. **Backup Strategy:**
   - Daily database backups
   - Weekly full backups (code + database)
   - Store backups di external storage (S3, Google Drive, dll)

### Upgrade VPS Hostinger

**Status VPS Anda Saat Ini:**
- **Current Plan:** KVM 2
- **CPUs:** 2 cores
- **RAM:** 8GB (8192 MB) - **Sudah sangat baik untuk production!**
- **Disk:** 100GB (102400 MB) - **Cukup untuk aplikasi + data**

**Kapan Perlu Upgrade:**
- Jika traffic sangat tinggi dan CPU usage konsisten > 80%
- Jika RAM usage konsisten > 85%
- Jika disk usage > 80% dan terus meningkat
- Jika response time lambat meskipun sudah dioptimasi

**Upgrade Options:**
- **KVM 2 → KVM 4**: 4 CPUs, 16GB RAM (untuk very high traffic)
- **Add SSD storage**: Untuk better I/O performance
- **Add bandwidth**: Jika traffic data transfer tinggi

**Cara Upgrade:**
1. Login ke Hostinger hPanel: https://hpanel.hostinger.com
2. Navigate ke **VPS** section
3. Pilih VPS: `srv1162366.hstgr.cloud` (IP: 168.231.118.3)
4. Klik **"Upgrade"** dan pilih plan baru
5. Tunggu proses upgrade selesai (biasanya beberapa menit)
6. Restart services jika perlu:
   ```bash
   ssh root@168.231.118.3
   docker-compose -f /path/to/dokploy/docker-compose.yml restart
   ```

### Troubleshooting Common Issues

**Issue: High Memory Usage**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Check memory usage per container
docker stats --no-stream

# Check overall memory
free -h

# Identify container dengan high memory
# Restart container jika perlu
docker restart app038_laravel

# Atau via Dokploy UI: Applications → app038-production → Restart service

# Check for memory leaks
docker logs app038_laravel | grep -i "memory\|fatal"

# Check PHP-FPM processes
docker exec app038_laravel ps aux | grep php-fpm

# Adjust PHP-FPM config jika perlu (edit Dockerfile dan rebuild)
```

**Issue: Disk Space Full**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Check disk usage
df -h
# Check: pastikan tidak melebihi 80% usage

# Check what's using space
du -sh /* 2>/dev/null | sort -h | tail -10

# Clean Docker unused resources (HATI-HATI!)
docker system prune -a --volumes
# Atau lebih aman, hanya unused:
docker system prune

# Clean old logs
sudo journalctl --vacuum-time=7d

# Remove old backups (keep last 30 days)
find /var/backups/app038 -type f -mtime +30 -delete

# Clean old Docker images
docker image prune -a

# Check Dokploy files size
du -sh /root/dokploy/files/app038/*  # Atau /opt/dokploy/files/app038/*
```

**Issue: Slow Response Time**

```bash
# SSH ke VPS
ssh root@168.231.118.3

# Check database connections
docker exec app038_postgres psql -U postgres -d app038 -c "SELECT count(*) FROM pg_stat_activity;"

# Check active database queries
docker exec app038_postgres psql -U postgres -d app038 -c "SELECT pid, state, query FROM pg_stat_activity WHERE state != 'idle';"

# Check Redis performance
docker exec app038_redis redis-cli --latency

# Check Redis memory usage
docker exec app038_redis redis-cli INFO memory

# Check application logs untuk slow queries
docker logs app038_laravel | grep -i "slow\|timeout"

# Check PHP-FPM status
docker exec app038_laravel curl http://localhost/status

# Check Nginx access logs
docker logs app038_laravel | grep nginx

# Check system load
uptime
top
```

### Support & Resources

- **Dokploy Documentation**: https://docs.dokploy.com
- **Dokploy GitHub**: https://github.com/dokploy/dokploy
- **Hostinger Support**: https://www.hostinger.com/contact
- **Hostinger hPanel**: https://hpanel.hostinger.com
- **Hostinger Knowledge Base**: https://support.hostinger.com
- **VPS Management**: Login ke hPanel → VPS → srv1162366.hstgr.cloud
- **Docker Documentation**: https://docs.docker.com
- **Laravel Documentation**: https://laravel.com/docs
- **Nginx Documentation**: https://nginx.org/en/docs/
- **PostgreSQL Documentation**: https://www.postgresql.org/docs/
- **Redis Documentation**: https://redis.io/documentation
- **RabbitMQ Documentation**: https://www.rabbitmq.com/documentation.html
