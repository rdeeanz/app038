# GitHub Actions Workflows

This directory contains CI/CD workflows for the App038 project.

## Workflows

### 1. CI/CD Pipeline (`ci-cd.yml`)

Main workflow for building, testing, and deploying the application.

**Triggers:**
- Push to `main` or `develop` branches
- Push tags starting with `v*`
- Pull requests to `main` or `develop`

**Jobs:**

1. **test-php** - PHP/Laravel tests with matrix strategy
   - PHP versions: 8.2, 8.3
   - Dependency versions: prefer-stable, prefer-lowest
   - Services: PostgreSQL, Redis
   - Runs: PHPUnit tests, Laravel Pint, PHPStan

2. **test-node** - Node.js/Svelte tests with matrix strategy
   - Node versions: 20, 22
   - Runs: ESLint, build verification

3. **build-and-push** - Docker image build and push
   - Builds Laravel and Svelte images
   - Pushes to GitHub Container Registry
   - Multi-platform support (amd64, arm64)
   - Uses BuildKit cache

4. **deploy** - Helm deployment to Kubernetes
   - Deploys to production namespace
   - Creates/updates secrets
   - Waits for rollout
   - Runs post-deployment health checks

5. **rollback** - Automatic rollback on failure
   - Triggers if deployment fails
   - Rolls back to previous Helm revision
   - Sends notifications

6. **cleanup** - Cleanup old images
   - Removes old container images
   - Keeps last 10 versions

### 2. PR Checks (`pr-checks.yml`)

Lightweight checks for pull requests.

**Jobs:**
- Quick PHP tests
- Quick Node tests
- Lint and format checks

### 3. Security Scan (`security-scan.yml`)

Security vulnerability scanning.

**Jobs:**
- Dependency vulnerability scanning (Trivy, Snyk)
- Docker image scanning
- Weekly scheduled scans

## Required Secrets

Configure these secrets in GitHub repository settings:

### Kubernetes Deployment
- `KUBECONFIG` - Kubernetes cluster kubeconfig file
- `DB_PASSWORD` - PostgreSQL database password
- `REDIS_PASSWORD` - Redis password
- `RABBITMQ_PASSWORD` - RabbitMQ password
- `APP_KEY` - Laravel application key

### Optional
- `SNYK_TOKEN` - Snyk API token for dependency scanning
- `SLACK_WEBHOOK_URL` - Slack webhook for notifications
- `CODECOV_TOKEN` - Codecov token for coverage reports

## Environment Variables

Set in repository settings → Environments → production:

- `KUBERNETES_NAMESPACE` - Kubernetes namespace (default: app038-production)
- `REGISTRY` - Container registry (default: ghcr.io)

## Usage

### Manual Deployment

```bash
# Trigger deployment manually
gh workflow run ci-cd.yml --ref main
```

### Rollback

The workflow automatically rolls back on deployment failure. To manually rollback:

```bash
helm rollback app038 <revision> -n app038-production
```

### View Workflow Status

```bash
gh run list --workflow=ci-cd.yml
```

## Matrix Strategy

The workflow uses matrix strategies to test multiple versions:

- **PHP**: 8.2, 8.3
- **Node**: 20, 22

This ensures compatibility across supported versions.

## Caching

The workflow uses GitHub Actions cache for:
- Composer dependencies
- npm dependencies
- Docker BuildKit cache

This significantly speeds up workflow execution.

## Docker Images

Images are tagged with:
- Branch name (e.g., `main`, `develop`)
- Git SHA (e.g., `main-abc123`)
- Semantic version (e.g., `v1.0.0`)
- `latest` (only for default branch)

## Helm Deployment

The deployment job:
1. Sets up Kubernetes context
2. Creates namespace if needed
3. Creates/updates secrets
4. Deploys using Helm with `--atomic` flag
5. Waits for rollout
6. Runs health checks

## Rollback Mechanism

If deployment fails:
1. `rollback` job is triggered
2. Gets previous Helm revision
3. Executes `helm rollback`
4. Sends notification (if configured)

## Best Practices

1. **Never commit secrets** - Use GitHub Secrets
2. **Use environment protection** - Protect production environment
3. **Review PRs** - Require reviews before merging
4. **Monitor workflows** - Set up notifications
5. **Regular security scans** - Weekly vulnerability scans
6. **Test before deploy** - All tests must pass
7. **Use atomic deployments** - Helm `--atomic` flag
8. **Health checks** - Verify deployment success

## Troubleshooting

### Tests failing
- Check service containers (PostgreSQL, Redis) are healthy
- Verify environment variables are set correctly
- Check database migrations

### Docker build failing
- Verify Dockerfile syntax
- Check build context
- Review BuildKit cache

### Deployment failing
- Check Kubernetes cluster connectivity
- Verify secrets are set correctly
- Check Helm chart values
- Review pod logs: `kubectl logs -n app038-production -l app.kubernetes.io/name=app038`

### Rollback not working
- Verify previous revision exists
- Check Helm history: `helm history app038 -n app038-production`
- Ensure rollback job has proper permissions

