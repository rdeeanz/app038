# Environment Variables Skeleton

This file documents all environment variables used in the application.

> **Note**: This is a skeleton file. Never commit actual `.env` files with real secrets to version control.

## Vault Configuration

```env
# Vault Address
VAULT_ADDR=http://vault:8200

# Vault Authentication
VAULT_TOKEN=your-vault-token-here
VAULT_AUTH_METHOD=kubernetes  # Options: token, kubernetes, approle

# AppRole Authentication (if using AppRole)
VAULT_ROLE_ID=your-role-id
VAULT_SECRET_ID=your-secret-id
VAULT_APPROLE_MOUNT=approle

# Kubernetes Authentication (if using Kubernetes)
VAULT_K8S_ROLE=laravel-app
VAULT_K8S_MOUNT=kubernetes
VAULT_K8S_TOKEN_PATH=/var/run/secrets/kubernetes.io/serviceaccount/token

# Secrets Engine Paths
VAULT_KV_PATH=secret/data/app038
VAULT_KV_VERSION=2
VAULT_DB_PATH=database/creds/app038-readwrite
VAULT_TRANSIT_PATH=transit
VAULT_TRANSIT_KEY=app038-key

# Vault Cache
VAULT_CACHE_ENABLED=true
VAULT_CACHE_TTL=3600
VAULT_CACHE_STORE=redis

# Vault Connection
VAULT_TIMEOUT=5
VAULT_TLS_VERIFY=true
VAULT_TLS_CERT_PATH=
VAULT_TLS_KEY_PATH=
VAULT_TLS_CA_PATH=
```

## Database Configuration

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Fallback Database
DB_FALLBACK_HOST=postgres_fallback
DB_FALLBACK_PORT=5432
DB_FALLBACK_DATABASE=app038
DB_FALLBACK_USERNAME=postgres
DB_FALLBACK_PASSWORD=postgres
```

## Redis Configuration

```env
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# Fallback Redis
REDIS_FALLBACK_HOST=redis_fallback
REDIS_FALLBACK_PORT=6379
REDIS_FALLBACK_PASSWORD=null
REDIS_FALLBACK_DB=0
```

## Queue Configuration

```env
QUEUE_CONNECTION=rabbitmq

# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/

# RabbitMQ Fallback
RABBITMQ_FALLBACK_HOST=rabbitmq_fallback
RABBITMQ_FALLBACK_PORT=5672
RABBITMQ_FALLBACK_USER=guest
RABBITMQ_FALLBACK_PASSWORD=guest
```

## Kafka Configuration

```env
KAFKA_BROKERS=kafka:9092
KAFKA_BROKERS_FALLBACK=kafka_fallback:9092
KAFKA_CONSUMER_GROUP_ID=app038-consumer
KAFKA_OFFSET_RESET=earliest
KAFKA_AUTO_COMMIT=true
KAFKA_COMPRESSION_TYPE=gzip
```

## SAP Configuration

```env
SAP_DEFAULT_CONNECTOR=odata
SAP_ODATA_BASE_URL=https://sap.example.com
SAP_ODATA_USERNAME=
SAP_ODATA_PASSWORD=
SAP_RFC_HOST=
SAP_RFC_CLIENT=
SAP_RFC_USER=
SAP_RFC_PASSWORD=
SAP_IDOC_HOST=
SAP_IDOC_PORT=3300
```

## Application Configuration

```env
APP_NAME=App038
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

## Cache and Session

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

## Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Sanctum Configuration

```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:8000,::1
```
