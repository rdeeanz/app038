output "vault_cluster_name" {
  description = "Name of the Vault cluster"
  value       = local.vault_cluster_name
}

output "vault_security_group_id" {
  description = "Security group ID for Vault"
  value       = aws_security_group.vault.id
}

output "vault_iam_role_arn" {
  description = "IAM role ARN for Vault"
  value       = aws_iam_role.vault.arn
}

output "vault_iam_role_name" {
  description = "IAM role name for Vault"
  value       = aws_iam_role.vault.name
}

output "vault_alb_dns_name" {
  description = "DNS name of the Vault ALB"
  value       = var.vault_alb_enabled ? aws_lb.vault[0].dns_name : null
}

output "vault_alb_arn" {
  description = "ARN of the Vault ALB"
  value       = var.vault_alb_enabled ? aws_lb.vault[0].arn : null
}

output "vault_target_group_arn" {
  description = "ARN of the Vault target group"
  value       = var.vault_alb_enabled ? aws_lb_target_group.vault[0].arn : null
}

output "vault_kms_key_id" {
  description = "KMS key ID for Vault auto-unseal"
  value       = var.vault_enable_auto_unseal && var.vault_kms_key_id == "" ? aws_kms_key.vault[0].id : var.vault_kms_key_id
}

output "vault_kms_key_arn" {
  description = "KMS key ARN for Vault auto-unseal"
  value       = var.vault_enable_auto_unseal && var.vault_kms_key_id == "" ? aws_kms_key.vault[0].arn : null
}

output "vault_s3_bucket_id" {
  description = "S3 bucket ID for Vault storage (if using S3 backend)"
  value       = var.vault_storage_backend == "s3" && var.vault_s3_bucket == "" ? aws_s3_bucket.vault[0].id : var.vault_s3_bucket
}

output "vault_dynamodb_table_id" {
  description = "DynamoDB table ID for Vault storage (if using DynamoDB backend)"
  value       = var.vault_storage_backend == "dynamodb" && var.vault_dynamodb_table == "" ? aws_dynamodb_table.vault[0].id : var.vault_dynamodb_table
}

output "vault_autoscaling_group_name" {
  description = "Name of the Auto Scaling Group for Vault"
  value       = aws_autoscaling_group.vault.name
}

output "vault_cloudwatch_log_group" {
  description = "CloudWatch Log Group name for Vault"
  value       = var.vault_cloudwatch_logs_enabled ? aws_cloudwatch_log_group.vault[0].name : null
}

