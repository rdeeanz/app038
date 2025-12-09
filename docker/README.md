# Docker Configuration

This directory contains optimized Dockerfiles for the Laravel backend and Svelte frontend applications.

## Structure

```
docker/
├── php/
│   ├── Dockerfile          # Laravel PHP-FPM multi-stage build
│   ├── nginx.conf          # Nginx main configuration
│   ├── default.conf        # Nginx server configuration
│   ├── supervisord.conf    # Supervisor configuration
│   └── .dockerignore       # Docker ignore file
└── svelte/
    ├── Dockerfile          # Svelte Nginx multi-stage build
    ├── nginx.conf          # Nginx main configuration
    ├── default.conf        # Nginx server configuration
    └── .dockerignore       # Docker ignore file
```

## Laravel Dockerfile

### Features

- **Multi-stage build** for optimized image size
- **Cached Composer dependencies** layer
- **PHP 8.2 FPM** with Alpine Linux
- **OPcache** enabled for production
- **Nginx** as reverse proxy
- **Supervisor** to manage PHP-FPM and Nginx
- **Health checks** configured

### Build

```bash
docker build -f docker/php/Dockerfile -t app038-laravel:latest .
```

### Run

```bash
docker run -d \
  --name app038-laravel \
  -p 8080:80 \
  -e DB_HOST=postgres \
  -e DB_DATABASE=app038 \
  -e DB_USERNAME=postgres \
  -e DB_PASSWORD=secret \
  app038-laravel:latest
```

## Svelte Dockerfile

### Features

- **Multi-stage build** with separate dependency and build stages
- **Cached npm dependencies** layer
- **Node.js 20** for building
- **Nginx Alpine** for serving static files
- **Optimized asset caching** with long-term cache headers
- **SPA routing** support
- **Health checks** configured

### Build

```bash
docker build -f docker/svelte/Dockerfile -t app038-svelte:latest .
```

### Run

```bash
docker run -d \
  --name app038-svelte \
  -p 80:80 \
  app038-svelte:latest
```

## Production Deployment

### Using Docker Compose

```bash
# Build and start all services
docker-compose -f docker-compose.prod.yml up -d --build

# View logs
docker-compose -f docker-compose.prod.yml logs -f

# Stop services
docker-compose -f docker-compose.prod.yml down
```

### Environment Variables

Create a `.env` file with the following variables:

```env
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app038
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
```

## Optimization Features

### Laravel Dockerfile

1. **Layer Caching**: Composer dependencies are installed in a separate stage and cached
2. **OPcache**: Enabled with production-optimized settings
3. **PHP-FPM Tuning**: Optimized process manager settings
4. **Nginx Compression**: Gzip enabled for text-based assets
5. **Security Headers**: X-Frame-Options, X-Content-Type-Options, etc.

### Svelte Dockerfile

1. **Layer Caching**: npm dependencies installed separately and cached
2. **Build Optimization**: Production build with minification
3. **Static Asset Caching**: Long-term cache headers for immutable assets
4. **Nginx Compression**: Gzip enabled
5. **SPA Routing**: All routes serve index.html for client-side routing

## Image Sizes

- **Laravel**: ~150MB (Alpine-based)
- **Svelte**: ~25MB (Nginx Alpine)

## Health Checks

Both containers include health checks:

- **Laravel**: `/health` endpoint
- **Svelte**: `/health` endpoint

Check health status:

```bash
docker ps
```

## Troubleshooting

### Laravel Container

```bash
# View logs
docker logs app038-laravel

# Execute commands
docker exec -it app038-laravel sh

# Check PHP-FPM status
docker exec app038-laravel php-fpm-healthcheck
```

### Svelte Container

```bash
# View logs
docker logs app038-svelte

# Execute commands
docker exec -it app038-svelte sh

# Test Nginx configuration
docker exec app038-svelte nginx -t
```

## Development vs Production

### Development

For development, use the existing `docker-compose.yml` which includes:
- Hot reloading
- Volume mounts
- Development dependencies

### Production

For production, use `docker-compose.prod.yml` which:
- Uses optimized builds
- No volume mounts (except for persistent data)
- Production dependencies only
- Health checks enabled

## CI/CD Integration

### Build and Push

```bash
# Build images
docker build -f docker/php/Dockerfile -t registry.example.com/app038-laravel:latest .
docker build -f docker/svelte/Dockerfile -t registry.example.com/app038-svelte:latest .

# Push to registry
docker push registry.example.com/app038-laravel:latest
docker push registry.example.com/app038-svelte:latest
```

### Kubernetes Deployment

The Dockerfiles are optimized for Kubernetes deployments with:
- Health checks for liveness/readiness probes
- Non-root user support (Nginx)
- Resource limits compatibility
- Stateless design (except Laravel storage volumes)

