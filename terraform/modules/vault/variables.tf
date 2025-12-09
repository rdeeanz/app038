variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
}

variable "vault_cluster_name" {
  description = "Name of the Vault cluster"
  type        = string
  default     = ""
}

variable "vpc_id" {
  description = "ID of the VPC"
  type        = string
}

variable "subnet_ids" {
  description = "List of subnet IDs for Vault"
  type        = list(string)
}

variable "security_group_ids" {
  description = "List of security group IDs for Vault"
  type        = list(string)
  default     = []
}

variable "vault_instance_type" {
  description = "EC2 instance type for Vault"
  type        = string
  default     = "t3.medium"
}

variable "vault_instance_count" {
  description = "Number of Vault instances"
  type        = number
  default     = 3
  validation {
    condition     = var.vault_instance_count >= 1 && var.vault_instance_count % 2 == 1
    error_message = "Vault instance count must be an odd number (1, 3, 5, etc.) for HA."
  }
}

variable "vault_version" {
  description = "Vault version"
  type        = string
  default     = "1.15.0"
}

variable "vault_storage_backend" {
  description = "Storage backend for Vault (s3, dynamodb, consul)"
  type        = string
  default     = "s3"
  validation {
    condition     = contains(["s3", "dynamodb", "consul"], var.vault_storage_backend)
    error_message = "Storage backend must be s3, dynamodb, or consul."
  }
}

variable "vault_s3_bucket" {
  description = "S3 bucket name for Vault storage (required if storage_backend is s3)"
  type        = string
  default     = ""
}

variable "vault_dynamodb_table" {
  description = "DynamoDB table name for Vault storage (required if storage_backend is dynamodb)"
  type        = string
  default     = ""
}

variable "vault_consul_address" {
  description = "Consul address for Vault storage (required if storage_backend is consul)"
  type        = string
  default     = ""
}

variable "vault_kms_key_id" {
  description = "KMS key ID for Vault auto-unseal"
  type        = string
  default     = ""
}

variable "vault_enable_auto_unseal" {
  description = "Enable Vault auto-unseal using KMS"
  type        = bool
  default     = true
}

variable "vault_enable_ui" {
  description = "Enable Vault UI"
  type        = bool
  default     = true
}

variable "vault_api_addr" {
  description = "Vault API address"
  type        = string
  default     = ""
}

variable "vault_cluster_addr" {
  description = "Vault cluster address"
  type        = string
  default     = ""
}

variable "vault_log_level" {
  description = "Vault log level"
  type        = string
  default     = "info"
  validation {
    condition     = contains(["trace", "debug", "info", "warn", "error"], var.vault_log_level)
    error_message = "Log level must be trace, debug, info, warn, or error."
  }
}

variable "vault_license" {
  description = "Vault license (for Enterprise)"
  type        = string
  default     = ""
  sensitive   = true
}

variable "vault_ami_id" {
  description = "AMI ID for Vault instances (optional, will use latest if not specified)"
  type        = string
  default     = ""
}

variable "vault_key_name" {
  description = "EC2 Key Pair name for SSH access"
  type        = string
  default     = ""
}

variable "vault_user_data" {
  description = "User data script for Vault instances"
  type        = string
  default     = ""
}

variable "vault_alb_enabled" {
  description = "Enable Application Load Balancer for Vault"
  type        = bool
  default     = true
}

variable "vault_alb_internal" {
  description = "Make ALB internal (not publicly accessible)"
  type        = bool
  default     = true
}

variable "vault_alb_certificate_arn" {
  description = "ACM certificate ARN for ALB HTTPS listener"
  type        = string
  default     = ""
}

variable "vault_allowed_cidr_blocks" {
  description = "CIDR blocks allowed to access Vault"
  type        = list(string)
  default     = []
}

variable "vault_cloudwatch_logs_enabled" {
  description = "Enable CloudWatch Logs for Vault"
  type        = bool
  default     = true
}

variable "vault_cloudwatch_log_group_name" {
  description = "CloudWatch Log Group name for Vault"
  type        = string
  default     = ""
}

variable "tags" {
  description = "Additional tags to apply to resources"
  type        = map(string)
  default     = {}
}

