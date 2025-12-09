# Monitoring Stack Guide

Complete monitoring setup for App038 using Prometheus, Grafana, Loki, and Jaeger.

## Architecture

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Laravel    │────▶│ Prometheus   │────▶│  Grafana    │
│  App        │     │  (Metrics)   │     │ (Dashboards)│
└─────────────┘     └──────────────┘     └─────────────┘
      │                    │
      │                    │
      ▼                    ▼
┌─────────────┐     ┌──────────────┐
│  Promtail   │────▶│    Loki      │
│  (Logs)     │     │  (Log Agg)   │
└─────────────┘     └──────────────┘
      │
      │
      ▼
┌─────────────┐
│   Jaeger    │
│  (Traces)   │
└─────────────┘
```

## Quick Start

### 1. Install Monitoring Stack

```bash
# Add Helm repositories
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo add grafana https://grafana.github.io/helm-charts
helm repo add jaegertracing https://jaegertracing.github.io/helm-charts
helm repo update

# Build dependencies
helm dependency build helm/monitoring

# Install
helm install monitoring helm/monitoring \
  --namespace monitoring \
  --create-namespace
```

### 2. Access Services

```bash
# Grafana
kubectl port-forward -n monitoring svc/grafana 3000:80
# http://localhost:3000 (admin/admin)

# Prometheus
kubectl port-forward -n monitoring svc/prometheus-server 9090:80
# http://localhost:9090

# Jaeger
kubectl port-forward -n monitoring svc/jaeger-query 16686:80
# http://localhost:16686

# Loki
kubectl port-forward -n monitoring svc/loki 3100:3100
```

## Service Level Objectives (SLOs)

### Availability SLO

- **Target**: 99.9% uptime
- **Error Budget**: 0.1% (43.2 minutes/month)
- **Alert**: Fires when availability < 99.9% for 5 minutes

### Latency SLO

- **Target**: 99th percentile < 500ms
- **Alert**: Fires when p99 latency > 500ms for 5 minutes

### Error Rate SLO

- **Target**: < 0.1% errors
- **Alert**: Fires when error rate > 0.1% for 5 minutes

## Dashboards

### App038 Overview Dashboard

**Location**: `helm/monitoring/dashboards/app038-overview-dashboard.json`

**Panels**:
- Request Rate by Service
- Response Time Percentiles (p50, p95, p99)
- HTTP Status Codes (2xx, 4xx, 5xx)
- Memory Usage
- CPU Usage
- Recent Errors (Loki)

### App038 SLO Dashboard

**Location**: `helm/monitoring/dashboards/app038-slo-dashboard.json`

**Panels**:
- Availability SLO (Target: 99.9%)
- Latency SLO - 99th Percentile (Target: < 500ms)
- Error Rate SLO (Target: < 0.1%)
- Request Rate (Total vs Errors)
- SLO Status Summary Table

## Alert Rules

### SLO Alerts

All SLO alerts are defined in `helm/monitoring/prometheus-alerts.yaml`:

1. **AvailabilitySLOViolation**
   - Condition: Availability < 99.9% for 5 minutes
   - Severity: Critical

2. **LatencySLOViolation**
   - Condition: p99 latency > 500ms for 5 minutes
   - Severity: Critical

3. **ErrorRateSLOViolation**
   - Condition: Error rate > 0.1% for 5 minutes
   - Severity: Critical

4. **HighErrorBudgetBurnRate**
   - Condition: Error rate > 0.2% for 1 minute
   - Severity: Warning

### Application Alerts

- **HighRequestRate** - Request rate > 1000 req/s
- **HighMemoryUsage** - Memory usage > 90%
- **HighCPUUsage** - CPU usage > 90%
- **PodCrashLooping** - Pod restarting frequently
- **DatabaseConnectionPoolExhausted** - DB pool > 90%
- **QueueBacklogGrowing** - Queue backlog > 1000 jobs

### Infrastructure Alerts

- **NodeDown** - Node unreachable for 5 minutes
- **DiskSpaceLow** - Disk space < 10%
- **HighDiskIO** - Disk I/O > 90%

## Instrumentation

### Laravel Application

Install Prometheus client:

```bash
composer require promphp/prometheus_client_php
```

Expose metrics endpoint:

```php
// routes/api.php
Route::get('/metrics', [MetricsController::class, 'index']);

// app/Http/Controllers/MetricsController.php
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function index(CollectorRegistry $registry)
    {
        $renderer = new RenderTextFormat();
        return response($renderer->render($registry->getMetricFamilySamples()))
            ->header('Content-Type', 'text/plain');
    }
}
```

### Svelte Frontend

Install Prometheus client:

```bash
npm install prom-client
```

Expose metrics:

```javascript
// metrics.js
import { register, Counter, Histogram } from 'prom-client';

const httpRequestsTotal = new Counter({
  name: 'http_requests_total',
  help: 'Total number of HTTP requests',
  labelNames: ['method', 'status', 'endpoint']
});

const httpRequestDuration = new Histogram({
  name: 'http_request_duration_seconds',
  help: 'HTTP request duration in seconds',
  labelNames: ['method', 'endpoint'],
  buckets: [0.1, 0.5, 1.0, 2.5, 5.0]
});

// Export metrics endpoint
app.get('/metrics', async (req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(await register.metrics());
});
```

## Logging

### Laravel Logs to Loki

Configure Laravel to send logs to Loki:

```php
// config/logging.php
'channels' => [
    'loki' => [
        'driver' => 'custom',
        'via' => \App\Logging\LokiLogger::class,
        'url' => env('LOKI_URL', 'http://loki-gateway.monitoring.svc.cluster.local/loki/api/v1/push'),
    ],
],
```

### Promtail Configuration

Promtail automatically collects logs from:
- Kubernetes pods
- Container stdout/stderr
- Application log files

Logs are labeled with:
- `app` - Application name
- `namespace` - Kubernetes namespace
- `pod` - Pod name
- `container` - Container name

## Tracing

### Jaeger Integration

Configure Laravel to send traces:

```php
// .env
JAEGER_AGENT_HOST=jaeger-agent.monitoring.svc.cluster.local
JAEGER_AGENT_PORT=6831
JAEGER_SAMPLING_RATE=0.01
```

Install Jaeger client:

```bash
composer require jonahgeorge/jaeger-client-php
```

### Trace Sampling

- **Production**: 1% (0.01)
- **Staging**: 10% (0.1)
- **Development**: 100% (1.0)

## Query Examples

### Prometheus Queries

```promql
# Request rate
sum(rate(http_requests_total[5m])) by (job)

# Error rate
sum(rate(http_requests_total{status=~"5.."}[5m])) by (job)
/
sum(rate(http_requests_total[5m])) by (job)

# p99 latency
histogram_quantile(0.99, 
  sum(rate(http_request_duration_seconds_bucket[5m])) by (le, job)
)

# Availability
1 - (
  sum(rate(http_requests_total{status=~"5.."}[5m])) by (job)
  /
  sum(rate(http_requests_total[5m])) by (job)
)
```

### Loki Queries

```logql
# Error logs
{job=~".*laravel.*"} |= "error"

# Errors by pod
{job=~".*laravel.*"} |= "error" | json | line_format "{{.pod}}: {{.message}}"

# Error rate
sum(rate({job=~".*laravel.*"} |= "error" [5m])) by (pod)
```

## Maintenance

### Backup

```bash
# Backup Prometheus data
kubectl exec -n monitoring prometheus-server-0 -- tar czf - /prometheus | \
  gzip > prometheus-backup-$(date +%Y%m%d).tar.gz

# Backup Grafana dashboards
kubectl exec -n monitoring grafana-0 -- \
  grafana-cli admin export-dashboard > dashboards-backup.json
```

### Retention

- **Prometheus**: 30 days (configurable in values.yaml)
- **Loki**: 168 hours / 7 days (configurable in values.yaml)
- **Jaeger**: Depends on Elasticsearch retention

### Scaling

Adjust resources in `values.yaml`:

```yaml
prometheus:
  prometheusSpec:
    resources:
      limits:
        cpu: 2000m
        memory: 4Gi
      requests:
        cpu: 1000m
        memory: 2Gi
```

## Troubleshooting

### Prometheus Not Scraping

1. Check ServiceMonitor resources:
   ```bash
   kubectl get servicemonitor -n monitoring
   ```

2. Check Prometheus targets:
   ```bash
   kubectl port-forward -n monitoring svc/prometheus-server 9090:80
   # Visit http://localhost:9090/targets
   ```

3. Verify service endpoints:
   ```bash
   kubectl get endpoints -n default
   ```

### Grafana No Data

1. Check data source connection:
   - Grafana → Configuration → Data Sources
   - Test Prometheus connection

2. Verify metrics exist:
   ```bash
   curl http://prometheus-server.monitoring.svc.cluster.local/api/v1/query?query=http_requests_total
   ```

### Alerts Not Firing

1. Check alert rules are loaded:
   ```bash
   kubectl get prometheusrule -n monitoring
   ```

2. Check Alertmanager:
   ```bash
   kubectl port-forward -n monitoring svc/alertmanager-main 9093:9093
   # Visit http://localhost:9093
   ```

3. Verify alert expression:
   ```bash
   # Test in Prometheus UI
   http://localhost:9090/graph
   ```

## Production Checklist

- [ ] Change Grafana default password
- [ ] Enable OAuth/SAML authentication
- [ ] Configure TLS for all ingress endpoints
- [ ] Set appropriate resource limits
- [ ] Configure backup strategy
- [ ] Set up alert notification channels (Slack, PagerDuty, etc.)
- [ ] Review and adjust SLO targets
- [ ] Configure log retention policies
- [ ] Set up monitoring for monitoring stack itself
- [ ] Document runbooks for common alerts

