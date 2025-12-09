# 🚀 Deployment Guide - App038

Panduan lengkap dan mudah diikuti untuk mendeploy aplikasi App038 ke internet menggunakan standar DevOps best practices.

## 📋 Daftar Isi

1. [Overview Project](#overview-project)
2. [Prasyarat & Tools](#prasyarat--tools)
3. [Strategi Deployment](#strategi-deployment)
4. [Langkah-langkah Deployment (Step-by-Step)](#langkah-langkah-deployment-step-by-step)
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

Kami menyediakan **4 opsi deployment** dari yang paling sederhana hingga enterprise:

### 🆓 Opsi 0: Free Tier Deployment (GRATIS) ⭐⭐⭐
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

**Rekomendasi untuk GRATIS:** Mulai dengan **Opsi 0 (Free Tier)** - lihat section detail di bawah.

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
- **Untuk GRATIS:** Gunakan **Opsi 0 (Free Tier Deployment)** - Lihat detail di bawah ⬇️
- **Untuk Production dengan Budget:** Mulai dengan **Opsi 2 (Kubernetes)** untuk production.

---

## Langkah-langkah Deployment (Step-by-Step)

### 🆓 Opsi 0: Free Tier Deployment (100% GRATIS) ⭐⭐⭐

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

### 🎯 Opsi 1: Docker Compose Deployment (Paling Mudah)

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

Edit `terraform/main.tf`:

```hcl
terraform {
  backend "s3" {
    bucket         = "app038-terraform-state"
    key            = "app038/terraform.tfstate"
    region         = "us-west-2"
    encrypt        = true
    dynamodb_table = "terraform-state-lock"
  }
}
```

##### Step 4: Provision Infrastructure

```bash
cd terraform

# Initialize
terraform init

# Create terraform.tfvars
cat > terraform.tfvars <<EOF
project_name = "app038"
environment = "production"
aws_region = "us-west-2"
db_password = "$(openssl rand -base64 32)"
domain_name = "yourdomain.com"
EOF

# Plan
terraform plan -out=tfplan

# Apply (akan membuat VPC, EKS, RDS, dll)
terraform apply tfplan

# Save outputs
terraform output -json > ../terraform-outputs.json
```

**Output yang dihasilkan:**
- EKS Cluster Name
- RDS Endpoint
- VPC ID
- Security Group IDs

##### Step 5: Configure kubectl

```bash
# Update kubeconfig
aws eks update-kubeconfig \
  --region us-west-2 \
  --name app038-eks-cluster

# Verify
kubectl cluster-info
kubectl get nodes
```

#### Phase 2: Container Registry Setup

##### Step 6: Setup GitHub Container Registry

```bash
# Login ke GitHub Container Registry
echo $GITHUB_TOKEN | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# Verify
docker info | grep Username
```

**Atau setup GitHub Secrets untuk CI/CD:**
1. GitHub Repository → Settings → Secrets and variables → Actions
2. Add secret: `GITHUB_TOKEN` dengan Personal Access Token

##### Step 7: Build & Push Docker Images

**Manual Build (untuk testing):**

```bash
# Build Laravel image
docker buildx build \
  --platform linux/amd64 \
  -f docker/php/Dockerfile \
  -t ghcr.io/YOUR_USERNAME/app038/laravel:latest \
  -t ghcr.io/YOUR_USERNAME/app038/laravel:v1.0.0 \
  --push .

# Build Svelte image
docker buildx build \
  --platform linux/amd64 \
  -f docker/svelte/Dockerfile \
  -t ghcr.io/YOUR_USERNAME/app038/svelte:latest \
  -t ghcr.io/YOUR_USERNAME/app038/svelte:v1.0.0 \
  --push .
```

**Atau gunakan CI/CD (Recommended):**
- Push code ke GitHub
- GitHub Actions akan otomatis build dan push images

#### Phase 3: Kubernetes Deployment

##### Step 8: Create Namespace

```bash
kubectl create namespace app038-production

# Verify
kubectl get namespaces | grep app038
```

##### Step 9: Setup Secrets

```bash
# Generate strong passwords
DB_PASSWORD=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)
RABBITMQ_PASSWORD=$(openssl rand -base64 32)
APP_KEY=$(php artisan key:generate --show | grep -oP 'base64:\K.*')

# Create Kubernetes secrets
kubectl create secret generic app038-secrets \
  --from-literal=DB_PASSWORD="$DB_PASSWORD" \
  --from-literal=REDIS_PASSWORD="$REDIS_PASSWORD" \
  --from-literal=RABBITMQ_PASSWORD="$RABBITMQ_PASSWORD" \
  --from-literal=APP_KEY="$APP_KEY" \
  --namespace=app038-production

# Verify
kubectl get secrets -n app038-production
```

##### Step 10: Setup Image Pull Secret

```bash
# Create registry secret
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=YOUR_USERNAME \
  --docker-password=$GITHUB_TOKEN \
  --namespace=app038-production
```

##### Step 11: Install Helm Chart

```bash
cd helm/app038

# Update dependencies (jika ada)
helm dependency update

# Install chart
helm upgrade --install app038 . \
  --namespace app038-production \
  --set laravel.image.repository=ghcr.io/YOUR_USERNAME/app038/laravel \
  --set laravel.image.tag=latest \
  --set svelte.image.repository=ghcr.io/YOUR_USERNAME/app038/svelte \
  --set svelte.image.tag=latest \
  --set ingress.hosts[0].host=app038.yourdomain.com \
  --set secrets.create=false \
  --set secrets.dbPassword=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d) \
  --wait \
  --timeout 10m

# Verify
helm list -n app038-production
kubectl get pods -n app038-production
```

##### Step 12: Setup Database

```bash
# Get RDS endpoint dari Terraform output
RDS_ENDPOINT=$(cat terraform-outputs.json | jq -r '.rds_endpoint.value')

# Connect dan create database
psql -h $RDS_ENDPOINT -U postgres -c "CREATE DATABASE app038_production;"
psql -h $RDS_ENDPOINT -U postgres -c "CREATE USER app038_user WITH PASSWORD '$DB_PASSWORD';"
psql -h $RDS_ENDPOINT -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE app038_production TO app038_user;"

# Run migrations
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan migrate --force

# Run seeders
kubectl exec -it deployment/app038-laravel -n app038-production -- \
  php artisan db:seed --force
```

##### Step 13: Setup SSL/TLS

```bash
# Install cert-manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Wait for cert-manager
kubectl wait --for=condition=ready pod \
  -l app.kubernetes.io/instance=cert-manager \
  -n cert-manager \
  --timeout=300s

# Create ClusterIssuer
kubectl apply -f - <<EOF
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: your-email@example.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF

# Update Helm values untuk SSL
helm upgrade app038 ./helm/app038 \
  --namespace app038-production \
  --reuse-values \
  --set ingress.annotations."cert-manager\.io/cluster-issuer"=letsencrypt-prod \
  --wait

# Verify certificate
kubectl get certificate -n app038-production
```

##### Step 14: Setup DNS

```bash
# Get Load Balancer IP
kubectl get ingress -n app038-production

# Update DNS records di Route53 atau DNS provider
# A record: app038.yourdomain.com -> [Load Balancer IP]
# Atau CNAME: app038.yourdomain.com -> [Load Balancer Hostname]
```

#### Phase 4: CI/CD Setup

##### Step 15: Configure GitHub Actions

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

### Docker Compose
```bash
# Start
docker-compose -f docker-compose.prod.yml up -d

# Stop
docker-compose -f docker-compose.prod.yml down

# Logs
docker-compose -f docker-compose.prod.yml logs -f

# Restart
docker-compose -f docker-compose.prod.yml restart
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

**URL:** `https://app038.yourdomain.com`

**Next Steps:**
1. Setup monitoring alerts
2. Configure automated backups
3. Setup disaster recovery plan
4. Document runbooks
5. Train team members
