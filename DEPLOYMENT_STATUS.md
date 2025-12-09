# 📊 Deployment Status - Kubernetes (Opsi 2)

Status deployment App038 ke Kubernetes berdasarkan `DEPLOYMENT_GUIDE.md`.

## ✅ Completed Steps

### Phase 1: Infrastructure Setup

#### ✅ Step 1: Tools Installation
- ✅ AWS CLI: `aws-cli/2.32.10` - **INSTALLED**
- ✅ Terraform: `v1.5.7` - **INSTALLED**
- ✅ Helm: `v4.0.1` - **INSTALLED**
- ✅ kubectl: `v1.34.1` - **INSTALLED**
- ✅ Docker: **INSTALLED**
- ✅ jq: **INSTALLED**

#### ✅ Step 2: Setup AWS Credentials
**Status:** ✅ **COMPLETED**

- ✅ AWS CLI configured
- ✅ Account: `040681451912`
- ✅ User: `idmobstic`
- ✅ Region: `us-west-2`
- ✅ Output format: `json`

**Verified:**
```bash
aws sts get-caller-identity
# Output: Account 040681451912, User idmobstic
```

#### ✅ Step 3: Setup Terraform Backend
**Status:** ✅ **COMPLETED**

- ✅ S3 Bucket: `app038-terraform-state` - **Created**
- ✅ S3 Versioning: **Enabled**
- ⚠️ S3 Encryption: Not enabled (permission needed, optional)
- ✅ DynamoDB Table: `terraform-state-lock` - **Active**

**Verified:**
```bash
aws s3 ls s3://app038-terraform-state
aws dynamodb describe-table --table-name terraform-state-lock --region us-west-2
```

---

## 🆓 Opsi Deployment GRATIS (Free Tier)

**Jika Anda ingin deployment dengan biaya GRATIS**, gunakan opsi berikut:

### 🆓 Quick Start - Fly.io Free Tier (Recommended) ⭐

**Total Biaya: $0 (GRATIS)**
**Total Waktu: 30-45 menit**

```bash
# 1. Install Fly CLI
curl -L https://fly.io/install.sh | sh

# 2. Login
fly auth login

# 3. Create app
fly launch --name app038 --region ams --no-deploy

# 4. Create PostgreSQL database
fly postgres create --name app038-db --region ams --vm-size shared-cpu-1x --volume-size 3

# 5. Attach database
fly postgres attach app038-db --app app038

# 6. Configure fly.toml (edit file yang dibuat)
# 7. Set secrets
fly secrets set APP_KEY="base64:YOUR_KEY" --app app038

# 8. Deploy
fly deploy --app app038
```

**✅ Website sudah online di:** `https://app038.fly.dev`

**Guide Lengkap:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment" → "Opsi 0A: Fly.io"

---

### 📊 Perbandingan Free Tier Options

| Platform | Free Tier | PostgreSQL | Sleep Mode | Setup Time | Best For |
|----------|-----------|------------|------------|------------|----------|
| **Fly.io** ⭐ | 3 VMs, 3GB | ✅ Gratis | ❌ No | 30-45 min | Production-ready |
| **Railway** | $5 credit/mo | ✅ Gratis | ❌ No | 20-30 min | Quick deploy |
| **Render** | Web service | ⚠️ Trial 90d | ✅ Yes | 20-30 min | Low traffic |
| **Oracle Cloud** | 2 VMs forever | ⚠️ Self-host | ❌ No | 45-60 min | Full control |

**💡 Rekomendasi untuk GRATIS:** 
- **Best Choice:** **Fly.io Free Tier** - paling mudah, tidak ada sleep mode, PostgreSQL included
- **Quick Setup:** Railway - auto-deploy dari GitHub
- **Full Control:** Oracle Cloud - VPS gratis selamanya

**Guide Lengkap:** Lihat `DEPLOYMENT_GUIDE.md` section "Opsi 0: Free Tier Deployment"

---

## 📋 Next Steps (In Order) - URGENT (Untuk Paid Deployment)

### Phase 1: Infrastructure Setup (Continue)

#### ⏳ Step 4: Configure Terraform
**Status:** ⚠️ **PENDING - Action Required NOW**

**Action Required:**

1. **Update `terraform/main.tf` - Uncomment backend block:**
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

2. **Create `terraform/terraform.tfvars`:**
```bash
cd terraform
cat > terraform.tfvars <<EOF
project_name = "app038"
environment = "production"
aws_region = "us-west-2"
db_password = "$(openssl rand -base64 32)"
domain_name = "yourdomain.com"  # GANTI dengan domain Anda
EOF
```

**⚠️ IMPORTANT:** Ganti `yourdomain.com` dengan domain yang sebenarnya!

#### ⏳ Step 5: Provision Infrastructure
```bash
# Script akan otomatis membuat, atau manual:
aws s3 mb s3://app038-terraform-state --region us-west-2
aws s3api put-bucket-versioning \
  --bucket app038-terraform-state \
  --versioning-configuration Status=Enabled
aws dynamodb create-table \
  --table-name terraform-state-lock \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region us-west-2
```

#### Step 4: Configure Terraform
- Update `terraform/main.tf` backend configuration
- Create `terraform/terraform.tfvars`

#### ⏳ Step 5: Provision Infrastructure
**Status:** ⚠️ **PENDING - CRITICAL STEP**

**⚠️ WARNING:** Step ini akan membuat biaya AWS bulanan!

**Action Required:**
```bash
cd terraform

# Initialize Terraform dengan backend
terraform init

# Review plan (PENTING: Review semua resources yang akan dibuat)
terraform plan -out=tfplan

# Apply (akan membuat VPC, EKS, RDS - memakan waktu 15-30 menit)
terraform apply tfplan

# Save outputs untuk langkah selanjutnya
terraform output -json > ../terraform-outputs.json
```

**Resources yang akan dibuat:**
- VPC dengan public/private subnets
- EKS Cluster (Kubernetes)
- RDS PostgreSQL Database
- NAT Gateway
- Security Groups
- Route Tables

**⚠️ Estimated Time:** 15-30 minutes  
**💰 Estimated Cost:** ~$120-250/month  
**💡 Tip:** Review `terraform plan` dengan teliti sebelum apply!

#### ⏳ Step 6: Configure kubectl
**Status:** ⚠️ **PENDING - After Step 5**

**Action Required (setelah Step 5 selesai):**
```bash
# Get cluster name dari Terraform output
EKS_CLUSTER_NAME=$(jq -r '.kubernetes_cluster_name.value // .eks_cluster_name.value' terraform-outputs.json)

# Update kubeconfig
aws eks update-kubeconfig \
  --region us-west-2 \
  --name $EKS_CLUSTER_NAME

# Verify connection
kubectl cluster-info
kubectl get nodes
```

**Expected Output:** 2+ nodes should be in Ready state

---

### Phase 2: Container Registry Setup

#### ⏳ Step 7: Setup GitHub Container Registry
**Status:** ⚠️ **PENDING - User Action Required**

**Prerequisites:**
- GitHub Personal Access Token dengan permission: `write:packages`, `read:packages`
- GitHub Username

**Action Required:**
```bash
export GITHUB_TOKEN="your_token"
export GITHUB_USERNAME="your_username"
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_USERNAME --password-stdin
```

#### ⏳ Step 8: Build & Push Docker Images
**Status:** ⚠️ **PENDING - After Step 7**

**Action Required (setelah Step 7 selesai):**
```bash
docker buildx build --platform linux/amd64 \
  -f docker/php/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/laravel:latest \
  --push .

docker buildx build --platform linux/amd64 \
  -f docker/svelte/Dockerfile \
  -t ghcr.io/$GITHUB_USERNAME/app038/svelte:latest \
  --push .
```

---

### Phase 3: Kubernetes Deployment

#### ⏳ Step 9: Install Ingress-Nginx Controller
**Status:** ⚠️ **PENDING - CRITICAL untuk akses online**

**⚠️ PENTING:** Tanpa Ingress Controller, aplikasi TIDAK BISA diakses dari internet!

**Action Required:**
```bash
# Install ingress-nginx menggunakan Helm
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update

helm upgrade --install ingress-nginx ingress-nginx/ingress-nginx \
  --namespace ingress-nginx \
  --create-namespace \
  --set controller.service.type=LoadBalancer \
  --set controller.service.annotations."service\.beta\.kubernetes\.io/aws-load-balancer-type"=nlb \
  --set controller.replicaCount=2 \
  --wait \
  --timeout 5m

# Get Load Balancer hostname/IP (untuk DNS setup nanti)
kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}'
```

#### ⏳ Step 10: Install cert-manager untuk SSL/TLS
**Status:** ⚠️ **PENDING - After Step 9**

**Action Required:**
```bash
# Install cert-manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Wait for cert-manager to be ready
kubectl wait --for=condition=ready pod \
  -l app.kubernetes.io/instance=cert-manager \
  -n cert-manager \
  --timeout=300s

# Create ClusterIssuer untuk Let's Encrypt
# GANTI EMAIL dengan email Anda yang valid!
read -p "Masukkan email untuk Let's Encrypt: " LETSENCRYPT_EMAIL

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
```

#### ⏳ Step 11: Create Namespace & Setup Secrets
**Status:** ⚠️ **PENDING - After Step 10**

**Action Required:**
```bash
# Create namespace
kubectl create namespace app038-production

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

# ⚠️ SIMPAN PASSWORD INI DENGAN AMAN!
echo "DB_PASSWORD: $DB_PASSWORD" > deployment-secrets.txt
echo "REDIS_PASSWORD: $REDIS_PASSWORD" >> deployment-secrets.txt
echo "RABBITMQ_PASSWORD: $RABBITMQ_PASSWORD" >> deployment-secrets.txt
echo "APP_KEY: $APP_KEY" >> deployment-secrets.txt
```

#### ⏳ Step 12: Setup Image Pull Secret
**Status:** ⚠️ **PENDING - After Step 7**

**Action Required:**
```bash
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=$GITHUB_USERNAME \
  --docker-password=$GITHUB_TOKEN \
  --namespace=app038-production
```

#### ⏳ Step 13: Deploy Aplikasi dengan Helm
**Status:** ⚠️ **PENDING - After Steps 11-12**

**Action Required:**
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
```

**⚠️ IMPORTANT:** Ganti `app038.yourdomain.com` dengan domain yang sebenarnya!

#### ⏳ Step 14: Setup Database & Run Migrations
**Status:** ⚠️ **PENDING - After Step 13**

**Action Required:**
```bash
# Get RDS endpoint dari Terraform output
RDS_ENDPOINT=$(jq -r '.database_endpoint.value // .rds_endpoint.value' terraform-outputs.json)

# Update ConfigMap dengan RDS endpoint
kubectl create configmap app038-config \
  --from-literal=DB_HOST="$RDS_ENDPOINT" \
  --from-literal=DB_PORT="5432" \
  --from-literal=DB_DATABASE="app038_production" \
  --from-literal=DB_USERNAME="app038_user" \
  --namespace=app038-production \
  --dry-run=client -o yaml | kubectl apply -f -

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
```

#### ⏳ Step 15: Configure DNS
**Status:** ⚠️ **PENDING - CRITICAL untuk akses online**

**⚠️ PENTING:** Tanpa DNS, aplikasi TIDAK BISA diakses dari internet!

**Action Required:**
```bash
# Get Ingress Load Balancer hostname/IP
INGRESS_HOSTNAME=$(kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
INGRESS_IP=$(kubectl get svc ingress-nginx-controller -n ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].ip}')

echo "Ingress Load Balancer: $INGRESS_HOSTNAME or $INGRESS_IP"

# Update DNS records:
# - A record: app038.yourdomain.com -> $INGRESS_IP
# - Atau CNAME: app038.yourdomain.com -> $INGRESS_HOSTNAME

# Update Ingress dengan domain yang benar
cd helm/app038
helm upgrade app038 . \
  --namespace app038-production \
  --reuse-values \
  --set ingress.hosts[0].host=app038.yourdomain.com \
  --set ingress.tls[0].hosts[0]=app038.yourdomain.com \
  --wait
```

#### ⏳ Step 16: Verify Aplikasi Bisa Diakses Online
**Status:** ⚠️ **PENDING - Final Step**

**Action Required:**
```bash
# Check semua pods status
kubectl get pods -n app038-production

# Check ingress
kubectl get ingress -n app038-production

# Check certificates
kubectl get certificate -n app038-production

# Test application
DOMAIN_NAME="app038.yourdomain.com"
curl -v http://$DOMAIN_NAME/health
curl -v https://$DOMAIN_NAME/health
```

**Expected Results:**
- ✅ Semua pods Running
- ✅ Ingress memiliki Load Balancer address
- ✅ Certificate status Ready
- ✅ HTTP/HTTPS endpoints return 200 OK

---

## 🚀 Quick Start Options

### Option A: Automated Script (Recommended)

```bash
# 1. Setup AWS credentials
aws configure

# 2. Set GitHub credentials
export GITHUB_TOKEN="your_token"
export GITHUB_USERNAME="your_username"
export DOMAIN_NAME="app038.yourdomain.com"

# 3. Run deployment
./QUICK_DEPLOY_K8S.sh
```

### Option B: Step-by-Step Manual

Follow instructions in `DEPLOY_K8S_INTERACTIVE.md`

### Option C: Use Main Deployment Script

```bash
./scripts/deploy-k8s.sh
```

---

## 📝 Required Information

Before starting deployment, prepare:

1. **AWS Credentials:**
   - Access Key ID
   - Secret Access Key
   - IAM permissions: EC2, EKS, RDS, S3, Route53, IAM

2. **GitHub Credentials:**
   - Personal Access Token (with `write:packages` permission)
   - GitHub Username

3. **Domain Name:**
   - Domain yang sudah terdaftar
   - Akses ke DNS provider

---

## ⚠️ Important Notes

1. **Costs:** Infrastructure akan memakan biaya bulanan (~$120-250)
2. **Time:** Full deployment memakan waktu 1-2 jam
3. **Credentials:** Simpan semua credentials dengan aman
4. **Backup:** Setup automated backups setelah deployment

---

## 📚 Documentation

- **Full Guide:** `DEPLOYMENT_GUIDE.md`
- **Interactive Guide:** `DEPLOY_K8S_INTERACTIVE.md`
- **Deployment Script:** `scripts/deploy-k8s.sh`
- **Quick Script:** `QUICK_DEPLOY_K8S.sh`

---

## 🎯 Current Status

### ✅ Completed Steps:
1. ✅ Tools installed (AWS CLI, Terraform, Helm, kubectl, Docker, jq)
2. ✅ AWS credentials configured (Account: 040681451912, User: idmobstic)
3. ✅ Terraform Backend setup (S3 bucket + DynamoDB table created)

### ⏳ Next Critical Steps (Urutan Penting):

**IMMEDIATE ACTION REQUIRED:**
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

### 📋 Prerequisites yang Perlu Disiapkan:

**Before Step 4:**
- [ ] Domain name (untuk production)
- [ ] Review dan update `terraform/terraform.tfvars` dengan domain yang benar

**Before Step 7:**
- [ ] GitHub Personal Access Token (dengan `write:packages` permission)
- [ ] GitHub Username

**Before Step 15:**
- [ ] Akses ke DNS provider (Route53 atau DNS provider lain)
- [ ] Domain sudah pointing ke Load Balancer

### ⚠️ Important Notes:

1. **Costs:** Infrastructure provisioning (Step 5) akan memakan biaya bulanan ~$120-250
2. **Time:** Full deployment memakan waktu 1-2 jam
3. **Critical Steps:** Step 9 (Ingress) dan Step 15 (DNS) adalah CRITICAL - tanpa ini aplikasi tidak bisa diakses online
4. **Credentials:** Simpan semua passwords dengan aman (deployment-secrets.txt)

### 🚀 Quick Start:

**Option 1: Automated Script**
```bash
# Set environment variables
export GITHUB_TOKEN="your_token"
export GITHUB_USERNAME="your_username"
export DOMAIN_NAME="app038.yourdomain.com"

# Run deployment script
./scripts/deploy-k8s.sh
```

**Option 2: Step-by-Step Manual**
Follow instructions in `DEPLOY_K8S_INTERACTIVE.md` (recommended untuk first-time deployment)

**Next Immediate Action:** 
1. Update `terraform/main.tf` - uncomment backend block
2. Create `terraform/terraform.tfvars` dengan domain yang benar
3. Continue dengan Step 5: Provision Infrastructure

