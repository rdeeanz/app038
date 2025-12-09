# Monitoring Stack Helm Chart

This Helm chart deploys a comprehensive monitoring stack for Kubernetes, including:

- **Prometheus** - Metrics collection and alerting
- **Grafana** - Visualization and dashboards
- **Loki** - Log aggregation
- **Jaeger** - Distributed tracing

## Prerequisites

- Kubernetes 1.24+
- Helm 3.8+
- StorageClass for persistent volumes
- Ingress controller (nginx recommended)

## Installation

### Add Helm Repositories

```bash
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo add grafana https://grafana.github.io/helm-charts
helm repo add jaegertracing https://jaegertracing.github.io/helm-charts
helm repo update
```

### Install Dependencies

```bash
helm dependency build helm/monitoring
```

### Install Monitoring Stack

```bash
helm install monitoring helm/monitoring \
  --namespace monitoring \
  --create-namespace \
  --values helm/monitoring/values.yaml
```

### Upgrade

```bash
helm upgrade monitoring helm/monitoring \
  --namespace monitoring \
  --values helm/monitoring/values.yaml
```

## Configuration

### Prometheus

- **Retention**: 30 days
- **Storage**: 50GB PVC
- **Scrape Interval**: 30s
- **Evaluation Interval**: 30s

### Grafana

- **Default Admin**: admin/admin (change in production!)
- **Storage**: 10GB PVC
- **Ingress**: grafana.app038.local

### Loki

- **Retention**: 168 hours (7 days)
- **Storage**: 50GB PVC
- **Ingestion Rate**: 16 MB/s

### Jaeger

- **Storage**: Elasticsearch (configure in values.yaml)
- **Ingress**: jaeger.app038.local

## Access

### Grafana

```bash
# Port forward
kubectl port-forward -n monitoring svc/grafana 3000:80

# Access via ingress
http://grafana.app038.local
```

Default credentials: `admin/admin`

### Prometheus

```bash
# Port forward
kubectl port-forward -n monitoring svc/prometheus-server 9090:80

# Access via service
http://prometheus-server.monitoring.svc.cluster.local
```

### Jaeger

```bash
# Port forward
kubectl port-forward -n monitoring svc/jaeger-query 16686:80

# Access via ingress
http://jaeger.app038.local
```

### Loki

```bash
# Port forward
kubectl port-forward -n monitoring svc/loki 3100:3100
```

## Dashboards

### Pre-configured Dashboards

1. **App038 Overview Dashboard** (`app038-overview-dashboard.json`)
   - Request rates
   - Response times
   - HTTP status codes
   - Resource usage
   - Error logs

2. **App038 SLO Dashboard** (`app038-slo-dashboard.json`)
   - Availability SLO (99.9%)
   - Latency SLO (p99 < 500ms)
   - Error Rate SLO (< 0.1%)
   - Error budget burn rate

### Import Dashboards

Dashboards are automatically provisioned via Grafana's dashboard provisioning. To manually import:

1. Go to Grafana → Dashboards → Import
2. Upload the JSON file from `helm/monitoring/dashboards/`
3. Select Prometheus as the data source

## Alert Rules

### SLO Alerts

Located in `prometheus-alerts.yaml`:

- **AvailabilitySLOViolation** - Availability < 99.9% for 5 minutes
- **LatencySLOViolation** - p99 latency > 500ms for 5 minutes
- **ErrorRateSLOViolation** - Error rate > 0.1% for 5 minutes
- **HighErrorBudgetBurnRate** - Error rate > 0.2% for 1 minute

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

## SLO Configuration

### Service Level Objectives

1. **Availability**: 99.9% (0.999)
   - Error budget: 0.1% (43.2 minutes/month)

2. **Latency**: 99th percentile < 500ms
   - Target: p99 < 0.5 seconds

3. **Error Rate**: < 0.1% (0.001)
   - Target: < 1 error per 1000 requests

### Error Budget

- **30-day window**: 43.2 minutes downtime allowed
- **Burn rate alerts**: 
  - 2x burn rate: Warning
  - 10x burn rate: Critical

## Metrics

### Application Metrics

The following metrics should be exposed by your application:

```php
// Example Laravel metrics endpoint
http_requests_total{method="GET",status="200",endpoint="/api/dashboard"}
http_request_duration_seconds_bucket{le="0.1",endpoint="/api/dashboard"}
http_request_duration_seconds_bucket{le="0.5",endpoint="/api/dashboard"}
http_request_duration_seconds_bucket{le="1.0",endpoint="/api/dashboard"}
http_request_duration_seconds_bucket{le="+Inf",endpoint="/api/dashboard"}
```

### Instrumentation

Use Prometheus client libraries:

- **PHP**: `promphp/prometheus_client_php`
- **Node.js**: `prom-client`

## Logging

### Loki Integration

Logs are automatically collected by Promtail from:

- Kubernetes pods
- Container logs
- Application logs

### Log Labels

Logs are labeled with:
- `app` - Application name
- `namespace` - Kubernetes namespace
- `pod` - Pod name
- `container` - Container name

## Tracing

### Jaeger Integration

Configure your application to send traces to Jaeger:

```php
// Laravel example
JAEGER_AGENT_HOST=jaeger-agent.monitoring.svc.cluster.local
JAEGER_AGENT_PORT=6831
```

### Trace Sampling

Configure sampling rates in your application:

- **Production**: 1% (0.01)
- **Staging**: 10% (0.1)
- **Development**: 100% (1.0)

## Troubleshooting

### Prometheus Not Scraping

1. Check ServiceMonitor/PodMonitor resources
2. Verify service endpoints are accessible
3. Check Prometheus targets: `http://prometheus:9090/targets`

### Grafana No Data

1. Verify data source connection
2. Check Prometheus is accessible
3. Verify metrics are being collected

### Loki No Logs

1. Check Promtail is running
2. Verify log paths are correct
3. Check Loki storage is available

### Alerts Not Firing

1. Check alert rules are loaded
2. Verify alertmanager is configured
3. Check alert routes and receivers

## Customization

### Modify Values

Edit `values.yaml` to customize:

- Resource limits
- Storage sizes
- Retention periods
- Alert thresholds
- Ingress configurations

### Add Custom Dashboards

1. Create dashboard JSON in `dashboards/`
2. Update `values.yaml` Grafana dashboards section
3. Upgrade Helm release

### Add Custom Alerts

1. Add alert rules to `prometheus-alerts.yaml`
2. Update Prometheus serverFiles in `values.yaml`
3. Upgrade Helm release

## Production Considerations

1. **Change default passwords** - Update Grafana admin password
2. **Enable authentication** - Configure OAuth/SAML for Grafana
3. **Increase storage** - Adjust PVC sizes based on retention needs
4. **Configure backup** - Set up backups for Prometheus and Grafana data
5. **Resource limits** - Adjust based on cluster capacity
6. **High availability** - Consider multi-replica deployments
7. **Network policies** - Restrict access to monitoring services
8. **TLS/SSL** - Enable TLS for all ingress endpoints

## Uninstallation

```bash
helm uninstall monitoring --namespace monitoring
```

Note: This will delete all monitoring data. Backup important dashboards and alerts before uninstalling.

