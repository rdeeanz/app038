#!/bin/bash

# Kubernetes Deployment Script for App038
# This script automates the deployment process for Opsi 2: Kubernetes Deployment

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="app038"
ENVIRONMENT="production"
AWS_REGION="us-west-2"
NAMESPACE="app038-production"
DOMAIN_NAME="${DOMAIN_NAME:-app038.example.com}"
GITHUB_USERNAME="${GITHUB_USERNAME:-YOUR_USERNAME}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}App038 Kubernetes Deployment${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Function to check if command exists
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}Error: $1 is not installed${NC}"
        return 1
    fi
    echo -e "${GREEN}✓ $1 is installed${NC}"
    return 0
}

# Function to install tools on macOS
install_tools_macos() {
    echo -e "${YELLOW}Installing required tools for macOS...${NC}"
    
    # Check if Homebrew is installed
    if ! command -v brew &> /dev/null; then
        echo -e "${RED}Homebrew is not installed. Please install it first:${NC}"
        echo "/bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
        exit 1
    fi
    
    # Install AWS CLI
    if ! command -v aws &> /dev/null; then
        echo -e "${YELLOW}Installing AWS CLI...${NC}"
        brew install awscli
    fi
    
    # Install Terraform
    if ! command -v terraform &> /dev/null; then
        echo -e "${YELLOW}Installing Terraform...${NC}"
        brew install terraform
    fi
    
    # Install Helm
    if ! command -v helm &> /dev/null; then
        echo -e "${YELLOW}Installing Helm...${NC}"
        brew install helm
    fi
    
    # Install jq (for JSON parsing)
    if ! command -v jq &> /dev/null; then
        echo -e "${YELLOW}Installing jq...${NC}"
        brew install jq
    fi
}

# Phase 1: Infrastructure Setup
phase1_infrastructure() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Phase 1: Infrastructure Setup${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    
    # Step 1: Check/Install Tools
    echo -e "${YELLOW}Step 1: Checking required tools...${NC}"
    if [[ "$OSTYPE" == "darwin"* ]]; then
        install_tools_macos
    else
        echo -e "${RED}This script currently supports macOS. For Linux, please install tools manually.${NC}"
        exit 1
    fi
    
    # Verify all tools
    check_command aws || exit 1
    check_command terraform || exit 1
    check_command kubectl || exit 1
    check_command helm || exit 1
    check_command docker || exit 1
    check_command jq || exit 1
    
    echo ""
    
    # Step 2: Setup AWS Credentials
    echo -e "${YELLOW}Step 2: Setup AWS Credentials${NC}"
    if [ ! -f ~/.aws/credentials ]; then
        echo -e "${YELLOW}Please configure AWS credentials:${NC}"
        aws configure
    else
        echo -e "${GREEN}✓ AWS credentials found${NC}"
    fi
    
    # Verify AWS access
    echo -e "${YELLOW}Verifying AWS access...${NC}"
    if aws sts get-caller-identity &> /dev/null; then
        echo -e "${GREEN}✓ AWS access verified${NC}"
        aws sts get-caller-identity
    else
        echo -e "${RED}Error: Cannot access AWS. Please check credentials.${NC}"
        exit 1
    fi
    
    echo ""
    
    # Step 3: Setup Terraform Backend
    echo -e "${YELLOW}Step 3: Setup Terraform Backend${NC}"
    
    S3_BUCKET="app038-terraform-state"
    DYNAMODB_TABLE="terraform-state-lock"
    
    # Check if S3 bucket exists
    if aws s3 ls "s3://${S3_BUCKET}" 2>&1 | grep -q 'NoSuchBucket'; then
        echo -e "${YELLOW}Creating S3 bucket for Terraform state...${NC}"
        aws s3 mb "s3://${S3_BUCKET}" --region "${AWS_REGION}"
        
        # Enable versioning
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
        
        echo -e "${GREEN}✓ S3 bucket created${NC}"
    else
        echo -e "${GREEN}✓ S3 bucket already exists${NC}"
    fi
    
    # Check if DynamoDB table exists
    if ! aws dynamodb describe-table --table-name "${DYNAMODB_TABLE}" --region "${AWS_REGION}" &> /dev/null; then
        echo -e "${YELLOW}Creating DynamoDB table for state locking...${NC}"
        aws dynamodb create-table \
            --table-name "${DYNAMODB_TABLE}" \
            --attribute-definitions AttributeName=LockID,AttributeType=S \
            --key-schema AttributeName=LockID,KeyType=HASH \
            --billing-mode PAY_PER_REQUEST \
            --region "${AWS_REGION}"
        
        echo -e "${YELLOW}Waiting for DynamoDB table to be active...${NC}"
        aws dynamodb wait table-exists --table-name "${DYNAMODB_TABLE}" --region "${AWS_REGION}"
        
        echo -e "${GREEN}✓ DynamoDB table created${NC}"
    else
        echo -e "${GREEN}✓ DynamoDB table already exists${NC}"
    fi
    
    echo ""
    
    # Step 4: Configure Terraform
    echo -e "${YELLOW}Step 4: Configure Terraform${NC}"
    cd terraform
    
    # Update terraform/main.tf backend configuration
    if ! grep -q "backend \"s3\"" main.tf; then
        echo -e "${YELLOW}Updating Terraform backend configuration...${NC}"
        # Backup original file
        cp main.tf main.tf.bak
        
        # Uncomment backend block if commented
        sed -i.bak 's/# backend "s3"/backend "s3"/' main.tf
        sed -i.bak "s/#   bucket         = \"terraform-state-bucket\"/  bucket         = \"${S3_BUCKET}\"/" main.tf
        sed -i.bak 's/#   key            = "app038\/terraform.tfstate"/  key            = "app038\/terraform.tfstate"/' main.tf
        sed -i.bak "s/#   region         = \"us-west-2\"/  region         = \"${AWS_REGION}\"/" main.tf
        sed -i.bak 's/#   encrypt        = true/  encrypt        = true/' main.tf
        sed -i.bak "s/#   dynamodb_table = \"terraform-state-lock\"/  dynamodb_table = \"${DYNAMODB_TABLE}\"/" main.tf
        
        echo -e "${GREEN}✓ Terraform backend configured${NC}"
    else
        echo -e "${GREEN}✓ Terraform backend already configured${NC}"
    fi
    
    # Create terraform.tfvars if not exists
    if [ ! -f terraform.tfvars ]; then
        echo -e "${YELLOW}Creating terraform.tfvars...${NC}"
        cat > terraform.tfvars <<EOF
project_name = "${PROJECT_NAME}"
environment = "${ENVIRONMENT}"
aws_region = "${AWS_REGION}"
db_password = "$(openssl rand -base64 32)"
domain_name = "${DOMAIN_NAME}"
EOF
        echo -e "${GREEN}✓ terraform.tfvars created${NC}"
        echo -e "${YELLOW}⚠️  Please review terraform.tfvars and update domain_name if needed${NC}"
    else
        echo -e "${GREEN}✓ terraform.tfvars already exists${NC}"
    fi
    
    echo ""
    
    # Step 5: Initialize and Plan Terraform
    echo -e "${YELLOW}Step 5: Initialize Terraform...${NC}"
    terraform init
    
    echo ""
    echo -e "${YELLOW}Step 6: Terraform Plan (Review changes)...${NC}"
    terraform plan -out=tfplan
    
    echo ""
    echo -e "${YELLOW}⚠️  Review the plan above. Continue with apply? (y/n)${NC}"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        echo -e "${RED}Deployment cancelled${NC}"
        exit 1
    fi
    
    echo ""
    echo -e "${YELLOW}Step 7: Applying Terraform (This will create infrastructure)...${NC}"
    terraform apply tfplan
    
    # Save outputs
    terraform output -json > ../terraform-outputs.json
    echo -e "${GREEN}✓ Infrastructure provisioned${NC}"
    echo -e "${GREEN}✓ Terraform outputs saved to terraform-outputs.json${NC}"
    
    cd ..
    echo ""
}

# Phase 2: Container Registry Setup
phase2_container_registry() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Phase 2: Container Registry Setup${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    
    # Step 6: Setup GitHub Container Registry
    echo -e "${YELLOW}Step 6: Setup GitHub Container Registry${NC}"
    
    if [ -z "$GITHUB_TOKEN" ]; then
        echo -e "${YELLOW}Please enter your GitHub Personal Access Token:${NC}"
        echo -e "${YELLOW}(Create one at: https://github.com/settings/tokens)${NC}"
        read -s GITHUB_TOKEN
        export GITHUB_TOKEN
    fi
    
    if [ -z "$GITHUB_USERNAME" ]; then
        echo -e "${YELLOW}Please enter your GitHub username:${NC}"
        read GITHUB_USERNAME
        export GITHUB_USERNAME
    fi
    
    echo "$GITHUB_TOKEN" | docker login ghcr.io -u "$GITHUB_USERNAME" --password-stdin
    
    if docker info | grep -q "Username"; then
        echo -e "${GREEN}✓ Logged in to GitHub Container Registry${NC}"
    else
        echo -e "${RED}Error: Failed to login to GitHub Container Registry${NC}"
        exit 1
    fi
    
    echo ""
    
    # Step 7: Build & Push Docker Images
    echo -e "${YELLOW}Step 7: Build & Push Docker Images${NC}"
    
    LARAVEL_IMAGE="ghcr.io/${GITHUB_USERNAME}/app038/laravel:latest"
    SVELTE_IMAGE="ghcr.io/${GITHUB_USERNAME}/app038/svelte:latest"
    
    echo -e "${YELLOW}Building Laravel image...${NC}"
    docker buildx build \
        --platform linux/amd64 \
        -f docker/php/Dockerfile \
        -t "${LARAVEL_IMAGE}" \
        --push .
    
    echo -e "${GREEN}✓ Laravel image built and pushed${NC}"
    
    echo -e "${YELLOW}Building Svelte image...${NC}"
    docker buildx build \
        --platform linux/amd64 \
        -f docker/svelte/Dockerfile \
        -t "${SVELTE_IMAGE}" \
        --push .
    
    echo -e "${GREEN}✓ Svelte image built and pushed${NC}"
    
    echo ""
    echo -e "${YELLOW}⚠️  Note: For production, use CI/CD pipeline instead of manual build${NC}"
    echo ""
}

# Phase 3: Kubernetes Deployment
phase3_kubernetes() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Phase 3: Kubernetes Deployment${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    
    # Step 8: Configure kubectl
    echo -e "${YELLOW}Step 8: Configure kubectl${NC}"
    
    EKS_CLUSTER_NAME=$(jq -r '.eks_cluster_name.value' terraform-outputs.json 2>/dev/null || echo "app038-eks-cluster")
    
    aws eks update-kubeconfig \
        --region "${AWS_REGION}" \
        --name "${EKS_CLUSTER_NAME}"
    
    if kubectl cluster-info &> /dev/null; then
        echo -e "${GREEN}✓ kubectl configured${NC}"
        kubectl cluster-info
        kubectl get nodes
    else
        echo -e "${RED}Error: Cannot connect to Kubernetes cluster${NC}"
        exit 1
    fi
    
    echo ""
    
    # Step 9: Create Namespace
    echo -e "${YELLOW}Step 9: Create Kubernetes Namespace${NC}"
    
    if kubectl get namespace "${NAMESPACE}" &> /dev/null; then
        echo -e "${GREEN}✓ Namespace already exists${NC}"
    else
        kubectl create namespace "${NAMESPACE}"
        echo -e "${GREEN}✓ Namespace created${NC}"
    fi
    
    echo ""
    
    # Step 10: Setup Secrets
    echo -e "${YELLOW}Step 10: Setup Kubernetes Secrets${NC}"
    
    # Generate passwords
    DB_PASSWORD=$(openssl rand -base64 32)
    REDIS_PASSWORD=$(openssl rand -base64 32)
    RABBITMQ_PASSWORD=$(openssl rand -base64 32)
    APP_KEY=$(php artisan key:generate --show 2>/dev/null | grep -oP 'base64:\K.*' || echo "base64:$(openssl rand -base64 32)")
    
    # Create secrets
    kubectl create secret generic app038-secrets \
        --from-literal=DB_PASSWORD="${DB_PASSWORD}" \
        --from-literal=REDIS_PASSWORD="${REDIS_PASSWORD}" \
        --from-literal=RABBITMQ_PASSWORD="${RABBITMQ_PASSWORD}" \
        --from-literal=APP_KEY="${APP_KEY}" \
        --namespace="${NAMESPACE}" \
        --dry-run=client -o yaml | kubectl apply -f -
    
    echo -e "${GREEN}✓ Secrets created${NC}"
    echo -e "${YELLOW}⚠️  Save these passwords securely:${NC}"
    echo "DB_PASSWORD: ${DB_PASSWORD}"
    echo "REDIS_PASSWORD: ${REDIS_PASSWORD}"
    echo "RABBITMQ_PASSWORD: ${RABBITMQ_PASSWORD}"
    echo "APP_KEY: ${APP_KEY}"
    
    echo ""
    
    # Step 11: Setup Image Pull Secret
    echo -e "${YELLOW}Step 11: Setup Image Pull Secret${NC}"
    
    if [ -z "$GITHUB_TOKEN" ] || [ -z "$GITHUB_USERNAME" ]; then
        echo -e "${YELLOW}Please enter GitHub credentials for image pull:${NC}"
        if [ -z "$GITHUB_TOKEN" ]; then
            read -s -p "GitHub Token: " GITHUB_TOKEN
            echo ""
        fi
        if [ -z "$GITHUB_USERNAME" ]; then
            read -p "GitHub Username: " GITHUB_USERNAME
        fi
    fi
    
    kubectl create secret docker-registry ghcr-secret \
        --docker-server=ghcr.io \
        --docker-username="${GITHUB_USERNAME}" \
        --docker-password="${GITHUB_TOKEN}" \
        --namespace="${NAMESPACE}" \
        --dry-run=client -o yaml | kubectl apply -f -
    
    echo -e "${GREEN}✓ Image pull secret created${NC}"
    
    echo ""
    
    # Step 12: Install Helm Chart
    echo -e "${YELLOW}Step 12: Install Helm Chart${NC}"
    
    cd helm/app038
    
    # Update dependencies
    helm dependency update
    
    # Install chart
    helm upgrade --install app038 . \
        --namespace "${NAMESPACE}" \
        --set laravel.image.repository="ghcr.io/${GITHUB_USERNAME}/app038/laravel" \
        --set laravel.image.tag=latest \
        --set svelte.image.repository="ghcr.io/${GITHUB_USERNAME}/app038/svelte" \
        --set svelte.image.tag=latest \
        --set ingress.hosts[0].host="${DOMAIN_NAME}" \
        --set secrets.create=false \
        --set secrets.dbPassword="${DB_PASSWORD}" \
        --wait \
        --timeout 10m
    
    echo -e "${GREEN}✓ Helm chart installed${NC}"
    
    cd ../..
    
    echo ""
    
    # Step 13: Verify Deployment
    echo -e "${YELLOW}Step 13: Verify Deployment${NC}"
    
    kubectl get pods -n "${NAMESPACE}"
    kubectl get services -n "${NAMESPACE}"
    kubectl get ingress -n "${NAMESPACE}"
    
    echo ""
    echo -e "${GREEN}✓ Deployment completed!${NC}"
    echo ""
}

# Main execution
main() {
    echo -e "${GREEN}Starting Kubernetes Deployment...${NC}"
    echo ""
    
    # Check if we're in the right directory
    if [ ! -f "composer.json" ] || [ ! -d "terraform" ] || [ ! -d "helm" ]; then
        echo -e "${RED}Error: Please run this script from the project root directory${NC}"
        exit 1
    fi
    
    # Ask for confirmation
    echo -e "${YELLOW}This will deploy App038 to Kubernetes. Continue? (y/n)${NC}"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        echo -e "${RED}Deployment cancelled${NC}"
        exit 1
    fi
    
    # Run phases
    phase1_infrastructure
    phase2_container_registry
    phase3_kubernetes
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Deployment Complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "1. Setup database and run migrations"
    echo "2. Configure SSL/TLS with cert-manager"
    echo "3. Setup DNS records"
    echo "4. Verify application is accessible"
    echo ""
}

# Run main function
main

