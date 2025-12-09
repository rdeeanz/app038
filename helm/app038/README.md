# App038 Helm Chart

A Helm chart for deploying Laravel API + Svelte frontend with Redis and RabbitMQ on Kubernetes.

## Prerequisites

- Kubernetes 1.19+
- Helm 3.0+
- Ingress controller (e.g., NGINX Ingress Controller)
- PersistentVolume provisioner support in the underlying infrastructure

## Installation

### Install with default values

```bash
helm install app038 ./helm/app038
```

### Install with custom values

```bash
helm install app038 ./helm/app038 -f my-values.yaml
```

### Install with secrets

```bash
helm install app038 ./helm/app038 \
  --set secrets.dbPassword=your-db-password \
  --set secrets.redisPassword=your-redis-password \
  --set secrets.rabbitmqPassword=your-rabbitmq-password \
  --set secrets.appKey=your-laravel-app-key
```

### Install from external secret management

If using external secret management (e.g., AWS Secrets Manager, HashiCorp Vault), create the secret manually:

```bash
kubectl create secret generic app038-secrets \
  --from-literal=DB_PASSWORD=your-db-password \
  --from-literal=REDIS_PASSWORD=your-redis-password \
  --from-literal=RABBITMQ_PASSWORD=your-rabbitmq-password \
  --from-literal=APP_KEY=your-laravel-app-key
```

Then set `secrets.create: false` in values.yaml.

## Configuration

### Values File

The following table lists the configurable parameters and their default values:

| Parameter | Description | Default |
|-----------|-------------|---------|
| `laravel.enabled` | Enable Laravel API deployment | `true` |
| `laravel.replicaCount` | Number of Laravel replicas | `3` |
| `laravel.image.repository` | Laravel image repository | `app038/laravel` |
| `laravel.image.tag` | Laravel image tag | `latest` |
| `svelte.enabled` | Enable Svelte frontend deployment | `true` |
| `svelte.replicaCount` | Number of Svelte replicas | `2` |
| `redis.enabled` | Enable Redis deployment | `true` |
| `rabbitmq.enabled` | Enable RabbitMQ deployment | `true` |
| `ingress.enabled` | Enable Ingress | `true` |
| `ingress.hosts[0].host` | Ingress hostname | `app038.example.com` |
| `secrets.create` | Create secrets | `true` |

### Environment Variables

The chart supports environment variables via:

1. **ConfigMap** - Non-sensitive configuration
2. **Secret** - Sensitive data (passwords, keys)
3. **SecretRef** - Reference to existing secrets

#### Using SecretRef

To use an existing secret, configure `envFrom` in values.yaml:

```yaml
laravel:
  envFrom:
    - secretRef:
        name: my-existing-secret
```

Or reference specific keys:

```yaml
laravel:
  env:
    - name: DB_PASSWORD
      valueFrom:
        secretKeyRef:
          name: my-secret
          key: database-password
```

## Components

### Laravel API

- **Deployment**: Manages Laravel PHP-FPM pods
- **Service**: ClusterIP service exposing port 80
- **HPA**: Horizontal Pod Autoscaler (if enabled)
- **PVC**: Persistent volume for storage directory

### Svelte Frontend

- **Deployment**: Manages Svelte Nginx pods
- **Service**: ClusterIP service exposing port 80
- **HPA**: Horizontal Pod Autoscaler (if enabled)

### Redis

- **Deployment**: Redis cache server
- **Service**: ClusterIP service exposing port 6379
- **PVC**: Persistent volume for data persistence

### RabbitMQ

- **Deployment**: RabbitMQ message broker
- **Service**: ClusterIP service exposing ports 5672 (AMQP) and 15672 (Management)
- **PVC**: Persistent volume for message persistence

### Ingress

- Routes traffic to Svelte frontend and Laravel API
- Supports TLS termination
- Configurable annotations for ingress controller

## Upgrading

```bash
helm upgrade app038 ./helm/app038 -f my-values.yaml
```

## Uninstalling

```bash
helm uninstall app038
```

**Note**: This will delete all resources including persistent volumes. Make sure to backup data before uninstalling.

## Troubleshooting

### Check pod status

```bash
kubectl get pods -l app.kubernetes.io/name=app038
```

### View logs

```bash
# Laravel logs
kubectl logs -l app.kubernetes.io/component=laravel --tail=100

# Svelte logs
kubectl logs -l app.kubernetes.io/component=svelte --tail=100

# Redis logs
kubectl logs -l app.kubernetes.io/component=redis --tail=100

# RabbitMQ logs
kubectl logs -l app.kubernetes.io/component=rabbitmq --tail=100
```

### Check services

```bash
kubectl get svc -l app.kubernetes.io/name=app038
```

### Check ingress

```bash
kubectl get ingress -l app.kubernetes.io/name=app038
```

### Debug configuration

```bash
# View rendered templates
helm template app038 ./helm/app038

# Dry run
helm install app038 ./helm/app038 --dry-run --debug
```

## Production Considerations

1. **Secrets Management**: Use external secret management (AWS Secrets Manager, HashiCorp Vault, etc.)
2. **Resource Limits**: Adjust CPU and memory limits based on workload
3. **Autoscaling**: Enable HPA for production workloads
4. **Persistence**: Ensure persistent volumes are backed up
5. **Monitoring**: Integrate with Prometheus/Grafana
6. **Logging**: Set up centralized logging (ELK, Loki, etc.)
7. **TLS**: Configure proper TLS certificates for ingress
8. **Network Policies**: Implement network policies for security

## Examples

### Production values.yaml

```yaml
laravel:
  replicaCount: 5
  autoscaling:
    enabled: true
    minReplicas: 5
    maxReplicas: 20
  resources:
    limits:
      cpu: 2000m
      memory: 1Gi
    requests:
      cpu: 1000m
      memory: 512Mi

svelte:
  replicaCount: 3
  autoscaling:
    enabled: true
    minReplicas: 3
    maxReplicas: 10

ingress:
  enabled: true
  className: "nginx"
  annotations:
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
  hosts:
    - host: app038.example.com
      paths:
        - path: /
          pathType: Prefix
          service: svelte
        - path: /api
          pathType: Prefix
          service: laravel
  tls:
    - secretName: app038-tls
      hosts:
        - app038.example.com
```

