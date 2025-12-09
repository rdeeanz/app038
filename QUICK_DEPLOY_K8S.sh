#!/bin/bash

# Quick Kubernetes Deployment Script
# Simplified version for quick deployment

set -e

echo "🚀 App038 Kubernetes Deployment"
echo "================================"
echo ""

# Check prerequisites
echo "📋 Checking prerequisites..."

# Check AWS CLI
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI not found. Installing..."
    brew install awscli
fi

# Check Terraform
if ! command -v terraform &> /dev/null; then
    echo "❌ Terraform not found. Installing..."
    brew install terraform
fi

# Check Helm
if ! command -v helm &> /dev/null; then
    echo "❌ Helm not found. Installing..."
    brew install helm
fi

# Check kubectl
if ! command -v kubectl &> /dev/null; then
    echo "❌ kubectl not found. Please install kubectl."
    exit 1
fi

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not found. Please install Docker Desktop."
    exit 1
fi

echo "✅ All tools are installed"
echo ""

# Check AWS credentials
echo "🔐 Checking AWS credentials..."
if [ ! -f ~/.aws/credentials ]; then
    echo "⚠️  AWS credentials not configured."
    echo "Please run: aws configure"
    echo ""
    echo "You will need:"
    echo "  - AWS Access Key ID"
    echo "  - AWS Secret Access Key"
    echo "  - Default region: us-west-2"
    echo "  - Default output: json"
    exit 1
fi

if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS credentials are invalid. Please run: aws configure"
    exit 1
fi

echo "✅ AWS credentials configured"
aws sts get-caller-identity
echo ""

# Check GitHub credentials
echo "🐙 Checking GitHub credentials..."
if [ -z "$GITHUB_TOKEN" ] || [ -z "$GITHUB_USERNAME" ]; then
    echo "⚠️  GitHub credentials not set."
    echo "Please set environment variables:"
    echo "  export GITHUB_TOKEN='your_github_token'"
    echo "  export GITHUB_USERNAME='your_github_username'"
    echo ""
    echo "To create GitHub token:"
    echo "  1. Go to: https://github.com/settings/tokens"
    echo "  2. Generate new token (classic)"
    echo "  3. Select scopes: write:packages, read:packages"
    exit 1
fi

echo "✅ GitHub credentials configured"
echo ""

# Ask for domain
if [ -z "$DOMAIN_NAME" ]; then
    echo "🌐 Please enter your domain name (e.g., app038.yourdomain.com):"
    read -r DOMAIN_NAME
    export DOMAIN_NAME
fi

echo ""
echo "📝 Deployment Configuration:"
echo "  - Project: app038"
echo "  - Environment: production"
echo "  - AWS Region: us-west-2"
echo "  - Domain: $DOMAIN_NAME"
echo "  - GitHub: $GITHUB_USERNAME"
echo ""

echo "⚠️  WARNING: This will create AWS resources that will incur costs!"
echo "Estimated monthly cost: ~$120-250"
echo ""
read -p "Continue? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

echo ""
echo "🚀 Starting deployment..."
echo ""

# Run main deployment script
./scripts/deploy-k8s.sh

