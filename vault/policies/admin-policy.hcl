# Admin Policy - Full access to all secrets engines
# WARNING: Use with extreme caution, only for administrators

# Full access to KV secrets
path "secret/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# Full access to database secrets
path "database/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# Full access to transit secrets
path "transit/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# Full access to PKI
path "pki/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# Full access to AWS secrets
path "aws/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# System paths
path "sys/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

# Auth methods
path "auth/*" {
  capabilities = ["create", "read", "update", "delete", "list", "sudo"]
}

