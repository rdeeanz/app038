# SRE (Site Reliability Engineering) Guide

Comprehensive SRE configuration for App038 including SLOs, SLIs, autoscaling, circuit breakers, and disaster recovery.

## Overview

This guide covers:
- Service Level Objectives (SLOs) and Indicators (SLIs)
- Horizontal Pod Autoscaling (HPA)
- KEDA autoscaling
- Circuit breaker middleware
- Disaster recovery backup scripts

## SLOs and SLIs

### Service Level Objectives

Located in `sre/slos.yaml`, defines availability, latency, and error rate targets for:

- **Laravel API**: 99.9% availability, <500ms p99 latency
- **Svelte Frontend**: 99.95% availability, <200ms p95 latency
- **PostgreSQL**: 99.99% availability, <10ms p95 latency
- **Redis**: 99.95% availability, <5ms p95 latency, >90% hit rate
- **RabbitMQ**: 99.9% availability, <1000 queue depth
- **ERP Integration**: 99.5% availability, <30s p99 sync latency

### Service Level Indicators

Located in `sre/slis.yaml`, Prometheus recording rules for:

- Availability ratios
- Latency percentiles (p50, p95, p99)
- Error rates
- Throughput metrics
- Resource utilization
- Error budget calculations

### Viewing SLO/SLI Metrics

```bash
# Query availability SLI
kubectl exec -it prometheus-server -n monitoring -- \
  promtool query instant 'sli:laravel_api:availability:ratio'

# Query latency SLI
kubectl exec -it prometheus-server -n monitoring -- \
  promtool query instant 'sli:laravel_api:latency:p99'

# Query error budget
kubectl exec -it prometheus-server -n monitoring -- \
  promtool query instant 'sli:laravel_api:error_budget_remaining:ratio'
```

## Autoscaling

### HPA (Horizontal Pod Autoscaler)

**Location**: `helm/app038/templates/laravel-hpa-advanced.yaml`

**Features**:
- CPU-based scaling (70% threshold)
- Memory-based scaling (80% threshold)
- Custom metrics (request rate, queue depth)
- Scale-up/down policies with stabilization windows

**Configuration**:
```yaml
laravel:
  autoscaling:
    enabled: true
    minReplicas: 3
    maxReplicas: 10
    targetCPUUtilizationPercentage: 70
    targetMemoryUtilizationPercentage: 80
    targetRequestRate: 100
```

### KEDA Autoscaling

**Location**: `helm/app038/templates/laravel-keda-scaledobject.yaml`

**Features**:
- RabbitMQ queue depth scaling
- Redis list length scaling
- PostgreSQL connection pool scaling
- Prometheus metric-based scaling

**Enable KEDA**:
```yaml
keda:
  enabled: true
  cooldownPeriod: 300
  pollingInterval: 30
  queueLengthThreshold: 100
```

**Scalers Supported**:
- RabbitMQ queue depth
- Redis list length
- PostgreSQL connections
- Prometheus metrics
- Custom metrics

## Circuit Breaker

### Middleware

**Location**: `app/Http/Middleware/CircuitBreaker.php`

**States**:
- **Closed**: Normal operation
- **Open**: Circuit open, requests blocked
- **Half-Open**: Testing if service recovered

**Configuration** (`config/circuit_breaker.php`):
```php
'failure_threshold' => 5,      // Failures before opening
'success_threshold' => 2,     // Successes to close from half-open
'timeout' => 60,               // Seconds before attempting reset
```

### Usage

```php
// Apply to routes
Route::middleware(['circuit.breaker:erp-integration'])->group(function () {
    Route::post('/erp/sync', [ERPIntegrationController::class, 'sync']);
});

// Service-specific configuration
'services' => [
    'erp-integration' => [
        'failure_threshold' => 3,
        'timeout' => 120,
    ],
],
```

### API Endpoints

```bash
# Get all circuit breaker statuses
GET /api/sre/circuit-breakers

# Get specific service status
GET /api/sre/circuit-breakers/erp-integration

# Reset circuit breaker
POST /api/sre/circuit-breakers/erp-integration/reset
```

## Disaster Recovery

### PostgreSQL Backup

**Script**: `scripts/backup/postgresql-backup.sh`

**Backup Types**:
- **Full**: Complete database dump (daily)
- **Incremental**: Base backup with WAL (every 6 hours)
- **WAL**: Write-Ahead Log archiving (continuous)

**Usage**:
```bash
# Full backup
./scripts/backup/postgresql-backup.sh full

# Incremental backup
./scripts/backup/postgresql-backup.sh incremental

# With S3 upload
S3_BUCKET=my-backups ./scripts/backup/postgresql-backup.sh full

# With Vault authentication
VAULT_ADDR=http://vault:8200 \
VAULT_TOKEN=my-token \
./scripts/backup/postgresql-backup.sh full
```

**Restore**:
```bash
# Restore from backup
./scripts/backup/postgresql-restore.sh /backups/postgresql/full_backup_20240101_020000.sql.gz

# Restore from S3
S3_BUCKET=my-backups \
./scripts/backup/postgresql-restore.sh full_backup_20240101_020000.sql.gz
```

### Vault Backup

**Script**: `scripts/backup/vault-backup.sh`

**Backup Types**:
- **Full**: Complete snapshot (daily)
- **Policies**: Policy files only (weekly)
- **Snapshot**: Raft snapshot

**Usage**:
```bash
# Full backup
./scripts/backup/vault-backup.sh full

# Policies only
./scripts/backup/vault-backup.sh policies

# With S3 upload
S3_BUCKET=my-backups ./scripts/backup/vault-backup.sh full
```

**Restore**:
```bash
# Restore from snapshot
./scripts/backup/vault-restore.sh /backups/vault/vault_snapshot_20240101_030000.snap.gz

# Restore from S3
S3_BUCKET=my-backups \
./scripts/backup/vault-restore.sh vault_snapshot_20240101_030000.snap.gz
```

### Automated Backups

**Cron Script**: `scripts/backup/backup-cron.sh`

**Schedule**:
- PostgreSQL full backup: Daily at 2 AM
- PostgreSQL incremental: Every 6 hours
- Vault full backup: Daily at 3 AM
- Vault policies: Weekly on Sunday at 4 AM

**Setup**:
```bash
chmod +x scripts/backup/backup-cron.sh
./scripts/backup/backup-cron.sh
```

## Monitoring SLOs

### Grafana Dashboard

The SLO dashboard (`helm/monitoring/dashboards/app038-slo-dashboard.json`) displays:

- Availability percentage
- Latency percentiles
- Error rates
- Error budget remaining
- Error budget burn rate

### Prometheus Alerts

Located in `helm/monitoring/prometheus-alerts.yaml`:

- **AvailabilitySLOViolation**: When availability drops below target
- **LatencySLOViolation**: When latency exceeds target
- **ErrorRateSLOViolation**: When error rate exceeds target
- **HighErrorBudgetBurnRate**: When error budget is burning too fast

## Best Practices

### SLO Management

1. **Set realistic targets** based on business requirements
2. **Monitor error budgets** to prevent SLO violations
3. **Review SLOs regularly** and adjust as needed
4. **Document SLO decisions** and rationale

### Autoscaling

1. **Start conservative** with min/max replicas
2. **Monitor scaling behavior** to tune thresholds
3. **Use multiple metrics** for better scaling decisions
4. **Set appropriate cooldown periods** to prevent thrashing

### Circuit Breakers

1. **Configure per service** based on failure characteristics
2. **Monitor circuit states** in dashboards
3. **Set appropriate timeouts** for service recovery
4. **Implement fallbacks** when circuits are open

### Backup Strategy

1. **Test restores regularly** to ensure backups work
2. **Store backups off-site** (S3, separate region)
3. **Encrypt backups** for sensitive data
4. **Document restore procedures** for disaster scenarios
5. **Automate backup verification** to catch failures early

## Troubleshooting

### SLO Violations

```bash
# Check current availability
kubectl exec -it prometheus-server -n monitoring -- \
  promtool query instant 'sli:laravel_api:availability:ratio'

# Check error budget burn rate
kubectl exec -it prometheus-server -n monitoring -- \
  promtool query instant 'sli:laravel_api:error_budget_burn_rate:1h'
```

### Autoscaling Issues

```bash
# Check HPA status
kubectl get hpa -n default

# Check KEDA ScaledObject
kubectl get scaledobject -n default

# View scaling events
kubectl describe hpa laravel-hpa -n default
```

### Circuit Breaker Issues

```bash
# Check circuit breaker state
curl http://api/app038.example.com/api/sre/circuit-breakers/erp-integration

# Reset circuit breaker
curl -X POST http://api/app038.example.com/api/sre/circuit-breakers/erp-integration/reset
```

### Backup Issues

```bash
# Verify backup integrity
gzip -t /backups/postgresql/full_backup_*.sql.gz

# Check backup logs
tail -f /var/log/backups/postgresql-backup.log

# Test restore in staging
./scripts/backup/postgresql-restore.sh <backup_file>
```

## Resources

- [Google SRE Book](https://sre.google/books/)
- [Kubernetes HPA Documentation](https://kubernetes.io/docs/tasks/run-application/horizontal-pod-autoscale/)
- [KEDA Documentation](https://keda.sh/docs/)
- [Circuit Breaker Pattern](https://martinfowler.com/bliki/CircuitBreaker.html)
- [PostgreSQL Backup Documentation](https://www.postgresql.org/docs/current/backup.html)
- [Vault Backup Documentation](https://developer.hashicorp.com/vault/docs/operations/backup)

