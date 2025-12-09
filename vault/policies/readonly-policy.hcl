# Read-only Policy - Read access to application secrets
# For monitoring and auditing purposes

# Read-only access to KV secrets
path "secret/data/app038/*" {
  capabilities = ["read", "list"]
}

# Read-only access to shared secrets
path "secret/data/shared/*" {
  capabilities = ["read", "list"]
}

# Read own token information
path "auth/token/lookup-self" {
  capabilities = ["read"]
}

# Renew own token
path "auth/token/renew-self" {
  capabilities = ["update"]
}

