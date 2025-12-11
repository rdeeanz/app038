# 🚨 TROUBLESHOOTING: Port 8080 Already in Use

## 🔍 Error Analysis

**Error:** `failed to bind host port 0.0.0.0:8080/tcp: address already in use`

**Cause:** Another process is already using port 8080, preventing the Laravel container from starting.

---

## 🛠️ SOLUTION STEPS

### Step 1: Identify What's Using Port 8080

```bash
# Check what process is using port 8080
sudo lsof -i :8080
# Or use netstat
sudo netstat -tulpn | grep :8080
# Or use ss
sudo ss -tulpn | grep :8080
```

**Expected output will show the process using port 8080.**

### Step 2: Stop the Conflicting Process

**Option A: If it's another Docker container**
```bash
# List all running containers
docker ps

# Stop any container using port 8080
docker stop <container_name_or_id>

# Or stop all containers if needed
docker stop $(docker ps -q)
```

**Option B: If it's a system service**
```bash
# Find the process ID (PID) from Step 1 output
sudo kill -9 <PID>

# Or if it's a known service (like Apache, Nginx, etc.)
sudo systemctl stop apache2  # if Apache is running
sudo systemctl stop nginx    # if Nginx is running on 8080
```

**Option C: If it's a previous deployment**
```bash
# Stop all app038 containers
docker-compose -f docker-compose.prod.yml down

# Remove any orphaned containers
docker container prune -f

# Check if port is free now
sudo lsof -i :8080
```

### Step 3: Restart Docker Services

```bash
# Navigate to project directory
cd /var/www/app038

# Start services again
docker-compose -f docker-compose.prod.yml up -d

# Check container status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep app038
```

---

## 🔧 ALTERNATIVE SOLUTIONS

### Solution A: Change Laravel Container Port

If you can't free port 8080, modify the docker-compose file:

```bash
# Edit docker-compose.prod.yml
nano docker-compose.prod.yml

# Change the Laravel service ports from:
# ports:
#   - "8080:80"
# To:
# ports:
#   - "8081:80"  # or any available port

# Update Nginx configuration accordingly
sudo nano /etc/nginx/sites-available/app038

# Change proxy_pass from:
# proxy_pass http://127.0.0.1:8080;
# To:
# proxy_pass http://127.0.0.1:8081;

# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx

# Restart Docker services
docker-compose -f docker-compose.prod.yml up -d
```

### Solution B: Use Different Port Temporarily

```bash
# Quick fix: Use port 8081
sed -i 's/8080:80/8081:80/g' docker-compose.prod.yml

# Update Nginx proxy
sudo sed -i 's/127.0.0.1:8080/127.0.0.1:8081/g' /etc/nginx/sites-available/app038

# Reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# Start containers
docker-compose -f docker-compose.prod.yml up -d
```

---

## 🧪 VERIFICATION STEPS

### Step 1: Check Container Status
```bash
# All containers should be running
docker ps | grep app038

# Expected output:
# app038_laravel    Up (healthy)    0.0.0.0:8080->80/tcp (or 8081)
# app038_postgres   Up (healthy)    5432/tcp
# app038_redis      Up (healthy)    6379/tcp
# app038_rabbitmq   Up (healthy)    5672/tcp
```

### Step 2: Test Health Endpoint
```bash
# Test container directly (use correct port)
curl http://localhost:8080/up
# Or if using port 8081:
curl http://localhost:8081/up

# Expected: "healthy" response
```

### Step 3: Test via Nginx
```bash
# Test through Nginx proxy
curl http://localhost/up

# Expected: "healthy" response
```

### Step 4: Test Website Access
```bash
# Test main page
curl -I http://localhost/

# Expected: HTTP 200 or 302 response
```

---

## 🚀 QUICK FIX COMMANDS

**Copy and paste this complete fix:**

```bash
# Step 1: Stop all containers and free port 8080
docker-compose -f docker-compose.prod.yml down
docker stop $(docker ps -q) 2>/dev/null || true
sudo pkill -f ":8080" 2>/dev/null || true

# Step 2: Check if port is free
if sudo lsof -i :8080 >/dev/null 2>&1; then
    echo "⚠️ Port 8080 still in use, switching to 8081"
    sed -i 's/8080:80/8081:80/g' docker-compose.prod.yml
    sudo sed -i 's/127.0.0.1:8080/127.0.0.1:8081/g' /etc/nginx/sites-available/app038
    sudo nginx -t && sudo systemctl reload nginx
    echo "✅ Switched to port 8081"
else
    echo "✅ Port 8080 is now free"
fi

# Step 3: Start containers
docker-compose -f docker-compose.prod.yml up -d

# Step 4: Wait and verify
sleep 15
docker ps | grep app038
curl -s http://localhost/up && echo "✅ Website is working!"
```

---

## 🆘 IF STILL NOT WORKING

### Check System Resources
```bash
# Check available memory
free -h

# Check disk space
df -h

# Check Docker status
sudo systemctl status docker
```

### Check Docker Logs
```bash
# Check Laravel container logs
docker logs app038_laravel --tail 50

# Check all container logs
docker-compose -f docker-compose.prod.yml logs --tail=20
```

### Complete Reset (Last Resort)
```bash
# Stop everything
docker-compose -f docker-compose.prod.yml down
docker system prune -a --volumes -f

# Restart Docker service
sudo systemctl restart docker

# Start fresh
docker-compose -f docker-compose.prod.yml up -d --build
```

---

## 📞 NEXT STEPS

1. **Run the Quick Fix Commands** above
2. **Verify all containers are running** with `docker ps`
3. **Test website access** at `http://168.231.118.3`
4. **If still issues**, check the logs with `docker logs app038_laravel`

**The website should be accessible at `http://168.231.118.3` after fixing the port conflict.**