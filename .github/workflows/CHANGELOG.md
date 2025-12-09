# CI/CD Workflow Changelog

## Version 1.0.0

### Features

- **Matrix Strategy Testing**
  - PHP versions: 8.2, 8.3
  - Node versions: 20, 22
  - Dependency version testing (prefer-stable, prefer-lowest)

- **Docker Build & Push**
  - Multi-platform builds (amd64, arm64)
  - BuildKit caching
  - Automatic tagging (branch, SHA, semver, latest)

- **Helm Deployment**
  - Atomic deployments (`--atomic` flag)
  - Automatic secret management
  - Health check verification
  - Post-deployment testing

- **Automatic Rollback**
  - Triggers on deployment failure
  - Rolls back to previous Helm revision
  - Notification support

- **Security Scanning**
  - Dependency vulnerability scanning
  - Docker image scanning
  - Weekly scheduled scans

### Workflows

1. **ci-cd.yml** - Main CI/CD pipeline
2. **pr-checks.yml** - Lightweight PR checks
3. **security-scan.yml** - Security vulnerability scanning
4. **manual-rollback.yml** - Manual rollback workflow
5. **ci-cd-quick.yml** - Quick tests for PRs

### Required Secrets

- `KUBECONFIG` - Kubernetes cluster configuration
- `DB_PASSWORD` - Database password
- `REDIS_PASSWORD` - Redis password
- `RABBITMQ_PASSWORD` - RabbitMQ password
- `APP_KEY` - Laravel application key

### Optional Secrets

- `SNYK_TOKEN` - Snyk API token
- `SLACK_WEBHOOK_URL` - Slack notifications
- `CODECOV_TOKEN` - Code coverage reporting

