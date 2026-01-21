# Production Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Setup
- [ ] IONOS hosting account configured
- [ ] Database created and credentials obtained
- [ ] SSH access configured
- [ ] Domain/subdomain DNS configured
- [ ] SSL certificate installed

### 2. Secrets Configuration
Required GitHub Secrets:
```
IONOS_SSH_KEY          - Private SSH key for deployment
IONOS_HOST             - Server hostname
IONOS_USER             - SSH username
IONOS_PATH             - Application path on server
DB_HOST                - Database host
DB_DATABASE            - Database name
DB_USERNAME            - Database user
DB_PASSWORD            - Database password
STAGING_SSH_KEY        - Staging server SSH key
STAGING_HOST           - Staging server host
STAGING_USER           - Staging user
STAGING_PATH           - Staging path
```

### 3. Server Requirements
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Nginx or Apache
- Composer installed
- Node.js 18+ (for asset compilation)
- Redis (optional, for caching/queues)

## Deployment Steps

### Initial Deployment

1. **Clone Repository**
   ```bash
   git clone https://github.com/your-org/personaldatasheet.git /var/www/pds
   cd /var/www/pds
   ```

2. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure .env**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://pds.agency.gov.ph
   
   DB_CONNECTION=mysql
   DB_HOST=your-db-host
   DB_DATABASE=pds_production
   DB_USERNAME=pds_user
   DB_PASSWORD=secure-password
   
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   
   ALERTING_ENABLED=true
   ALERT_EMAIL_1=admin@agency.gov.ph
   ```

5. **Database Setup**
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=RolePermissionSeeder
   php artisan db:seed --class=RoleTemplateSeeder
   ```

6. **Set Permissions**
   ```bash
   chown -R www-data:www-data /var/www/pds
   chmod -R 755 /var/www/pds
   chmod -R 775 /var/www/pds/storage
   chmod -R 775 /var/www/pds/bootstrap/cache
   ```

7. **Optimize**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Setup Queue Worker**
   ```bash
   # Create systemd service
   sudo nano /etc/systemd/system/pds-worker.service
   ```
   
   ```ini
   [Unit]
   Description=PDS Queue Worker
   After=network.target
   
   [Service]
   Type=simple
   User=www-data
   WorkingDirectory=/var/www/pds
   ExecStart=/usr/bin/php /var/www/pds/artisan queue:work --sleep=3 --tries=3
   Restart=always
   
   [Install]
   WantedBy=multi-user.target
   ```
   
   ```bash
   sudo systemctl enable pds-worker
   sudo systemctl start pds-worker
   ```

9. **Setup Scheduler**
   ```bash
   crontab -e
   ```
   
   Add:
   ```
   * * * * * cd /var/www/pds && php artisan schedule:run >> /dev/null 2>&1
   ```

### Automated Deployment (GitHub Actions)

1. **Tag a Release**
   ```bash
   git tag -a v1.0.0 -m "Production release v1.0.0"
   git push origin v1.0.0
   ```

2. **Workflow Triggers**
   - Production deployment workflow runs automatically
   - Pre-deployment checks executed
   - Migration approval gate (if migrations detected)
   - Deployment to production
   - Smoke tests run
   - Automatic rollback on failure

### Manual Deployment

```bash
# On your local machine
./deploy-ionos.sh production

# Or step by step on server
ssh user@server
cd /var/www/pds
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## Post-Deployment

### 1. Verification
```bash
# Check health endpoint
curl https://pds.agency.gov.ph/health

# Verify services
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status pds-worker
```

### 2. Create Admin User
```bash
php artisan tinker
```

```php
$user = User::create([
    'name' => 'System Administrator',
    'email' => 'admin@agency.gov.ph',
    'password' => bcrypt('ChangeMe123!'),
    'agency_id' => 1,
]);
$user->assignRole('agency_admin');
```

### 3. Initial Configuration
- Log in as admin
- Configure agency settings
- Create user roles
- Set up approval workflows
- Configure retention policies

## Monitoring

### Health Checks
```bash
# Manual health check
php artisan health:check

# Automated (cron every 5 minutes)
*/5 * * * * cd /var/www/pds && php artisan health:check --notify
```

### Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log
```

## Backup

### Database Backup
```bash
# Daily automated backup
0 2 * * * /usr/local/bin/backup-pds-db.sh
```

### Application Backup
```bash
# Weekly backup
0 3 * * 0 tar -czf /backups/pds-$(date +\%Y\%m\%d).tar.gz /var/www/pds
```

## Rollback Procedure

### Using GitHub Actions
- Automatic rollback on deployment failure
- Latest backup restored automatically

### Manual Rollback
```bash
# Restore from backup
cd /var/www
tar -xzf /backups/pds-YYYYMMDD-HHMMSS.tar.gz

# Restore database
mysql -u pds_user -p pds_production < /backups/db-YYYYMMDD.sql

# Restart services
php artisan up
php artisan queue:restart
```

## Troubleshooting

### Site Not Accessible
1. Check web server: `systemctl status nginx`
2. Check PHP-FPM: `systemctl status php8.2-fpm`
3. Check logs: `tail -f /var/log/nginx/error.log`

### Database Connection Failed
1. Check MySQL: `systemctl status mysql`
2. Verify credentials in `.env`
3. Test connection: `mysql -u pds_user -p`

### Queue Not Processing
1. Check worker: `systemctl status pds-worker`
2. Restart: `systemctl restart pds-worker`
3. Check failed jobs: `php artisan queue:failed`

## Security Hardening

### File Permissions
```bash
# Only web server should write to storage and cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Environment File
```bash
# Protect .env file
chmod 600 .env
```

### Disable Debug Mode
Ensure in `.env`:
```
APP_DEBUG=false
```

### Enable HTTPS Only
In `.env`:
```
SESSION_SECURE_COOKIE=true
```

## Maintenance

### Updates
```bash
# Put site in maintenance mode
php artisan down

# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan optimize

# Bring site back up
php artisan up
```

### Data Retention
```bash
# Dry run to see what would be deleted
php artisan data:retention --dry-run

# Apply retention policies
php artisan data:retention
```

## Support

For issues or questions:
- Email: support@agency.gov.ph
- Documentation: https://docs.pds.agency.gov.ph
- GitHub Issues: https://github.com/your-org/personaldatasheet/issues
