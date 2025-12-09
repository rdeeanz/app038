# Vault Configuration for App038

This directory contains HashiCorp Vault configuration files for secure secret management.

## Structure

```
vault/
├── config/
│   └── vault-config.hcl          # Vault server configuration
├── policies/
│   ├── app038-policy.hcl         # Application policy
│   ├── admin-policy.hcl          # Admin policy (full access)
│   └── readonly-policy.hcl       # Read-only policy
└── scripts/
    └── setup-vault.sh            # Vault initialization script
```

## Policies

### App038 Policy

Grants access to application secrets:
- Read/Write access to `secret/data/app038/*`
- Read-only access to `secret/data/shared/*`
- Dynamic database credentials
- Transit encryption/decryption
- PKI certificate generation
- AWS dynamic credentials

### Admin Policy

Full access to all secrets engines (use with caution).

### Read-only Policy

Read-only access for monitoring and auditing.

## Setup

### 1. Initialize Vault

```bash
# Set Vault address
export VAULT_ADDR="http://localhost:8200"

# Initialize Vault (first time only)
vault operator init -key-shares=5 -key-threshold=3

# Unseal Vault (requires 3 of 5 keys)
vault operator unseal <key1>
vault operator unseal <key2>
vault operator unseal <key3>

# Save root token securely
export VAULT_TOKEN="<root-token>"
```

### 2. Run Setup Script

```bash
chmod +x vault/scripts/setup-vault.sh
./vault/scripts/setup-vault.sh
```

The script will:
- Enable secrets engines (KV, Database, Transit, PKI, AWS)
- Create encryption keys
- Configure database connections
- Create policies
- Enable authentication methods (Kubernetes, AppRole)
- Create roles

### 3. Apply Policies

```bash
vault policy write app038-policy vault/policies/app038-policy.hcl
vault policy write readonly-policy vault/policies/readonly-policy.hcl
vault policy write admin-policy vault/policies/admin-policy.hcl
```

## Authentication Methods

### Token Authentication

```bash
export VAULT_TOKEN="<token>"
vault auth -method=token
```

### AppRole Authentication

```bash
# Get role-id
ROLE_ID=$(vault read -format=json auth/approle/role/laravel-app/role-id | jq -r '.data.role_id')

# Get secret-id
SECRET_ID=$(vault write -f -format=json auth/approle/role/laravel-app/secret-id | jq -r '.data.secret_id')

# Authenticate
vault write auth/approle/login role_id=$ROLE_ID secret_id=$SECRET_ID
```

### Kubernetes Authentication

Automatically authenticates using Kubernetes service account token when running in Kubernetes.

## Secrets Management

### Store Secret

```bash
vault kv put secret/data/app038/database \
  username=app038_user \
  password=secure_password
```

### Read Secret

```bash
vault kv get secret/data/app038/database
```

### Get Database Credentials

```bash
vault read database/creds/app038-readwrite
```

### Encrypt Data

```bash
vault write transit/encrypt/app038-key plaintext=$(base64 <<< "sensitive data")
```

### Decrypt Data

```bash
vault write transit/decrypt/app038-key ciphertext="vault:v1:..."
```

## Laravel Integration

### Environment Variables

```env
VAULT_ADDR=http://vault:8200
VAULT_AUTH_METHOD=kubernetes
VAULT_K8S_ROLE=laravel-app
VAULT_KV_PATH=secret/data/app038
VAULT_CACHE_ENABLED=true
VAULT_CACHE_TTL=3600
```

### Usage in Laravel

```php
use App\Services\VaultService;

$vault = app(VaultService::class);

// Get secret
$dbPassword = $vault->getSecret('database', 'password');

// Get all secrets
$secrets = $vault->getSecret('database');

// Store secret
$vault->putSecret('api-keys', [
    'stripe' => 'sk_live_...',
    'sap' => 'api_key_...',
]);

// Get database credentials
$dbCreds = $vault->getDatabaseCredentials('app038-readwrite');

// Encrypt data
$encrypted = $vault->encrypt('sensitive data');

// Decrypt data
$decrypted = $vault->decrypt($encrypted);
```

## Kubernetes Integration

### Service Account

```yaml
apiVersion: v1
kind: ServiceAccount
metadata:
  name: laravel-app
  namespace: default
```

### Vault Role

```bash
vault write auth/kubernetes/role/laravel-app \
  bound_service_account_names=laravel-app \
  bound_service_account_namespaces=default \
  policies=app038-policy \
  ttl=1h
```

## Security Best Practices

1. **Never commit tokens or secrets** to version control
2. **Use least privilege** - assign minimal required permissions
3. **Rotate secrets regularly** - use dynamic credentials when possible
4. **Enable audit logging** - track all secret access
5. **Use TLS** - always use HTTPS for Vault communication
6. **Enable MFA** - for admin access
7. **Regular backups** - backup Vault data and unseal keys
8. **Monitor access** - set up alerts for unusual access patterns

## Troubleshooting

### Cannot connect to Vault

```bash
# Check Vault status
vault status

# Verify address
echo $VAULT_ADDR

# Check network connectivity
curl -k $VAULT_ADDR/v1/sys/health
```

### Authentication failed

```bash
# Check token validity
vault token lookup

# Renew token
vault token renew

# Verify policy
vault policy read app038-policy
```

### Permission denied

```bash
# Check token capabilities
vault token capabilities secret/data/app038/database

# Verify policy assignment
vault token lookup -format=json | jq '.data.policies'
```

## Backup and Recovery

### Backup Vault

```bash
# Backup Vault data
vault operator raft snapshot save vault-backup.snap

# Backup policies
vault policy list > policies-backup.txt
for policy in $(vault policy list); do
  vault policy read $policy >> policies-backup.txt
done
```

### Restore Vault

```bash
# Restore from snapshot
vault operator raft snapshot restore vault-backup.snap
```

## Production Checklist

- [ ] Enable TLS/SSL
- [ ] Configure audit logging
- [ ] Set up high availability
- [ ] Configure backup strategy
- [ ] Enable MFA for admin
- [ ] Review and test policies
- [ ] Set up monitoring and alerts
- [ ] Document secret locations
- [ ] Train team on Vault usage
- [ ] Test disaster recovery procedures

