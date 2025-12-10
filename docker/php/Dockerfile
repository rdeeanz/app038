# Multi-stage build for Laravel PHP-FPM application
# Stage 1: Composer dependencies
FROM php:8.2-fpm-alpine AS composer

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (cached layer)
# Use --no-autoloader because autoloader needs app files which aren't available yet
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Stage 2: Application build
FROM php:8.2-fpm-alpine AS app

# Install build dependencies and PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
    && apk del .build-deps

# Install runtime dependencies
RUN apk add --no-cache \
    libpng \
    libzip \
    oniguruma \
    postgresql-libs \
    icu-libs \
    nginx \
    supervisor \
    wget

# Configure PHP-FPM
RUN { \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 127.0.0.1:9000'; \
    echo 'listen.owner = www-data'; \
    echo 'listen.group = www-data'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 50'; \
    echo 'pm.start_servers = 5'; \
    echo 'pm.min_spare_servers = 5'; \
    echo 'pm.max_spare_servers = 35'; \
    echo 'pm.max_requests = 500'; \
    echo 'pm.process_idle_timeout = 10s'; \
    echo 'pm.status_path = /status'; \
    echo 'ping.path = /ping'; \
} > /usr/local/etc/php-fpm.d/www.conf

# Configure PHP
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 20M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'date.timezone = UTC'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Configure OPcache for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /app

# Copy composer files first
COPY --from=composer /app/composer.json /app/composer.lock ./

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Composer dependencies from previous stage
COPY --from=composer /app/vendor /app/vendor

# Copy application files (needed for autoloader generation of App namespace)
COPY . .

# Ensure required directories exist for Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p bootstrap/cache

# Generate optimized autoloader (after app files are copied)
# This ensures App namespace and other PSR-4 namespaces are properly mapped
# Use --no-scripts to avoid running post-autoload-dump scripts that may require env vars
# Add --ignore-platform-reqs to skip platform check issues
# Try multiple approaches if one fails
RUN (composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs --apcu 2>&1 || \
     composer dump-autoload --no-dev --no-scripts --ignore-platform-reqs 2>&1 || \
     echo "Warning: composer dump-autoload failed, will regenerate at runtime") && \
    echo "Autoloader generation completed"

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Configure Nginx
COPY docker/php/nginx.conf /etc/nginx/nginx.conf
COPY docker/php/default.conf /etc/nginx/http.d/default.conf

# Configure Supervisor
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create Supervisor directories and set permissions
RUN mkdir -p /var/log/supervisor /var/run \
    && chmod -R 755 /var/log/supervisor /var/run

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

# Start supervisor (manages PHP-FPM and Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

