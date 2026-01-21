# Quick Start Guide - PDS Management System

## Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & npm

## Installation (5 minutes)

### 1. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_CONNECTION=mysql
DB_DATABASE=pds_management
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### 3. Setup Database
```bash
php artisan migrate
```

### 4. Start Services
```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work
```

### 5. Access Application
- Web Dashboard: http://localhost:8000/dashboard
- API Endpoints: http://localhost:8000/api/personal-data-sheets

## Quick Test

### Create a Test PDS via API
```bash
curl -X POST http://localhost:8000/api/personal-data-sheets \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "surname": "DELA CRUZ",
    "first_name": "JUAN",
    "middle_name": "SANTOS",
    "date_of_birth": "1990-01-15",
    "place_of_birth": "Manila",
    "sex": "Male",
    "civil_status": "Single",
    "citizenship": "Filipino",
    "residential_city": "Quezon City",
    "residential_province": "Metro Manila",
    "permanent_city": "Quezon City",
    "permanent_province": "Metro Manila",
    "mobile_no": "09171234567",
    "email_address": "juan.delacruz@example.com"
  }'
```

## Features to Test

### 1. Dashboard
Visit `/dashboard` to see:
- Total PDS count
- Pending reviews
- Quality score overview
- Recent submissions

### 2. Create PDS
Visit `/pds/create` and fill the form

### 3. View Quality Report
- Go to `/pds/{id}`
- Check quality score and suggestions

### 4. Test Search
- Go to `/pds`
- Use filters: department, status
- Use search: name or email

### 5. Export PDF
- View any PDS
- Click "Print" button
- Browser print dialog opens

## Optional: Enable OCR

### Install Tesseract
```bash
# Ubuntu/Debian
sudo apt-get install tesseract-ocr

# macOS
brew install tesseract

# Windows
# Download from https://github.com/UB-Mannheim/tesseract/wiki
```

### Upload Document for OCR
```bash
curl -X POST http://localhost:8000/api/personal-data-sheets/1/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/document.pdf" \
  -F "document_type=pds_form"
```

Check queue for OCR processing:
```bash
php artisan queue:work --once
```

## Optional: Enable Redis

### Install Redis
```bash
# Ubuntu/Debian
sudo apt-get install redis-server

# macOS
brew install redis

# Start Redis
redis-server
```

### Update .env
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Clear config cache:
```bash
php artisan config:clear
php artisan cache:clear
```

## Common Issues

### "Queue not processing"
```bash
php artisan queue:work --verbose
```

### "Cache not working"
```bash
php artisan cache:clear
php artisan config:cache
```

### "Migration error"
```bash
php artisan migrate:fresh
```

### "OCR not working"
```bash
# Check Tesseract installation
tesseract --version

# Check PHP can execute it
php -r "echo shell_exec('tesseract --version');"
```

## Production Deployment

### 1. Set Production Mode
```env
APP_ENV=production
APP_DEBUG=false
```

### 2. Optimize
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Setup Queue Worker Service
Create `/etc/systemd/system/pds-queue.service`:
```ini
[Unit]
Description=PDS Queue Worker

[Service]
User=www-data
WorkingDirectory=/var/www/pds
ExecStart=/usr/bin/php artisan queue:work --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable service:
```bash
sudo systemctl enable pds-queue
sudo systemctl start pds-queue
```

### 4. Setup Cron for Scheduled Tasks
```bash
* * * * * cd /var/www/pds && php artisan schedule:run >> /dev/null 2>&1
```

## Next Steps

1. Read [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) for detailed documentation
2. Read [FEATURES_SUMMARY.md](FEATURES_SUMMARY.md) for feature list
3. Configure user authentication (Sanctum tokens)
4. Set up monitoring (Laravel Telescope)
5. Configure backups
6. Set up SSL certificate
7. Enable rate limiting

## Support

For issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Queue logs: `php artisan queue:failed`
3. Database: Check migration status with `php artisan migrate:status`

## API Documentation

See [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) for complete API documentation.

Quick reference:
- `GET /api/personal-data-sheets` - List PDS
- `POST /api/personal-data-sheets` - Create PDS
- `GET /api/personal-data-sheets/{id}` - View PDS
- `GET /api/personal-data-sheets/{id}/quality-report` - Quality report
- `GET /api/personal-data-sheets/reports/statistics` - Statistics
