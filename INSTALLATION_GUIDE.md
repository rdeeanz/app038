# Installation Guide: PostgreSQL + Redis + RabbitMQ + Kafka

This guide will help you install and configure all required packages and services.

## Prerequisites

- Docker and Docker Compose
- PHP >= 8.2
- Composer
- PostgreSQL client libraries (for PHP)
- Redis extension (phpredis or predis)
- AMQP extension (for RabbitMQ)

## Step 1: Install PHP Extensions

### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install -y \
    php8.2-pgsql \
    php8.2-redis \
    php8.2-amqp \
    php8.2-rdkafka
```

### macOS (Homebrew)
```bash
brew install php@8.2
pecl install redis
pecl install amqp
pecl install rdkafka
```

### Enable Extensions
Add to `php.ini`:
```ini
extension=pgsql
extension=redis
extension=amqp
extension=rdkafka
```

## Step 2: Install Composer Packages

```bash
composer require vladimir-yuldashev/laravel-queue-rabbitmq
composer require mateusjunges/laravel-kafka
```

## Step 3: Publish Configuration Files

```bash
# Publish Kafka configuration
php artisan vendor:publish --tag=laravel-kafka-config

# Publish RabbitMQ configuration (if needed)
php artisan vendor:publish --provider="VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider"
```

## Step 4: Configure Environment Variables

Copy the environment variables from `ENV_SKELETON.md` to your `.env` file:

```bash
# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=redis_password

# RabbitMQ
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

# Kafka
KAFKA_BROKERS=kafka:9092
KAFKA_CONSUMER_GROUP_ID=laravel-group
```

## Step 5: Start Docker Services

```bash
# Start all primary services
docker-compose up -d

# Or start with fallback services
docker-compose --profile fallback up -d

# Or start with monitoring
docker-compose --profile monitoring up -d
```

## Step 6: Verify Services

```bash
# Check service status
docker-compose ps

# Test connections
php artisan connections:test

# View logs
docker-compose logs -f
```

## Step 7: Run Migrations

```bash
php artisan migrate
```

## Step 8: Test Queue Workers

```bash
# Start queue worker for RabbitMQ
php artisan queue:work rabbitmq

# Or use Horizon (if installed)
php artisan horizon
```

## Step 9: Test Kafka

### Produce a Message
```php
use Junges\Kafka\Facades\Kafka;

Kafka::publish('kafka')
    ->onTopic('test-topic')
    ->withBody(['message' => 'Hello Kafka'])
    ->send();
```

### Consume Messages
```php
use Junges\Kafka\Facades\Kafka;

Kafka::consumer(['test-topic'])
    ->withHandler(function ($message) {
        // Handle message
        logger()->info('Received message', ['body' => $message->getBody()]);
    })
    ->build()
    ->consume();
```

## Troubleshooting

### PHP Extensions Not Found

**Check if extensions are loaded:**
```bash
php -m | grep -E "pgsql|redis|amqp|rdkafka"
```

**If missing, install:**
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-pgsql php8.2-redis php8.2-amqp php8.2-rdkafka

# macOS
pecl install redis amqp rdkafka
```

### Docker Services Not Starting

**Check Docker status:**
```bash
docker-compose ps
docker-compose logs [service_name]
```

**Restart services:**
```bash
docker-compose restart [service_name]
```

### Connection Failures

**Test connections:**
```bash
php artisan connections:test
```

**Check environment variables:**
```bash
php artisan config:show database
php artisan config:show queue
php artisan config:show kafka
```

### RabbitMQ Connection Issues

**Access Management UI:**
- URL: http://localhost:15672
- Username: guest
- Password: guest

**Check virtual host:**
- Default: `/`
- Can be changed via `RABBITMQ_VHOST`

### Kafka Connection Issues

**Verify Zookeeper is running:**
```bash
docker-compose ps zookeeper
```

**Check Kafka logs:**
```bash
docker-compose logs kafka
```

**Test Kafka connection:**
```bash
docker exec -it app038_kafka kafka-topics --bootstrap-server localhost:9092 --list
```

## Next Steps

1. Configure fallback connections (see `CONFIGURATION.md`)
2. Set up monitoring and alerts
3. Configure SSL/TLS for production
4. Set up backup strategies
5. Tune performance settings

## Additional Resources

- [CONFIGURATION.md](./CONFIGURATION.md) - Detailed configuration guide
- [ENV_SKELETON.md](./ENV_SKELETON.md) - Environment variables reference
- [docker-compose.yml](./docker-compose.yml) - Docker services configuration

