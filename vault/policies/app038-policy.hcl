# App038 Application Policy
# Grants access to application secrets

# KV Secrets Engine - Read/Write access to app secrets
path "secret/data/app038/*" {
  capabilities = ["create", "read", "update", "delete", "list"]
}

# KV Secrets Engine - Read-only access to shared secrets
path "secret/data/shared/*" {
  capabilities = ["read", "list"]
}

# Database Secrets Engine - Dynamic credentials
path "database/creds/app038-*" {
  capabilities = ["read"]
}

# Transit Secrets Engine - Encryption/Decryption
path "transit/encrypt/app038-key" {
  capabilities = ["update"]
}

path "transit/decrypt/app038-key" {
  capabilities = ["update"]
}

# PKI Secrets Engine - Certificate generation
path "pki/issue/app038-role" {
  capabilities = ["create", "update"]
}

# AWS Secrets Engine - Dynamic AWS credentials
path "aws/creds/app038-role" {
  capabilities = ["read"]
}

# Read own token information
path "auth/token/lookup-self" {
  capabilities = ["read"]
}

# Renew own token
path "auth/token/renew-self" {
  capabilities = ["update"]
}

# Revoke own token
path "auth/token/revoke-self" {
  capabilities = ["update"]
}

