# Quick Start Guide - PDS Management System

## For Developers

### Initial Setup
```bash
# Clone and install
git clone <repository-url>
cd personaldatasheet
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan permissions:setup

# Build assets
npm run build
```

### Development Server
```bash
# Option 1: Use the dev script
composer dev

# Option 2: Manual start
php artisan serve &
php artisan queue:work &
npm run dev
```

### Running Tests
```bash
php artisan test
```

## For System Administrators

### Production Deployment

1. **Server Requirements**
   - PHP 8.2+
   - MySQL 8.0+
   - Redis 6.0+
   - Nginx/Apache
   - Composer 2.x
   - Node.js 18.x+

2. **Quick Deploy**
   ```bash
   # Copy production environment
   cp .env.production .env
   
   # Edit .env with your values
   nano .env
   
   # Install dependencies
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   
   # Database setup
   php artisan migrate --force
   php artisan permissions:setup
   
   # Set permissions
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

3. **Configure Queue Workers**
   ```bash
   # Install Supervisor
   sudo apt-get install supervisor
   
   # Add configuration (see PRODUCTION_DEPLOYMENT.md)
   sudo nano /etc/supervisor/conf.d/pds-worker.conf
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start pds-worker:*
   ```

4. **Setup Scheduled Tasks**
   ```bash
   # Add to crontab
   crontab -e
   # Add: * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```

## Common Tasks

### Backup Database
```bash
php artisan backup:database --verify
```

### Restore from Backup
```bash
php artisan tinker
>>> $service = app(App\Services\BackupService::class);
>>> $service->restoreDatabase('/path/to/backup.sql.gz');
```

### Verify Audit Integrity
```bash
php artisan audit:verify
```

### Rank Candidates
```bash
php artisan recruitment:rank {qualification_standard_id}
```

### Clean Expired Locks
```bash
php artisan locks:clean
```

### Apply Retention Policies
```bash
php artisan retention:apply
```

## API Quick Reference

### Authentication
```bash
# Get token (assumes Sanctum)
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

# Use token in subsequent requests
Authorization: Bearer {token}
```

### Health Check
```bash
curl https://your-domain.com/api/health
```

### Submit PDS for Approval
```bash
POST /api/v1/approvals/{pds_id}/submit
Authorization: Bearer {token}
{
  "assigned_to": 5
}
```

### Rank Candidates
```bash
POST /api/v1/recruitment/rank/{qualification_standard_id}
Authorization: Bearer {token}
```

### Give Privacy Consent
```bash
POST /api/v1/privacy/consent
Authorization: Bearer {token}
{
  "consent_type": "data_processing",
  "version": "1.0"
}
```

## Monitoring

### Check System Health
- Overall: `curl https://your-domain.com/api/health`
- Database: `curl https://your-domain.com/api/health/database`
- Cache: `curl https://your-domain.com/api/health/cache`
- Queue: `curl https://your-domain.com/api/health/queue`
- Storage: `curl https://your-domain.com/api/health/storage`

### View Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs
sudo tail -f /path/to/storage/logs/worker.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log
```

### Check Queue Status
```bash
# If using Horizon
php artisan horizon:list

# Check Supervisor
sudo supervisorctl status pds-worker:*
```

## Troubleshooting

### Issue: "Class not found"
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: "Queue not processing"
```bash
# Check worker status
sudo supervisorctl status pds-worker:*

# Restart workers
sudo supervisorctl restart pds-worker:*

# Check failed jobs
php artisan queue:failed
```

### Issue: "Permission denied"
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue: "Database connection failed"
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials in .env
cat .env | grep DB_
```

## Security Checklist

Before going live:
- [ ] Set `APP_DEBUG=false`
- [ ] Generate strong `APP_KEY`
- [ ] Configure HTTPS with valid SSL
- [ ] Set secure session cookie settings
- [ ] Configure firewall rules
- [ ] Set strong database passwords
- [ ] Enable Redis password
- [ ] Review security headers
- [ ] Test backup and restore
- [ ] Verify audit logging works

## Getting Help

1. **Documentation**
   - `IMPLEMENTATION_COMPLETE.md` - Full features list
   - `ENHANCED_FEATURES.md` - Feature usage guide
   - `PRODUCTION_DEPLOYMENT.md` - Detailed deployment
   - `SECURITY_ADVISORY.md` - Security best practices

2. **Logs**
   - Application: `storage/logs/laravel.log`
   - Queue: `storage/logs/worker.log`
   - Web server: `/var/log/nginx/` or `/var/log/apache2/`

3. **Database**
   - Security events: `SELECT * FROM security_events WHERE resolved = 0`
   - Activity logs: `SELECT * FROM activity_logs ORDER BY performed_at DESC LIMIT 50`
   - Failed jobs: `SELECT * FROM failed_jobs`

## Default Credentials (Change Immediately!)

After running migrations, create admin user:
```bash
php artisan tinker
>>> $user = App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('ChangeMe123!')]);
>>> $adminRole = App\Models\Role::where('name', 'admin')->first();
>>> $user->roles()->attach($adminRole);
```

**Important:** Change default password immediately!

## Support Contacts

- Technical Support: [email/contact]
- Security Issues: [security email]
- System Administrator: [admin email]
- Data Protection Officer: [dpo email]

---

**Version:** 1.0  
**Last Updated:** 2024-01-20
