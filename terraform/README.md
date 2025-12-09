# Terraform Infrastructure Modules

This directory contains Terraform modules for provisioning infrastructure on AWS.

## Modules

### 1. Networking Module (`modules/networking/`)

Creates VPC, subnets, NAT gateways, security groups, and networking components.

**Features:**
- VPC with configurable CIDR
- Public, private, and database subnets across multiple AZs
- Internet Gateway and NAT Gateways
- Security groups for web, database, and Kubernetes
- VPC Flow Logs support
- Database subnet group

**Usage:**
```hcl
module "networking" {
  source = "./modules/networking"

  project_name      = "app038"
  environment       = "prod"
  vpc_cidr          = "10.0.0.0/16"
  availability_zones = ["us-west-2a", "us-west-2b", "us-west-2c"]
  enable_nat_gateway = true
}
```

### 2. PostgreSQL Module (`modules/postgresql/`)

Creates RDS PostgreSQL instance with backups, monitoring, and security.

**Features:**
- PostgreSQL RDS instance
- Multi-AZ support
- Automated backups
- Performance Insights
- CloudWatch alarms
- Encryption at rest
- Parameter and option groups

**Usage:**
```hcl
module "postgresql" {
  source = "./modules/postgresql"

  project_name         = "app038"
  environment          = "prod"
  db_name              = "app038"
  db_username          = "postgres"
  db_password          = var.db_password
  db_instance_class    = "db.t3.medium"
  db_subnet_group_id   = module.networking.database_subnet_group_id
  db_security_group_ids = [module.networking.database_security_group_id]
  db_multi_az          = true
}
```

### 3. Kubernetes Module (`modules/kubernetes/`)

Creates EKS cluster with node groups, Fargate profiles, and addons.

**Features:**
- EKS cluster with configurable version
- Managed node groups
- Fargate profiles
- EKS addons (VPC CNI, kube-proxy, CoreDNS, EBS CSI)
- IRSA (IAM Roles for Service Accounts)
- Encryption at rest
- CloudWatch logging
- AWS Auth ConfigMap

**Usage:**
```hcl
module "kubernetes" {
  source = "./modules/kubernetes"

  project_name = "app038"
  environment  = "prod"
  vpc_id       = module.networking.vpc_id
  subnet_ids   = module.networking.private_subnet_ids

  node_groups = {
    main = {
      instance_types = ["t3.medium"]
      capacity_type  = "ON_DEMAND"
      min_size       = 2
      max_size       = 5
      desired_size   = 3
      disk_size      = 20
      ami_type       = "AL2_x86_64"
      labels         = {}
      taints         = []
    }
  }
}
```

### 4. Vault Module (`modules/vault/`)

Creates HashiCorp Vault cluster on AWS with auto-unseal and storage backends.

**Features:**
- Vault cluster with Auto Scaling Group
- Auto-unseal using AWS KMS
- Storage backends: S3, DynamoDB, or Consul
- Application Load Balancer
- CloudWatch Logs integration
- IAM roles and policies

**Usage:**
```hcl
module "vault" {
  source = "./modules/vault"

  project_name         = "app038"
  environment          = "prod"
  vpc_id               = module.networking.vpc_id
  subnet_ids           = module.networking.private_subnet_ids
  vault_storage_backend = "s3"
  vault_enable_auto_unseal = true
  vault_instance_count = 3
}
```

## Root Module

The root module (`main.tf`) orchestrates all modules:

```hcl
module "networking" { ... }
module "postgresql" { ... }
module "kubernetes" { ... }
module "vault" { ... }
```

## Usage

### Initialize Terraform

```bash
cd terraform
terraform init
```

### Plan

```bash
terraform plan -var-file=terraform.tfvars
```

### Apply

```bash
terraform apply -var-file=terraform.tfvars
```

### Destroy

```bash
terraform destroy -var-file=terraform.tfvars
```

## Variables

Create a `terraform.tfvars` file:

```hcl
project_name = "app038"
environment  = "prod"
aws_region  = "us-west-2"

vpc_cidr = "10.0.0.0/16"
availability_zones = ["us-west-2a", "us-west-2b", "us-west-2c"]

db_name         = "app038"
db_username     = "postgres"
db_password     = "your-secure-password"
db_instance_class = "db.t3.medium"
db_multi_az     = true

kubernetes_version = "1.28"

vault_storage_backend = "s3"
vault_enable_auto_unseal = true
```

## Outputs

After applying, you can access outputs:

```bash
terraform output
```

Key outputs:
- `vpc_id` - VPC ID
- `database_endpoint` - RDS endpoint
- `kubernetes_cluster_endpoint` - EKS API endpoint
- `vault_alb_dns_name` - Vault ALB DNS name

## Backend Configuration

For remote state, uncomment and configure the backend in `main.tf`:

```hcl
backend "s3" {
  bucket         = "terraform-state-bucket"
  key            = "app038/terraform.tfstate"
  region         = "us-west-2"
  encrypt        = true
  dynamodb_table = "terraform-state-lock"
}
```

## Best Practices

1. **State Management**: Use remote state (S3 + DynamoDB)
2. **Secrets**: Store sensitive variables in AWS Secrets Manager or use `-var` flags
3. **Versioning**: Pin provider versions
4. **Tagging**: All resources are tagged with project and environment
5. **Modularity**: Each module is self-contained and reusable
6. **Documentation**: Each module includes comprehensive variable and output documentation

## Requirements

- Terraform >= 1.0
- AWS Provider >= 5.0
- Kubernetes Provider >= 2.23
- AWS CLI configured with appropriate credentials

