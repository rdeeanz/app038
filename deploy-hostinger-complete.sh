#!/bin/bash

# =============================================================================
# COMPLETE DEPLOYMENT SCRIPT FOR HOSTINGER VPS
# App038 - Laravel 11 + Inertia.js + Svelte
# Ubuntu 24.04 LTS
# =============================================================================

set -e  # Exit on any error

echo "🚀 Starting App038 deployment to Hostinger VPS..."
echo "📅 $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root (use sudo)"
    exit 1
fi

# =============================================================================
# STEP 1: SYSTEM PREPARATION
# =============================================================================

print_info "Step 1: System Preparation"

# Update system
print_info "Updating system packages..."
apt update -y

# Install basic tools
print_info "Installing basic tools..."
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release htop nano

print_status "System preparation completed"

# =============================================================================
# STEP 2: INSTALL DOCKER & DOCKER COMPOSE
# =============================================================================

print_info "Step 2: Installing Docker & Docker Compose"

# Check if Docker is already installed
if command -v docker &> /dev/null; then
    print_warning "Docker already installed, skipping installation"
else
    print_info "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
fi

# Start Docker service
systemctl start docker
systemctl enable docker

# Install Docker Compose if not exists
if command -v docker-compose &> /dev/null; then
    print_warning "Docker Compose already installed, skipping installation"
else
    print_info "Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
fi

# Verify installations
docker --version
docker-compose --version

print_status "Docker & Docker Compose installed successfully"

# =============================================================================
# STEP 3: SETUP FIREWALL
# =============================================================================

print_info "Step 3: Setting up firewall"

# Install UFW if not exists
if ! command -v ufw &> /dev/null; then
    apt install ufw -y
fi

# Configure firewall
ufw --force reset
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw --force enable

print_status "Firewall configured successfully"

# =============================================================================
# STEP 4: CLONE REPOSITORY
# =============================================================================

print_info "Step 4: Cloning repository"

# Create directory
mkdir -p /var/www
cd /var/www

# Remove existing directory if exists
if [ -d "app038" ]; then
    print_warning "Existing app038 directory found, removing..."
    rm -rf app038
fi

# Clone repository
print_info "Cloning repository..."
echo "Please choose authentication method:"
echo "1) HTTPS with Personal Access Token (Recommended)"
echo "2) SSH Key"
echo "3) Public repository (no authentication)"
read -p "Enter choice (1-3): " auth_choice

case $auth_choice in
    1)
        read -p "Enter your GitHub Personal Access Token: " token
        git clone https://${token}@github.com/rdeeanz/app038.git
        ;;
    2)
        print_info "Make sure you have added your SSH key to GitHub"
        git clone git@github.com:rdeeanz/app038.git
        ;;
    3)
        git clone https://github.com/rdeeanz/app038.git
        ;;
    *)
        print_error "Invalid choice"
        exit 1
        ;;
esac

cd app038

print_status "Repository cloned successfully"

# =============================================================================
# STEP 5: SETUP ENVIRONMENT VARIABLES
# =============================================================================

print_info "Step 5: Setting up environment variables"

# Create .env file from example
if [ -f .env.example ]; then
    cp .env.example .env
    print_status ".env file created from .env.example"
else
    print_error ".env.example not found!"
    exit 1
fi

# Generate secure passwords
print_info "Generating secure passwords..."
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
RABBITMQ_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Generate APP_KEY
print_info "Generating APP_KEY..."
APP_KEY_VALUE=$(openssl rand -base64 32)
APP_KEY="base64:${APP_KEY_VALUE}"

# Update .env file
sed -i "s/DB_PASSWORD=$/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i "s/REDIS_PASSWORD=$/REDIS_PASSWORD=$REDIS_PASSWORD/" .env
sed -i "s/RABBITMQ_PASSWORD=$/RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD/" .env
sed -i "s/APP_KEY=$/APP_KEY=$APP_KEY/" .env

# Update APP_URL
read -p "Enter your domain name (or press Enter to use IP 168.231.118.3): " domain_name
if [ -z "$domain_name" ]; then
    sed -i "s|APP_URL=http://168.231.118.3|APP_URL=http://168.231.118.3|" .env
    print_status "APP_URL set to: http://168.231.118.3"
else
    sed -i "s|APP_URL=http://168.231.118.3|APP_URL=https://$domain_name|" .env
    print_status "APP_URL set to: https://$domain_name"
fi

# Save passwords securely
echo "DB_PASSWORD: $DB_PASSWORD" > /root/app038-passwords.txt
echo "REDIS_PASSWORD: $REDIS_PASSWORD" >> /root/app038-passwords.txt
echo "RABBITMQ_PASSWORD: $RABBITMQ_PASSWORD" >> /root/app038-passwords.txt
echo "APP_KEY: $APP_KEY" >> /root/app038-passwords.txt
chmod 600 /root/app038-passwords.txt

print_status "Environment variables configured successfully"
print_info "Passwords saved to: /root/app038-passwords.txt"

# =============================================================================
# STEP 6: INSTALL NODE.JS & BUILD ASSETS
# =============================================================================

print_info "Step 6: Installing Node.js and building assets"

# Install Node.js 20.x
print_info "Installing Node.js 20.x..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Verify installation
node --version
npm --version

# Install dependencies and build assets
print_info "Installing npm dependencies..."
npm install

print_info "Building Vite assets for production..."
npm run build

# Verify build output
if [ -d "public/build" ]; then
    print_status "Vite assets built successfully"
    ls -la public/build/
else
    print_error "Vite build failed - public/build directory not found"
    exit 1
fi

# =============================================================================
# STEP 7: BUILD & START DOCKER SERVICES
# =============================================================================

print_info "Step 7: Building and starting Docker services"

# Create Docker network
print_info "Creating Docker network..."
docker network create app038_network 2>/dev/null || print_warning "Network already exists"

# Build Docker images
print_info "Building Docker images..."
docker-compose -f docker-compose.prod.yml build --no-cache

# Start services
print_info "Starting Docker services..."
docker-compose -f docker-compose.prod.yml up -d

# Wait for services to start
print_info "Waiting for services to start..."
sleep 30

# Check container status
print_info "Checking container status..."
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038

print_status "Docker services started successfully"

# =============================================================================
# STEP 8: SETUP DATABASE
# =============================================================================

print_info "Step 8: Setting up database"

# Wait for database to be ready
print_info "Waiting for PostgreSQL to be ready..."
sleep 10

# Run migrations
print_info "Running database migrations..."
docker exec app038_laravel php artisan migrate --force

# Run seeders (optional)
read -p "Do you want to run database seeders? (y/N): " run_seeders
if [[ $run_seeders =~ ^[Yy]$ ]]; then
    print_info "Running database seeders..."
    docker exec app038_laravel php artisan db:seed --force
fi

print_status "Database setup completed"

# =============================================================================
# STEP 9: SETUP NGINX REVERSE PROXY
# =============================================================================

print_info "Step 9: Setting up Nginx reverse proxy"

# Install Nginx
if ! command -v nginx &> /dev/null; then
    print_info "Installing Nginx..."
    apt install nginx -y
else
    print_warning "Nginx already installed"
fi

# Create Nginx configuration
print_info "Creating Nginx configuration..."
cat > /etc/nginx/sites-available/app038 << 'EOF'
# App038 Nginx Configuration
server {
    listen 80;
    listen [::]:80;
    server_name _;

    # Logging
    access_log /var/log/nginx/app038-access.log;
    error_log /var/log/nginx/app038-error.log;

    # Client body size
    client_max_body_size 20M;

    # Proxy to Laravel container
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
        proxy_temp_file_write_size 256k;
        proxy_max_temp_file_size 0;
    }

    # Health check endpoint
    location /up {
        proxy_pass http://127.0.0.1:8080/up;
        access_log off;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/app038 /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test configuration
nginx -t

# Start Nginx
systemctl start nginx
systemctl enable nginx
systemctl reload nginx

print_status "Nginx configured successfully"

# =============================================================================
# STEP 10: OPTIMIZE LARAVEL
# =============================================================================

print_info "Step 10: Optimizing Laravel application"

# Clear and optimize cache
print_info "Clearing and optimizing cache..."
docker exec app038_laravel php artisan config:clear
docker exec app038_laravel php artisan cache:clear
docker exec app038_laravel php artisan view:clear
docker exec app038_laravel php artisan config:cache
docker exec app038_laravel php artisan route:cache

print_status "Laravel optimization completed"

# =============================================================================
# STEP 11: SETUP AUTO-START ON BOOT
# =============================================================================

print_info "Step 11: Setting up auto-start on boot"

# Create systemd service
cat > /etc/systemd/system/app038.service << EOF
[Unit]
Description=App038 Docker Compose
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/var/www/app038
ExecStart=/usr/local/bin/docker-compose -f docker-compose.prod.yml up -d
ExecStop=/usr/local/bin/docker-compose -f docker-compose.prod.yml down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

# Enable service
systemctl daemon-reload
systemctl enable app038.service

print_status "Auto-start configured successfully"

# =============================================================================
# STEP 12: FINAL VERIFICATION
# =============================================================================

print_info "Step 12: Final verification"

# Check all services
print_info "Checking all services..."
echo ""
echo "=== Container Status ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038
echo ""

# Test health endpoint
print_info "Testing health endpoint..."
sleep 5

# Test from container directly
container_health=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/up)
if [ "$container_health" = "200" ]; then
    print_status "Container health check: OK"
else
    print_error "Container health check failed (HTTP $container_health)"
fi

# Test from Nginx
nginx_health=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up)
if [ "$nginx_health" = "200" ]; then
    print_status "Nginx proxy health check: OK"
else
    print_error "Nginx proxy health check failed (HTTP $nginx_health)"
fi

# Test main page
main_page=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$main_page" = "200" ] || [ "$main_page" = "302" ]; then
    print_status "Main page accessible: OK"
else
    print_error "Main page not accessible (HTTP $main_page)"
fi

# =============================================================================
# DEPLOYMENT COMPLETED
# =============================================================================

echo ""
echo "🎉 ============================================="
echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "🎉 ============================================="
echo ""
print_status "Website is now accessible at:"
echo "   • Via IP: http://168.231.118.3"
if [ ! -z "$domain_name" ]; then
    echo "   • Via Domain: https://$domain_name (after DNS propagation)"
fi
echo ""
print_info "Important files:"
echo "   • Passwords: /root/app038-passwords.txt"
echo "   • Nginx config: /etc/nginx/sites-available/app038"
echo "   • Application: /var/www/app038"
echo ""
print_info "Useful commands:"
echo "   • Check containers: docker ps"
echo "   • View logs: docker logs app038_laravel"
echo "   • Restart services: systemctl restart app038"
echo "   • Check Nginx: systemctl status nginx"
echo ""

# Setup SSL if domain is provided
if [ ! -z "$domain_name" ]; then
    echo ""
    read -p "Do you want to setup SSL certificate with Let's Encrypt? (y/N): " setup_ssl
    if [[ $setup_ssl =~ ^[Yy]$ ]]; then
        print_info "Setting up SSL certificate..."
        
        # Install Certbot
        apt install certbot python3-certbot-nginx -y
        
        # Get SSL certificate
        certbot --nginx -d $domain_name --non-interactive --agree-tos --email admin@$domain_name
        
        # Test auto-renewal
        certbot renew --dry-run
        
        print_status "SSL certificate configured successfully"
        print_status "Website is now accessible at: https://$domain_name"
    fi
fi

echo ""
print_status "Deployment completed at $(date)"
print_info "Check the website in your browser: http://168.231.118.3"
echo ""