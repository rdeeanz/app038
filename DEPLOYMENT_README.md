# 🚀 App038 Deployment ke VPS Hostinger

## 📋 Ringkasan

Aplikasi **App038** adalah aplikasi Laravel 11 LTS dengan Inertia.js + Svelte yang siap untuk deployment ke VPS Hostinger dengan Ubuntu 24.04.

### 🏗️ Arsitektur
- **Backend**: Laravel 11 LTS (PHP 8.2+)
- **Frontend**: Svelte 4 + Inertia.js (Monolith)
- **Database**: PostgreSQL 15
- **Cache**: Redis 7
- **Queue**: RabbitMQ 3
- **Web Server**: Nginx (reverse proxy)
- **Container**: Docker + Docker Compose

## 🚀 Quick Deployment

### Metode 1: Automated Script (Recommended)

```bash
# SSH ke VPS Hostinger
ssh root@168.231.118.3

# Download dan jalankan script otomatis
wget https://raw.githubusercontent.com/rdeeanz/app038/main/deploy-hostinger-complete.sh
chmod +x deploy-hostinger-complete.sh
sudo ./deploy-hostinger-complete.sh
```

Script ini akan otomatis:
- ✅ Install Docker & Docker Compose
- ✅ Setup firewall (UFW)
- ✅ Clone repository
- ✅ Generate environment variables & passwords
- ✅ Install Node.js & build Vite assets
- ✅ Build & start Docker containers
- ✅ Setup database & migrations
- ✅ Configure Nginx reverse proxy
- ✅ Optimize Laravel application
- ✅ Setup auto-start on boot
- ✅ Verify deployment

### Metode 2: Manual Step-by-Step

Ikuti panduan lengkap di [DEPLOY_HOSTINGER.md](./DEPLOY_HOSTINGER.md)

## 🌐 Akses Website

Setelah deployment selesai:
- **Via IP**: http://168.231.118.3
- **Via Domain**: https://vibeapps.cloud (setelah DNS setup)

## 📋 Informasi VPS

- **IP Address**: 168.231.118.3
- **Hostname**: srv1162366.hstgr.cloud
- **OS**: Ubuntu 24.04 LTS
- **Resources**: 2 CPUs, 8GB RAM, 100GB Disk
- **Domain**: vibeapps.cloud

## 🔧 Post-Deployment

### SSL Certificate (Opsional)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d vibeapps.cloud -d www.vibeapps.cloud

# Test auto-renewal
sudo certbot renew --dry-run
```

### Maintenance Commands
```bash
# Check containers
docker ps

# View logs
docker logs app038_laravel -f

# Restart services
sudo systemctl restart app038

# Update application
cd /var/www/app038
git pull origin main
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

## 📁 File Structure

```
/var/www/app038/
├── app/                    # Laravel application
├── resources/js/           # Svelte components
├── public/build/          # Vite build assets
├── docker/                # Docker configurations
├── .env                   # Environment variables
├── docker-compose.prod.yml # Production Docker Compose
└── deploy-hostinger-complete.sh # Deployment script
```

## 🆘 Troubleshooting

### Common Issues

**1. Container tidak start**
```bash
docker logs app038_laravel
docker-compose -f docker-compose.prod.yml restart
```

**2. Website tidak bisa diakses**
```bash
# Check Nginx
sudo systemctl status nginx
sudo tail -f /var/log/nginx/app038-error.log

# Check firewall
sudo ufw status

# Test health endpoint
curl http://localhost/up
```

**3. Database connection error**
```bash
# Check PostgreSQL container
docker logs app038_postgres

# Check environment variables
grep DB_ /var/www/app038/.env
```

## 📞 Support

Untuk bantuan lebih lanjut, check:
- [DEPLOY_HOSTINGER.md](./DEPLOY_HOSTINGER.md) - Panduan lengkap
- Container logs: `docker logs app038_laravel`
- Nginx logs: `sudo tail -f /var/log/nginx/app038-error.log`

---

**🎉 Happy Deploying!**