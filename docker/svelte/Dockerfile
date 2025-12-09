# Multi-stage build for Svelte frontend application
# Stage 1: Dependencies installation
FROM node:20-alpine AS deps

# Install build dependencies
RUN apk add --no-cache \
    python3 \
    make \
    g++

# Set working directory
WORKDIR /app

# Copy package files
COPY package.json package-lock.json* ./

# Install dependencies (cached layer)
RUN npm ci --only=production --ignore-scripts && \
    npm cache clean --force

# Stage 2: Build application
FROM node:20-alpine AS builder

# Install build dependencies
RUN apk add --no-cache \
    python3 \
    make \
    g++

# Set working directory
WORKDIR /app

# Copy package files
COPY package.json package-lock.json* ./

# Install all dependencies (including dev dependencies for build)
RUN npm ci --ignore-scripts && \
    npm cache clean --force

# Copy application source
COPY . .

# Copy production dependencies from deps stage
COPY --from=deps /app/node_modules ./node_modules

# Build application
RUN npm run build

# Stage 3: Production with Nginx
FROM nginx:alpine AS production

# Install Node.js for runtime checks (optional, can be removed)
RUN apk add --no-cache nodejs

# Remove default nginx website
RUN rm -rf /usr/share/nginx/html/*

# Copy built assets from builder stage
# Vite outputs to public/build for Laravel integration
# For standalone Svelte, copy the entire public directory
COPY --from=builder /app/public /usr/share/nginx/html

# Copy Nginx configuration
COPY docker/svelte/nginx.conf /etc/nginx/nginx.conf
COPY docker/svelte/default.conf /etc/nginx/conf.d/default.conf

# Create nginx cache directories
RUN mkdir -p /var/cache/nginx/client_temp \
    /var/cache/nginx/proxy_temp \
    /var/cache/nginx/fastcgi_temp \
    /var/cache/nginx/uwsgi_temp \
    /var/cache/nginx/scgi_temp

# Set permissions
RUN chown -R nginx:nginx /usr/share/nginx/html \
    && chown -R nginx:nginx /var/cache/nginx \
    && chmod -R 755 /usr/share/nginx/html

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]

