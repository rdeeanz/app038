# Vault Configuration for App038
# This file configures Vault secrets engines and authentication methods

# Storage backend (example: Consul)
storage "consul" {
  address = "127.0.0.1:8500"
  path    = "vault/"
  scheme  = "http"
}

# Alternative: File storage (for development)
# storage "file" {
#   path = "/vault/data"
# }

# Alternative: S3 storage (for production)
# storage "s3" {
#   access_key = "AWS_ACCESS_KEY_ID"
#   secret_key = "AWS_SECRET_ACCESS_KEY"
#   bucket     = "vault-backend"
#   region     = "us-east-1"
# }

# API listener
listener "tcp" {
  address         = "0.0.0.0:8200"
  tls_cert_file   = "/vault/tls/vault.crt"
  tls_key_file    = "/vault/tls/vault.key"
  tls_min_version = "tls12"
}

# UI
ui = true

# Log level
log_level = "INFO"

# Cluster name
cluster_name = "app038-vault"

# Enable audit logging
# audit_device {
#   path = "file"
#   options = {
#     file_path = "/vault/audit/vault_audit.log"
#   }
# }

