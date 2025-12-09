# Security Guide

Comprehensive security configuration for App038 including Vault, IAM policies, Trivy scanning, and secret management.

## Overview

This guide covers:
- HashiCorp Vault secrets management
- IAM policies for AWS integration
- Trivy security scanning
- GitHub Actions secret scanning
- Laravel Vault integration

## Vault Configuration

### Secrets Engines

1. **KV v2** - Key-value secrets storage
2. **Database** - Dynamic database credentials
3. **Transit** - Encryption as a service
4. **PKI** - Certificate management
5. **AWS** - Dynamic AWS credentials

### Authentication Methods

- **Token** - Static tokens (development)
- **AppRole** - Application authentication
- **Kubernetes** - Service account authentication

### Setup

```bash
# Initialize Vault
vault operator init -key-shares=5 -key-threshold=3

# Unseal Vault
vault operator unseal <key1>
vault operator unseal <key2>
vault operator unseal <key3>

# Run setup script
./vault/scripts/setup-vault.sh
```

## IAM Policies

### Vault IAM Policy

Located in `terraform/modules/vault/iam-policy.tf`, this policy grants Vault access to:

- IAM user/role management
- EC2 instance management
- RDS database access
- Secrets Manager access
- KMS encryption/decryption

### Apply IAM Policy

```bash
cd terraform/modules/vault
terraform apply
```

## Trivy Scanning

### Scan Types

1. **Filesystem** - Scan local files for vulnerabilities
2. **Docker Image** - Scan container images
3. **Kubernetes** - Scan K8s manifests and cluster
4. **Dependencies** - Scan package dependencies

### Manual Scanning

```bash
# Filesystem scan
trivy fs .

# Docker image scan
trivy image app038-laravel:latest

# Kubernetes scan
trivy k8s --report summary cluster

# Dependency scan
trivy fs --scanners vuln,license .
```

### GitHub Actions

Automated scanning runs on:
- Push to main/develop
- Pull requests
- Daily schedule (2 AM UTC)
- Manual workflow dispatch

## Secret Scanning

### Tools

1. **Gitleaks** - Git secret detection
2. **TruffleHog** - Secret scanning
3. **GitHub Advanced Security** - Built-in scanning

### Configuration

Gitleaks configuration: `.gitleaks.toml`

Scans for:
- API keys
- Passwords
- Tokens
- Private keys
- Database credentials
- AWS credentials

### Exclusions

Automatically excludes:
- `node_modules/`
- `vendor/`
- `.env.example`
- Test files
- Documentation

## Laravel Vault Integration

### Installation

Vault integration is included in the application. No additional packages required.

### Configuration

```env
VAULT_ADDR=http://vault:8200
VAULT_AUTH_METHOD=kubernetes
VAULT_K8S_ROLE=laravel-app
VAULT_KV_PATH=secret/data/app038
VAULT_CACHE_ENABLED=true
VAULT_CACHE_TTL=3600
```

### Usage

```php
use App\Services\VaultService;

$vault = app(VaultService::class);

// Get secret
$password = $vault->getSecret('database', 'password');

// Store secret
$vault->putSecret('api-keys', [
    'stripe' => 'sk_live_...',
]);

// Get database credentials
$dbCreds = $vault->getDatabaseCredentials();

// Encrypt/Decrypt
$encrypted = $vault->encrypt('sensitive data');
$decrypted = $vault->decrypt($encrypted);
```

### Automatic Database Credentials

Database credentials are automatically loaded from Vault when `vault.database.enabled` is true.

## Security Best Practices

### Secrets Management

1. **Never commit secrets** to version control
2. **Use Vault** for all sensitive data
3. **Rotate secrets regularly**
4. **Use dynamic credentials** when possible
5. **Enable audit logging**

### Code Security

1. **Scan dependencies** regularly
2. **Update packages** promptly
3. **Review security advisories**
4. **Use least privilege** principles
5. **Enable secret scanning** in CI/CD

### Infrastructure Security

1. **Use IAM roles** instead of access keys
2. **Enable encryption** at rest and in transit
3. **Implement network policies**
4. **Regular security audits**
5. **Monitor access logs**

## GitHub Actions Security

### Workflows

1. **security-scan.yml** - Comprehensive security scanning
   - Secret scanning (Gitleaks, TruffleHog)
   - Trivy filesystem scan
   - Trivy Docker image scan
   - Trivy Kubernetes scan
   - Dependency scanning

### Results

Scan results are:
- Uploaded to GitHub Security tab
- Saved as artifacts
- Included in PR checks
- Summarized in workflow summary

## Monitoring and Alerts

### Vault Monitoring

- Monitor Vault health
- Track secret access
- Alert on failed authentications
- Monitor token expiration

### Security Alerts

- Failed secret scans
- Critical vulnerabilities
- Exposed secrets
- Unauthorized access attempts

## Incident Response

### If Secret is Exposed

1. **Immediately rotate** the exposed secret
2. **Revoke access** if compromised
3. **Review audit logs** for unauthorized access
4. **Notify security team**
5. **Update documentation**

### If Vulnerability Found

1. **Assess severity** using CVSS scores
2. **Check for patches** or updates
3. **Apply fixes** immediately for critical issues
4. **Test thoroughly** before deployment
5. **Monitor for exploitation**

## Compliance

### Standards

- **OWASP Top 10** - Web application security
- **CIS Benchmarks** - Security configuration
- **NIST Framework** - Security controls
- **SOC 2** - Security and availability

### Auditing

- All secret access is logged
- Security scans are documented
- Policy changes are tracked
- Access reviews are conducted regularly

## Resources

- [Vault Documentation](https://developer.hashicorp.com/vault/docs)
- [Trivy Documentation](https://aquasecurity.github.io/trivy/)
- [Gitleaks Documentation](https://github.com/gitleaks/gitleaks)
- [GitHub Security](https://docs.github.com/en/code-security)

