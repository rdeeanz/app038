# 🚀 Kubernetes Deployment - Interactive Guide

Panduan interaktif untuk menjalankan Opsi 2: Kubernetes Deployment.

## Status Tools

✅ **Tools yang sudah terinstall:**
- AWS CLI: `aws-cli/2.32.10` ✅
- Terraform: `v1.5.7` ✅
- Helm: `v4.0.1` ✅
- kubectl: `v1.34.1` ✅
- Docker: Installed ✅
- jq: Installed ✅

## Status Deployment

✅ **Sudah Selesai:**
- [x] Tools installation
- [x] AWS credentials configuration
- [x] Terraform Backend setup (S3 bucket + DynamoDB table)

## 🆓 Opsi Deployment GRATIS (Free Tier)

**⚠️ PENTING:** Jika Anda ingin deployment dengan biaya **GRATIS**, gunakan opsi berikut:

### 🆓 Opsi 0: Free Tier Deployment (100% GRATIS)

**Perfect untuk:** MVP, testing, personal projects

**Opsi yang Tersedia:**
1. **Fly.io Free Tier** ⭐ (Recommended)
   - 3 shared-cpu-1x VMs gratis
   - PostgreSQL gratis (3GB)
   - 160GB data transfer gratis
   - Setup: 30-45 menit
   - **Guide:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment" → "Opsi 0A: Fly.io"

2. **Railway Free Tier**
   - $5 credit per bulan
   - PostgreSQL gratis
   - Auto-deploy dari GitHub
   - Setup: 20-30 menit
   - **Guide:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment" → "Opsi 0B: Railway"

3. **Render Free Tier**
   - Web service gratis (sleeps after inactivity)
   - PostgreSQL trial 90 hari
   - Setup: 20-30 menit
   - **Guide:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment" → "Opsi 0C: Render"

4. **Oracle Cloud Free Tier**
   - 2 VMs gratis selamanya
   - 200GB storage gratis
   - Setup: 45-60 menit
   - **Guide:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment" → "Opsi 0D: Oracle Cloud"

**💡 Rekomendasi untuk GRATIS:** Gunakan **Fly.io Free Tier** - paling mudah dan reliable.

---

⏳ **Langkah Selanjutnya untuk PAID Deployment (AWS EKS):**
1. ✅ ~~Setup Terraform Backend (S3 + DynamoDB)~~ - **COMPLETED**
2. ⏳ **Configure Terraform** - Uncomment backend block & create terraform.tfvars - **NEXT STEP**
3. ⏳ **Provision Infrastructure** (EKS, RDS, VPC) - **15-30 menit, akan ada biaya ~$120-250/bulan**
4. ⏳ **Configure kubectl** - Connect ke EKS cluster
5. ⏳ **Setup GitHub Container Registry** - Login ke GHCR
6. ⏳ **Build & Push Docker Images** - Build Laravel & Svelte images
7. ⏳ **Install Ingress-Nginx Controller** - **CRITICAL untuk akses online**
8. ⏳ **Install cert-manager** untuk SSL/TLS
9. ⏳ **Deploy aplikasi ke Kubernetes** - Create namespace, secrets, deploy dengan Helm
10. ⏳ **Setup Database & Run Migrations**
11. ⏳ **Configure DNS** - **CRITICAL untuk akses online**
12. ⏳ **Verify aplikasi bisa diakses online**

**⚠️ Catatan:** Langkah di atas adalah untuk **PAID deployment** (AWS EKS). Untuk **GRATIS**, gunakan opsi di atas.

## 🎯 Ringkasan Langkah-Langkah Deployment

Untuk membuat aplikasi bisa diakses online, ikuti urutan langkah berikut:

### Phase 1: Infrastructure (15-30 menit)
1. ✅ AWS Credentials - **SUDAH SELESAI**
2. ✅ Setup Terraform Backend (S3 + DynamoDB) - **SUDAH SELESAI**
3. ⏳ **Configure Terraform** - Uncomment backend block & create terraform.tfvars - **NEXT STEP**
4. ⏳ Provision Infrastructure (EKS, RDS, VPC) - **PENTING: Membuat biaya AWS**

### Phase 2: Container Registry (10-15 menit)
4. Setup GitHub Container Registry
5. Build & Push Docker Images

### Phase 3: Kubernetes Deployment (20-30 menit)
6. Install Ingress-Nginx Controller - **PENTING: Tanpa ini aplikasi tidak bisa diakses dari internet**
7. Install cert-manager untuk SSL/TLS
8. Create Namespace & Secrets
9. Deploy aplikasi dengan Helm
10. Setup Database & Run Migrations
11. Configure DNS - **PENTING: Tanpa DNS aplikasi tidak bisa diakses**
12. Verify aplikasi bisa diakses online

**Total waktu estimasi: 45-75 menit**

---

## ⚠️ Prasyarat yang Perlu Disiapkan

Sebelum memulai deployment, pastikan Anda memiliki:

1. **AWS Account** dengan:
   - Access Key ID dan Secret Access Key
   - IAM permissions untuk: EC2, EKS, RDS, S3, Route53, IAM
   - Budget yang cukup (EKS cluster + RDS akan memakan biaya)

2. **GitHub Account** dengan:
   - Personal Access Token (dengan `write:packages` permission)
   - Akses ke repository app038

3. **Domain Name** (opsional untuk testing, tapi diperlukan untuk production)

---

## 🎯 Langkah-langkah Deployment

### Phase 1: Infrastructure Setup

#### Step 1: Setup AWS Credentials

```bash
# Configure AWS credentials
aws configure

# Anda akan diminta untuk:
# AWS Access Key ID: [masukkan access key]
# AWS Secret Access Key: [masukkan secret key]
# Default region name: us-west-2
# Default output format: json

# Verify
aws sts get-caller-identity
```

**Jika belum punya AWS credentials:**
1. Login ke AWS Console
2. IAM → Users → Your User → Security credentials
3. Create access key
4. Download dan simpan dengan aman

#### Step 2: Setup Terraform Backend

**✅ STATUS: COMPLETED**

Terraform backend sudah dibuat:
- ✅ S3 Bucket: `app038-terraform-state` - Created
- ✅ S3 Versioning: Enabled
- ⚠️ S3 Encryption: Not enabled (optional, permission needed)
- ✅ DynamoDB Table: `terraform-state-lock` - Active

**Verification:**
```bash
aws s3 ls s3://app038-terraform-state
aws dynamodb describe-table --table-name terraform-state-lock --region us-west-2
```

**Note:** Jika perlu enable encryption, lihat: `terraform/REQUEST_ENCRYPTION_PERMISSION.md`

---

#### Step 3: Configure Terraform

**⚠️ PENTING:** Step ini WAJIB dilakukan sebelum provision infrastructure!

**Action Required:**

```bash
# 1. Create S3 bucket untuk Terraform state
S3_BUCKET="app038-terraform-state"
AWS_REGION="us-west-2"

# Check if bucket exists
if ! aws s3 ls "s3://${S3_BUCKET}" 2>&1 | grep -q 'NoSuchBucket'; then
    echo "Bucket sudah ada, skip..."
else
    echo "Creating S3 bucket..."
    aws s3 mb "s3://${S3_BUCKET}" --region "${AWS_REGION}"
    
    # Enable versioning (untuk backup state)
    aws s3api put-bucket-versioning \
      --bucket "${S3_BUCKET}" \
      --versioning-configuration Status=Enabled
    
    # Enable encryption
    aws s3api put-bucket-encryption \
      --bucket "${S3_BUCKET}" \
      --server-side-encryption-configuration '{
          "Rules": [{
              "ApplyServerSideEncryptionByDefault": {
                  "SSEAlgorithm": "AES256"
              }
          }]
      }'
    
    echo "✅ S3 bucket created: ${S3_BUCKET}"
fi

# 2. Create DynamoDB table untuk state locking
DYNAMODB_TABLE="terraform-state-lock"

if aws dynamodb describe-table --table-name "${DYNAMODB_TABLE}" --region "${AWS_REGION}" &> /dev/null; then
    echo "DynamoDB table sudah ada, skip..."
else
    echo "Creating DynamoDB table..."
    aws dynamodb create-table \
      --table-name "${DYNAMODB_TABLE}" \
      --attribute-definitions AttributeName=LockID,AttributeType=S \
      --key-schema AttributeName=LockID,KeyType=HASH \
      --billing-mode PAY_PER_REQUEST \
      --region "${AWS_REGION}"
    
    # Wait for table to be active
    echo "Waiting for DynamoDB table to be active..."
    aws dynamodb wait table-exists --table-name "${DYNAMODB_TABLE}" --region "${AWS_REGION}"
    
    echo "✅ DynamoDB table created: ${DYNAMODB_TABLE}"
fi
```

#### Step 3: Configure Terraform

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
- Hapus tanda `#` di depan `backend "s3"` dan semua baris di dalamnya (baris 24-30)
- Simpan file

#### Step 4: Create terraform.tfvars

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

#### Step 5: Provision Infrastructure

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

#### Step 6: Configure kubectl

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

---

### Phase 2: Container Registry Setup

#### Step 7: Setup GitHub Container Registry

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

#### Step 8: Build & Push Docker Images

```bash
# Build Laravel image
docker buildx build \
  --platform linux/amd64 \
  -f docker/php/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/laravel:latest \
  --push .

# Build Svelte image
docker buildx build \
  --platform linux/amd64 \
  -f docker/svelte/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/svelte:latest \
  --push .
```

**Note:** Untuk production, gunakan CI/CD pipeline yang sudah dikonfigurasi.

---

### Phase 3: Kubernetes Deployment

#### Step 9: Install Ingress-Nginx Controller

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

#### Step 10: Create Namespace

```bash
kubectl create namespace app038-production

# Verify
kubectl get namespaces | grep app038
```

#### Step 11: Setup Secrets

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

#### Step 12: Setup Image Pull Secret

```bash
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=$GITHUB_USERNAME \
  --docker-password=$GITHUB_TOKEN \
  --namespace=app038-production

# Verify
kubectl get secrets -n app038-production | grep ghcr
```

#### Step 13: Install Helm Chart

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

#### Step 14: Setup Database & Environment Variables

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

# Connect ke RDS dan create database (jika belum ada)
# Install psql client jika belum ada:
# macOS: brew install postgresql
# Linux: sudo apt-get install postgresql-client

# Get DB password dari secret
DB_PASSWORD=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d)

# Create database dan user (jika belum ada)
PGPASSWORD=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d) \
psql -h $RDS_ENDPOINT -U postgres -c "CREATE DATABASE app038_production;" || echo "Database mungkin sudah ada"
PGPASSWORD=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d) \
psql -h $RDS_ENDPOINT -U postgres -c "CREATE USER app038_user WITH PASSWORD '$DB_PASSWORD';" || echo "User mungkin sudah ada"
PGPASSWORD=$(kubectl get secret app038-secrets -n app038-production -o jsonpath='{.data.DB_PASSWORD}' | base64 -d) \
psql -h $RDS_ENDPOINT -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE app038_production TO app038_user;"

# Update Laravel deployment untuk menggunakan ConfigMap
# (ConfigMap sudah direferensikan di Helm values.yaml)

```bash
# Get RDS endpoint dari Terraform output
RDS_ENDPOINT=$(jq -r '.database_endpoint.value' terraform-outputs.json)

# Connect dan create database
psql -h $RDS_ENDPOINT -U postgres -c "CREATE DATABASE app038_production;"
psql -h $RDS_ENDPOINT -U postgres -c "CREATE USER app038_user WITH PASSWORD '$DB_PASSWORD';"
psql -h $RDS_ENDPOINT -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE app038_production TO app038_user;"

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

#### Step 15: Install cert-manager untuk SSL/TLS

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

#### Step 16: Setup DNS

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

---

## 🚀 Quick Start dengan Script

Atau gunakan script otomatis:

```bash
# Set environment variables
export GITHUB_TOKEN="your_github_token"
export GITHUB_USERNAME="your_github_username"
export DOMAIN_NAME="app038.yourdomain.com"

# Run deployment script
./scripts/deploy-k8s.sh
```

---

## ✅ Verifikasi Deployment

### Step 17: Comprehensive Verification

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

echo "=== Svelte Logs (last 20 lines) ==="
kubectl logs deployment/app038-svelte -n app038-production --tail=20

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

### Expected Results:

✅ **Semua pods harus Running:**
- `app038-laravel-*` pods: Running (3 replicas)
- `app038-svelte-*` pods: Running (2 replicas)
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

---

## 📝 Catatan Penting

1. **Biaya AWS:** Infrastructure akan memakan biaya bulanan
2. **Domain:** Pastikan domain sudah pointing ke Load Balancer
3. **Secrets:** Simpan semua password dengan aman
4. **Backup:** Setup automated backups untuk database
5. **Monitoring:** Install Prometheus & Grafana untuk monitoring

---

## 🆘 Troubleshooting

### Masalah Umum dan Solusi

#### 1. Pods tidak bisa start (ImagePullBackOff)

```bash
# Check image pull secret
kubectl get secrets -n app038-production | grep ghcr

# Verify image exists
docker pull ghcr.io/$GITHUB_USERNAME/app038/laravel:latest

# Recreate image pull secret jika perlu
kubectl delete secret ghcr-secret -n app038-production
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=$GITHUB_USERNAME \
  --docker-password=$GITHUB_TOKEN \
  --namespace=app038-production
```

#### 2. Certificate tidak ter-issue

```bash
# Check certificate status
kubectl describe certificate app038-tls -n app038-production

# Check order status
kubectl get order -n app038-production
kubectl describe order -n app038-production

# Check challenge status
kubectl get challenge -n app038-production
kubectl describe challenge -n app038-production

# Common issues:
# - DNS belum pointing ke Load Balancer
# - Ingress class tidak match (harus "nginx")
# - ClusterIssuer tidak ditemukan
```

#### 3. Database connection error

```bash
# Check database endpoint
cd terraform && terraform output -json | jq -r '.database_endpoint.value'

# Test connection dari local
psql -h <RDS_ENDPOINT> -U postgres -d app038_production

# Check security groups (RDS harus allow traffic dari EKS)
# Check ConfigMap
kubectl get configmap app038-config -n app038-production -o yaml

# Check secrets
kubectl get secret app038-secrets -n app038-production -o yaml
```

#### 4. Ingress tidak accessible

```bash
# Check ingress controller
kubectl get pods -n ingress-nginx
kubectl get svc -n ingress-nginx

# Check ingress status
kubectl describe ingress -n app038-production

# Check Load Balancer
aws elbv2 describe-load-balancers --region us-west-2

# Test dari dalam cluster
kubectl run -it --rm debug --image=curlimages/curl --restart=Never -- \
  curl http://app038-laravel.app038-production.svc.cluster.local/health
```

#### 5. Application error 502/503

```bash
# Check pod logs
kubectl logs -f deployment/app038-laravel -n app038-production

# Check pod status
kubectl describe pod -l app.kubernetes.io/component=laravel -n app038-production

# Check resource limits
kubectl top pods -n app038-production

# Common issues:
# - Out of memory
# - Database connection timeout
# - Application crash loop
```

#### 6. DNS tidak resolve

```bash
# Test DNS resolution
nslookup $DOMAIN_NAME
dig $DOMAIN_NAME

# Check Route53 records
aws route53 list-resource-record-sets --hosted-zone-id $HOSTED_ZONE_ID

# Wait for DNS propagation (bisa 5-30 menit)
```

### Debug Commands

```bash
# Get semua resources di namespace
kubectl get all -n app038-production

# Describe problematic resource
kubectl describe <resource-type> <resource-name> -n app038-production

# Check events
kubectl get events -n app038-production --sort-by='.lastTimestamp'

# Port forward untuk testing lokal
kubectl port-forward svc/app038-laravel 8080:80 -n app038-production
# Test: curl http://localhost:8080/health
```

### Rollback Deployment

```bash
# Rollback Helm release
helm rollback app038 -n app038-production

# Delete dan reinstall
helm uninstall app038 -n app038-production
# Kemudian install ulang dengan konfigurasi yang benar
```

Lihat juga bagian Troubleshooting di `DEPLOYMENT_GUIDE.md` untuk solusi masalah umum lainnya.

---

## 📋 Checklist Final

Sebelum menganggap deployment selesai, pastikan semua checklist ini sudah ✅:

- [ ] ✅ AWS credentials configured dan verified
- [ ] ✅ Terraform backend (S3 + DynamoDB) created
- [ ] ✅ Infrastructure provisioned (EKS, RDS, VPC)
- [ ] ✅ kubectl configured dan bisa connect ke cluster
- [ ] ✅ Ingress-Nginx controller installed dan Load Balancer ready
- [ ] ✅ GitHub Container Registry configured
- [ ] ✅ Docker images built dan pushed
- [ ] ✅ cert-manager installed
- [ ] ✅ ClusterIssuer created untuk Let's Encrypt
- [ ] ✅ Namespace created
- [ ] ✅ Secrets created (DB, Redis, RabbitMQ, APP_KEY)
- [ ] ✅ Image pull secret created
- [ ] ✅ Helm chart installed
- [ ] ✅ Database created dan migrations run
- [ ] ✅ DNS configured (A atau CNAME record)
- [ ] ✅ Ingress updated dengan domain yang benar
- [ ] ✅ SSL certificate issued (status Ready)
- [ ] ✅ Application accessible via HTTP
- [ ] ✅ Application accessible via HTTPS
- [ ] ✅ Health endpoint returning 200 OK
- [ ] ✅ Database connection working
- [ ] ✅ Redis connection working

## 🎯 Post-Deployment Steps

Setelah aplikasi online, lakukan:

1. **Setup Monitoring:**
   ```bash
   # Install Prometheus & Grafana (jika belum)
   helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
   helm install monitoring prometheus-community/kube-prometheus-stack -n monitoring --create-namespace
   ```

2. **Setup Backups:**
   - Configure automated RDS snapshots
   - Setup S3 backup untuk persistent volumes

3. **Security Hardening:**
   - Review security groups
   - Enable VPC Flow Logs
   - Setup WAF (Web Application Firewall)

4. **Performance Tuning:**
   - Monitor resource usage
   - Adjust HPA thresholds
   - Optimize database queries

5. **Documentation:**
   - Document all credentials (simpan dengan aman!)
   - Update runbooks
   - Document rollback procedures

---

**Selamat! Website Anda sekarang sudah online di Kubernetes! 🎉**

**Akses aplikasi di:** `https://$DOMAIN_NAME`

