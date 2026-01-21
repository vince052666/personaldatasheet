# Production Deployment Guide

## System Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Redis 6.0 or higher
- Nginx or Apache
- Composer 2.x
- Node.js 18.x or higher

## Pre-Deployment Checklist

### 1. Environment Configuration

```bash
# Copy production environment file
cp .env.production .env

# Update the following variables:
# - APP_KEY (generate with: php artisan key:generate)
# - APP_URL
# - DB_* (database credentials)
# - REDIS_* (Redis configuration)
# - MAIL_* (email configuration)
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Setup default permissions
php artisan permissions:setup

# Create default retention policies
php artisan tinker
>>> App\Models\DataRetentionPolicy::create(['resource_type' => 'pds', 'retention_years' => 7]);
```

### 4. Storage Configuration

```bash
# Create required directories
mkdir -p storage/app/backups
mkdir -p storage/app/exports
mkdir -p storage/app/uploads
mkdir -p storage/logs

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Queue Workers

```bash
# Install Supervisor for queue management
sudo apt-get install supervisor

# Create supervisor configuration
sudo nano /etc/supervisor/conf.d/pds-worker.conf
```

Supervisor configuration:
```ini
[program:pds-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

### 6. Scheduled Tasks

Add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Laravel Horizon (Optional but Recommended)

```bash
php artisan horizon:install
php artisan horizon:publish

# Add to supervisor
sudo nano /etc/supervisor/conf.d/horizon.conf
```

## Security Hardening

### 1. SSL/TLS Configuration

Ensure your web server is configured with valid SSL certificates:

```nginx
# Nginx example
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security headers are handled by middleware
    
    root /path/to/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. File Permissions

```bash
# Secure file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R ug+rwx storage bootstrap/cache
```

### 3. Disable Debug Mode

Ensure in `.env`:
```
APP_DEBUG=false
APP_ENV=production
```

## Backup Configuration

### Automated Daily Backups

Add to Laravel scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily database backup at 2 AM
    $schedule->command('backup:database --verify')
        ->dailyAt('02:00')
        ->onFailure(function () {
            // Notify admin
        });
    
    // Weekly file backup on Sunday at 3 AM
    $schedule->command('backup:files')
        ->weekly()
        ->sundays()
        ->at('03:00');
    
    // Clean old backups monthly
    $schedule->command('backup:clean --days=30')
        ->monthly();
    
    // Apply retention policies daily
    $schedule->command('retention:apply')
        ->daily();
    
    // Clean expired locks every hour
    $schedule->command('locks:clean')
        ->hourly();
}
```

## Monitoring & Maintenance

### Health Checks

Access health check endpoints:
- `/api/health` - Overall system health
- `/api/health/database` - Database connectivity
- `/api/health/cache` - Cache functionality
- `/api/health/queue` - Queue status
- `/api/health/storage` - Storage space

### Logs

Monitor application logs:
```bash
tail -f storage/logs/laravel.log
```

### Audit Chain Verification

Regularly verify audit log integrity:
```bash
php artisan audit:verify
```

## Performance Optimization

### Enable OPcache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Redis Configuration

In `redis.conf`:
```
maxmemory 2gb
maxmemory-policy allkeys-lru
```

### Database Optimization

```bash
# Optimize tables regularly
php artisan db:optimize
```

## Troubleshooting

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Restart Services

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all
```

## Post-Deployment Verification

1. ✅ Access application via HTTPS
2. ✅ Test user login
3. ✅ Verify health checks pass
4. ✅ Check queue workers are running
5. ✅ Confirm scheduled tasks execute
6. ✅ Test backup creation
7. ✅ Verify audit logging works
8. ✅ Check security headers present
9. ✅ Test API endpoints
10. ✅ Verify email notifications

## Support & Maintenance

For issues:
1. Check application logs
2. Verify environment configuration
3. Check queue worker status
4. Review security event logs
5. Contact system administrator

## Compliance Notes

This system implements:
- ✅ Data Privacy Act compliance
- ✅ CSC requirements for PDS management
- ✅ Government data retention policies (7 years)
- ✅ Immutable audit trails
- ✅ Role-based access control
- ✅ Encryption of sensitive data
