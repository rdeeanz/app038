# Networking Outputs
output "vpc_id" {
  description = "ID of the VPC"
  value       = module.networking.vpc_id
}

output "public_subnet_ids" {
  description = "IDs of the public subnets"
  value       = module.networking.public_subnet_ids
}

output "private_subnet_ids" {
  description = "IDs of the private subnets"
  value       = module.networking.private_subnet_ids
}

# Database Outputs
output "database_endpoint" {
  description = "RDS instance endpoint"
  value       = module.postgresql.db_instance_endpoint
  sensitive   = false
}

output "database_address" {
  description = "RDS instance address"
  value       = module.postgresql.db_instance_address
}

output "database_port" {
  description = "RDS instance port"
  value       = module.postgresql.db_instance_port
}

# Kubernetes Outputs
output "kubernetes_cluster_id" {
  description = "EKS cluster ID"
  value       = module.kubernetes.cluster_id
}

output "kubernetes_cluster_endpoint" {
  description = "Endpoint for EKS control plane"
  value       = module.kubernetes.cluster_endpoint
}

output "kubernetes_cluster_name" {
  description = "Name of the EKS cluster"
  value       = module.kubernetes.cluster_name
}

# Vault Outputs
output "vault_alb_dns_name" {
  description = "DNS name of the Vault ALB"
  value       = module.vault.vault_alb_dns_name
}

output "vault_kms_key_id" {
  description = "KMS key ID for Vault auto-unseal"
  value       = module.vault.vault_kms_key_id
}

