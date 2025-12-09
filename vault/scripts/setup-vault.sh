#!/bin/bash
# Vault Setup Script for App038
# This script initializes Vault and configures secrets engines

set -e

VAULT_ADDR="${VAULT_ADDR:-http://localhost:8200}"
VAULT_TOKEN="${VAULT_TOKEN:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up Vault for App038...${NC}"

# Check if Vault is accessible
if ! vault status > /dev/null 2>&1; then
    echo -e "${RED}Error: Cannot connect to Vault at ${VAULT_ADDR}${NC}"
    exit 1
fi

# Enable KV v2 secrets engine
echo -e "${YELLOW}Enabling KV v2 secrets engine...${NC}"
vault secrets enable -version=2 -path=secret kv || echo "KV engine already enabled"

# Enable Database secrets engine
echo -e "${YELLOW}Enabling Database secrets engine...${NC}"
vault secrets enable -path=database database || echo "Database engine already enabled"

# Enable Transit secrets engine
echo -e "${YELLOW}Enabling Transit secrets engine...${NC}"
vault secrets enable -path=transit transit || echo "Transit engine already enabled"

# Enable PKI secrets engine
echo -e "${YELLOW}Enabling PKI secrets engine...${NC}"
vault secrets enable -path=pki pki || echo "PKI engine already enabled"

# Enable AWS secrets engine
echo -e "${YELLOW}Enabling AWS secrets engine...${NC}"
vault secrets enable -path=aws aws || echo "AWS engine already enabled"

# Create encryption key in Transit
echo -e "${YELLOW}Creating encryption key...${NC}"
vault write -f transit/keys/app038-key || echo "Key already exists"

# Configure Database connection (PostgreSQL example)
echo -e "${YELLOW}Configuring database connection...${NC}"
vault write database/config/app038-postgres \
    plugin_name=postgresql-database-plugin \
    connection_url="postgresql://{{username}}:{{password}}@postgres:5432/app038?sslmode=disable" \
    allowed_roles="app038-readonly,app038-readwrite" \
    username="vault" \
    password="${DB_VAULT_PASSWORD:-changeme}" || echo "Database config may already exist"

# Create database roles
echo -e "${YELLOW}Creating database roles...${NC}"
vault write database/roles/app038-readonly \
    db_name=app038-postgres \
    creation_statements="CREATE ROLE \"{{name}}\" WITH LOGIN PASSWORD '{{password}}' VALID UNTIL '{{expiration}}'; GRANT SELECT ON ALL TABLES IN SCHEMA public TO \"{{name}}\";" \
    default_ttl="1h" \
    max_ttl="24h" || echo "Role already exists"

vault write database/roles/app038-readwrite \
    db_name=app038-postgres \
    creation_statements="CREATE ROLE \"{{name}}\" WITH LOGIN PASSWORD '{{password}}' VALID UNTIL '{{expiration}}'; GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO \"{{name}}\";" \
    default_ttl="1h" \
    max_ttl="24h" || echo "Role already exists"

# Create policies
echo -e "${YELLOW}Creating policies...${NC}"
vault policy write app038-policy vault/policies/app038-policy.hcl
vault policy write readonly-policy vault/policies/readonly-policy.hcl
vault policy write admin-policy vault/policies/admin-policy.hcl

# Enable Kubernetes auth method
echo -e "${YELLOW}Enabling Kubernetes authentication...${NC}"
vault auth enable kubernetes || echo "Kubernetes auth already enabled"

# Configure Kubernetes auth (requires k8s service account token)
if [ -f "/var/run/secrets/kubernetes.io/serviceaccount/token" ]; then
    K8S_HOST="https://kubernetes.default.svc.cluster.local"
    K8S_CA_CERT=$(cat /var/run/secrets/kubernetes.io/serviceaccount/ca.crt | base64 | tr -d '\n')
    SA_TOKEN=$(cat /var/run/secrets/kubernetes.io/serviceaccount/token)
    
    vault write auth/kubernetes/config \
        token_reviewer_jwt="${SA_TOKEN}" \
        kubernetes_host="${K8S_HOST}" \
        kubernetes_ca_cert="${K8S_CA_CERT}" || echo "Kubernetes auth config may already exist"
fi

# Create Kubernetes role for Laravel app
echo -e "${YELLOW}Creating Kubernetes role for Laravel...${NC}"
vault write auth/kubernetes/role/laravel-app \
    bound_service_account_names=laravel-app \
    bound_service_account_namespaces=default \
    policies=app038-policy \
    ttl=1h || echo "Kubernetes role may already exist"

# Enable AppRole auth method
echo -e "${YELLOW}Enabling AppRole authentication...${NC}"
vault auth enable approle || echo "AppRole auth already enabled"

# Create AppRole for Laravel
echo -e "${YELLOW}Creating AppRole for Laravel...${NC}"
vault write auth/approle/role/laravel-app \
    token_policies="app038-policy" \
    token_ttl=1h \
    token_max_ttl=4h \
    bind_secret_id=true || echo "AppRole may already exist"

# Get AppRole role-id and secret-id
ROLE_ID=$(vault read -format=json auth/approle/role/laravel-app/role-id | jq -r '.data.role_id')
SECRET_ID=$(vault write -f -format=json auth/approle/role/laravel-app/secret-id | jq -r '.data.secret_id')

echo -e "${GREEN}Vault setup complete!${NC}"
echo -e "${YELLOW}AppRole Role ID: ${ROLE_ID}${NC}"
echo -e "${YELLOW}AppRole Secret ID: ${SECRET_ID}${NC}"
echo -e "${YELLOW}Store these securely!${NC}"

