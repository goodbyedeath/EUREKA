# EUREKA Laravel Production Setup Guide

## Overview
This guide will help you deploy the EUREKA Laravel application to production with MySQL database.

## Prerequisites

### System Requirements
- PHP 8.1 or higher
- MySQL 8.0 or higher (or MariaDB 10.3+)
- Composer
- Node.js 18+ and NPM
- Web server (Apache/Nginx)

### PHP Extensions Required
```bash
php -m | grep -E "(pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo|gd)"
```

## Step-by-Step Production Deployment

### 1. Database Setup

#### Create MySQL Database and User
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE eureka_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user
CREATE USER 'eureka_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON eureka_production.* TO 'eureka_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

#### MySQL Performance Optimization
Add to your MySQL configuration file (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
# Performance optimizations for Laravel
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_size = 64M
query_cache_type = 1
max_connections = 200
```

### 2. Environment Configuration

#### Copy Production Environment
```bash
cp .env.production .env
```

#### Update Required Settings
Edit `.env` file and update:

```env
# Application
APP_URL=https://yourdomain.com

# Database (update with your actual values)
DB_PASSWORD=your_actual_secure_password

# Mail Configuration
MAIL_HOST=your-smtp-server.com
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Session (for your domain)
SESSION_DOMAIN=yourdomain.com
```

### 3. Run Deployment Script

```bash
# Make script executable
chmod +x deploy-production.sh

# Run deployment
./deploy-production.sh
```

### 4. Web Server Configuration

#### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /path/to/eureka/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Laravel configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Apache Configuration Example
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /path/to/eureka/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    <Directory /path/to/eureka/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
</VirtualHost>
```

### 5. SSL Certificate Setup

#### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test renewal
sudo certbot renew --dry-run
```

### 6. Process Management

#### Queue Worker with Supervisor
Create `/etc/supervisor/conf.d/eureka-worker.conf`:

```ini
[program:eureka-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/eureka/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/eureka/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start eureka-worker:*
```

### 7. Cron Jobs

Add to crontab for www-data user:
```bash
sudo crontab -u www-data -e

# Add this line
* * * * * cd /path/to/eureka && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Monitoring and Logging

#### Log Rotation
Create `/etc/logrotate.d/eureka`:

```
/path/to/eureka/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        php /path/to/eureka/artisan config:cache
    endscript
}
```

#### Health Check Script
```bash
#!/bin/bash
# /usr/local/bin/eureka-health-check.sh

cd /path/to/eureka
php artisan down --render="errors::503" --secret="your-secret-token" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "Application is up"
    exit 0
else
    echo "Application is down"
    exit 1
fi
```

### 9. Backup Strategy

#### Database Backup Script
```bash
#!/bin/bash
# /usr/local/bin/backup-eureka-db.sh

BACKUP_DIR="/backups/eureka"
DB_NAME="eureka_production"
DB_USER="eureka_user"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/eureka_db_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/eureka_db_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Database backup completed: eureka_db_$DATE.sql.gz"
```

Add to crontab:
```bash
# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-eureka-db.sh
```

### 10. Performance Optimization

#### Enable Redis (Optional but Recommended)
If you have Redis installed:

```env
# Update .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### PHP OPcache Configuration
Add to PHP configuration:

```ini
; /etc/php/8.1/fpm/conf.d/99-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.save_comments=1
opcache.enable_file_override=1
```

### 11. Security Checklist

- [ ] SSL certificate installed and configured
- [ ] Database user has minimal required privileges
- [ ] Strong passwords for all accounts
- [ ] Firewall configured (only ports 22, 80, 443 open)
- [ ] Regular security updates applied
- [ ] File permissions properly set (storage/ and bootstrap/cache/ writable)
- [ ] .env file not publicly accessible
- [ ] Error reporting disabled in production
- [ ] Debug mode disabled
- [ ] Rate limiting enabled

### 12. Testing Production Setup

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test cache
php artisan cache:store
php artisan cache:get test-key

# Test queue
php artisan queue:work --once

# Test application
curl -I https://yourdomain.com
```

### 13. Troubleshooting

#### Common Issues

1. **Permission Issues**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

2. **Database Connection Issues**
   - Check MySQL service: `sudo systemctl status mysql`
   - Verify credentials in `.env`
   - Test connection: `mysql -u eureka_user -p eureka_production`

3. **Asset Issues**
   ```bash
   npm run build
   php artisan storage:link
   ```

4. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Maintenance

### Regular Tasks
- Monitor disk space and logs
- Update dependencies monthly
- Review security patches
- Test backup restoration quarterly
- Monitor application performance

### Updates
```bash
# Before updating
php artisan down

# Update code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader
npm ci --production && npm run build

# Update database
php artisan migrate --force

# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bring application back up
php artisan up
```

---

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- EUREKA Application specific issues: Check application logs in `storage/logs/`

Happy deploying! 🚀