# Configuration Guide: PostgreSQL + Redis + RabbitMQ + Kafka

This guide explains how to configure Laravel for PostgreSQL, Redis, RabbitMQ, and Kafka with fallback connection logic.

## Overview

The application is configured with:
- **PostgreSQL** as the primary database (with fallback support)
- **Redis** for caching, sessions, and queue management
- **RabbitMQ** as the primary message queue (with fallback to Redis)
- **Kafka** for event streaming and messaging

## Docker Compose Services

The `docker-compose.yml` file includes the following services:

### Primary Services
- `postgres` - PostgreSQL 16 database (port 5432)
- `redis` - Redis 7 cache (port 6379)
- `rabbitmq` - RabbitMQ message broker (AMQP: 5672, Management UI: 15672)
- `zookeeper` - Zookeeper for Kafka (port 2181)
- `kafka` - Kafka broker (port 9092)

### Fallback Services (Optional)
- `postgres_fallback` - Fallback PostgreSQL (port 5433)
- `redis_fallback` - Fallback Redis (port 6380)
- `rabbitmq_fallback` - Fallback RabbitMQ (AMQP: 5673, Management UI: 15673)
- `kafka_fallback` - Fallback Kafka broker (port 9093)

### Monitoring Services (Optional)
- `kafka-ui` - Kafka UI for monitoring (port 8080)

## Starting Services

### Start All Primary Services
```bash
docker-compose up -d
```

### Start with Fallback Services
```bash
docker-compose --profile fallback up -d
```

### Start with Monitoring
```bash
docker-compose --profile monitoring up -d
```

### Start Everything
```bash
docker-compose --profile fallback --profile monitoring up -d
```

## Configuration Files

### Database Configuration (`config/database.php`)

The database configuration includes:
- Primary PostgreSQL connection (`pgsql`)
- Fallback PostgreSQL connection (`pgsql_fallback`)
- Redis connections for cache, session, and queue
- Fallback Redis connection

**Key Features:**
- Automatic fallback to secondary database on connection failure
- Connection timeout and retry logic
- SSL mode configuration

### Queue Configuration (`config/queue.php`)

The queue configuration includes:
- Primary RabbitMQ connection
- Fallback RabbitMQ connection
- Redis queue connections (primary and fallback)
- Database queue as last resort
- Sync driver for development

**Queue Priority:**
1. RabbitMQ (primary)
2. RabbitMQ (fallback)
3. Redis
4. Redis (fallback)
5. Database
6. Sync (development only)

### Kafka Configuration (`config/kafka.php`)

Kafka configuration includes:
- Primary broker configuration
- Fallback broker configuration
- Consumer group settings
- Compression and timeout settings
- Automatic fallback on connection failure

## Environment Variables

See `ENV_SKELETON.md` for complete environment variable documentation.

### Quick Reference

**PostgreSQL:**
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

**Redis:**
```env
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=redis_password
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
```

**RabbitMQ:**
```env
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
```

**Kafka:**
```env
KAFKA_BROKERS=kafka:9092
KAFKA_CONSUMER_GROUP_ID=laravel-group
KAFKA_OFFSET_RESET=latest
```

## Connection Service

The `ConnectionService` class provides automatic fallback logic for all connections:

```php
use App\Services\ConnectionService;

// Get database connection with fallback
$db = ConnectionService::getDatabaseConnection('pgsql');

// Get Redis connection with fallback
$redis = ConnectionService::getRedisConnection('default');

// Get cache connection with fallback
$cache = ConnectionService::getCacheConnection('redis');

// Get queue connection with fallback
$queue = ConnectionService::getQueueConnection('rabbitmq');
```

## Testing Connections

### Using Artisan Command
```bash
php artisan connections:test
```

This command tests all connections and shows their status:
- ✓ Green: Connection successful
- ✗ Red: Connection failed (with error message)

### Manual Testing

```php
use App\Services\ConnectionService;

// Test all connections
$status = ConnectionService::testAllConnections();
dd($status);
```

## Fallback Logic

### Database Fallback
1. Try primary PostgreSQL connection
2. On failure, try fallback PostgreSQL connection
3. Log all connection attempts

### Redis Fallback
1. Try primary Redis connection
2. On failure, try fallback Redis connection
3. Log all connection attempts

### Cache Fallback
1. Try Redis cache
2. On failure, try database cache
3. Last resort: file cache

### Queue Fallback
1. Try RabbitMQ (primary)
2. Try RabbitMQ (fallback)
3. Try Redis queue
4. Try Redis queue (fallback)
5. Try database queue
6. Last resort: sync driver

### Kafka Fallback
1. Try primary Kafka broker
2. On failure, try fallback broker
3. Automatic retry with configurable attempts

## Monitoring

### RabbitMQ Management UI
Access at: `http://localhost:15672`
- Username: `guest`
- Password: `guest`

### Kafka UI
Access at: `http://localhost:8080` (when monitoring profile is enabled)

### Health Checks

All Docker services include health checks:
```bash
# Check service health
docker-compose ps

# View logs
docker-compose logs -f [service_name]
```

## Production Considerations

1. **Security:**
   - Change all default passwords
   - Use SSL/TLS for connections
   - Restrict network access

2. **High Availability:**
   - Enable fallback services
   - Configure replication
   - Set up monitoring and alerts

3. **Performance:**
   - Tune connection pools
   - Configure appropriate timeouts
   - Monitor resource usage

4. **Backup:**
   - Regular database backups
   - Redis persistence enabled
   - Kafka log retention policies

## Troubleshooting

### Connection Issues

1. **Check service status:**
   ```bash
   docker-compose ps
   ```

2. **View service logs:**
   ```bash
   docker-compose logs [service_name]
   ```

3. **Test connections:**
   ```bash
   php artisan connections:test
   ```

4. **Check environment variables:**
   ```bash
   php artisan config:show database
   php artisan config:show queue
   ```

### Common Issues

**PostgreSQL connection refused:**
- Verify service is running: `docker-compose ps postgres`
- Check port mapping: `docker-compose port postgres 5432`
- Verify credentials in `.env`

**Redis connection timeout:**
- Check Redis password
- Verify network connectivity
- Check Redis logs: `docker-compose logs redis`

**RabbitMQ connection failed:**
- Access management UI to verify service
- Check virtual host configuration
- Verify credentials

**Kafka connection issues:**
- Ensure Zookeeper is running
- Check broker advertised listeners
- Verify network configuration

## Additional Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)
- [RabbitMQ Documentation](https://www.rabbitmq.com/documentation.html)
- [Kafka Documentation](https://kafka.apache.org/documentation/)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Redis Documentation](https://laravel.com/docs/redis)

