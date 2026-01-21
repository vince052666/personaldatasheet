# PDS Management System - Deployment Guide

## IONOS Hosting Deployment

This guide covers deploying the PDS Management System to IONOS hosting.

### Prerequisites

1. IONOS hosting account with SSH access
2. PHP 8.2 or higher
3. Composer installed on server
4. MySQL/MariaDB database
5. SSL certificate configured

### Deployment Steps

#### 1. Prepare Your IONOS Server

```bash
# Connect to your IONOS server
ssh your_username@your_server.ionos.com

# Navigate to your web root
cd /path/to/your/web/root

# Create application directory
mkdir pds-system
cd pds-system
```

#### 2. Configure Environment Variables

Create `.env` file with the following configuration:

```env
APP_NAME="PDS Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.ionos.com
MAIL_PORT=587
MAIL_USERNAME=your_email@your-domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration (for background jobs)
QUEUE_CONNECTION=database

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache Configuration
CACHE_STORE=database
```

#### 3. Deploy Using Script

```bash
# From your local machine, run:
chmod +x deploy-ionos.sh
./deploy-ionos.sh
```

#### 4. Manual Deployment (Alternative)

If you prefer manual deployment:

```bash
# 1. Upload files via FTP/SFTP or Git
git clone https://github.com/your-username/personaldatasheet.git .

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 storage bootstrap/cache

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate --force

# 6. Seed database
php artisan db:seed --class=RolePermissionSeeder

# 7. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Post-Deployment Configuration

#### 1. Create Admin User

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('secure-password'),
    'is_active' => true,
]);

$adminRole = \App\Models\Role::where('slug', 'admin')->first();
$user->roles()->attach($adminRole);
```

#### 2. Configure Cron Jobs

Add to crontab:

```bash
* * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
```

#### 3. Configure Queue Worker

For background jobs, set up a supervisor configuration:

```ini
[program:pds-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600
```

### SSL Certificate Setup

#### Using Let's Encrypt (Recommended)

```bash
# Install certbot
apt-get install certbot python3-certbot-apache

# Obtain certificate
certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal is set up automatically
```

### Database Backup

Create a backup script:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"
DB_NAME="your_database_name"
DB_USER="your_database_user"
DB_PASS="your_database_password"

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/pds_backup_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "pds_backup_*.sql.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /path/to/backup-script.sh
```

### Troubleshooting

#### Permission Issues

```bash
# Reset permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### View Logs

```bash
tail -f storage/logs/laravel.log
```

### Monitoring

Set up monitoring for:
- Application uptime
- Database performance
- Queue jobs status
- Disk space usage
- Error rates

### Security Checklist

- [ ] SSL certificate installed and working
- [ ] `.env` file has proper permissions (600)
- [ ] Database credentials are secure
- [ ] APP_DEBUG is set to false
- [ ] File upload directory has proper permissions
- [ ] Regular backups are configured
- [ ] Firewall rules are configured
- [ ] Rate limiting is enabled
- [ ] CORS is properly configured
- [ ] Security headers are set

### Support

For issues or questions:
- Email: support@your-domain.com
- GitHub Issues: https://github.com/your-username/personaldatasheet/issues
