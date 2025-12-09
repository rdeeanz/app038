terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.23"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }

  # Uncomment and configure backend for remote state
   backend "s3" {
     bucket         = "terraform-state-bucket"
     key            = "app038/terraform.tfstate"
     region         = "us-west-2"
     encrypt        = true
     dynamodb_table = "terraform-state-lock"
   }
}

# Provider Configuration
provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = var.project_name
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# Networking Module
module "networking" {
  source = "./modules/networking"

  project_name      = var.project_name
  environment       = var.environment
  vpc_cidr          = var.vpc_cidr
  availability_zones = var.availability_zones
  enable_nat_gateway = var.enable_nat_gateway
  enable_flow_log    = var.enable_flow_log
  tags              = var.tags
}

# PostgreSQL Module
module "postgresql" {
  source = "./modules/postgresql"

  project_name         = var.project_name
  environment          = var.environment
  db_name              = var.db_name
  db_username          = var.db_username
  db_password          = var.db_password
  db_instance_class    = var.db_instance_class
  db_subnet_group_id   = module.networking.database_subnet_group_id
  db_security_group_ids = [module.networking.database_security_group_id]
  db_multi_az          = var.db_multi_az
  tags                 = var.tags

  depends_on = [module.networking]
}

# Kubernetes Module
module "kubernetes" {
  source = "./modules/kubernetes"

  project_name              = var.project_name
  environment               = var.environment
  vpc_id                    = module.networking.vpc_id
  subnet_ids                = module.networking.private_subnet_ids
  cluster_security_group_id = module.networking.kubernetes_security_group_id
  cluster_version           = var.kubernetes_version
  node_groups               = var.kubernetes_node_groups
  tags                      = var.tags

  depends_on = [module.networking]
}

# Vault Module
module "vault" {
  source = "./modules/vault"

  project_name         = var.project_name
  environment          = var.environment
  vpc_id               = module.networking.vpc_id
  subnet_ids           = module.networking.private_subnet_ids
  security_group_ids   = []
  vault_storage_backend = var.vault_storage_backend
  vault_enable_auto_unseal = var.vault_enable_auto_unseal
  tags                 = var.tags

  depends_on = [module.networking]
}

