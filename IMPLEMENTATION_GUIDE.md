# PDS Management System - Implementation Guide

## Overview

This Laravel-based PDS (Personal Data Sheet) Management System is designed for Philippine government agencies to manage employee data in compliance with Civil Service Commission (CSC) Form No. 212 standards.

## Features Implemented

### 1. AI-Assisted Data Analysis
- **Data Quality Scoring**: Automatic analysis of PDS completeness, accuracy, and consistency
- **Confidence Scoring**: Field-level confidence scores for parsed/OCR data
- **Anomaly Detection**: Identifies data inconsistencies (overlapping work experience, age concerns, etc.)
- **Smart Suggestions**: Actionable recommendations for improving data quality

### 2. OCR Integration
- **Tesseract OCR**: Processes scanned PDFs and images
- **Queue-Based Processing**: Background processing for large files
- **Confidence Tracking**: Per-field confidence scores for OCR results
- **Pattern Matching**: Extracts structured data from unstructured documents

### 3. HR Review Dashboards
- **Statistics Dashboard**: Total PDS, pending reviews, approval status
- **Quality Metrics**: Average quality scores with breakdowns
- **Analytics Dashboard**: Department-wise distribution, monthly trends
- **Low-Quality Alerts**: Highlights PDS records needing attention

### 4. Data Quality Reports
- **Quality Scoring**: 0-100% score per PDS based on completeness, accuracy, consistency
- **Bulk Reports**: Generate quality reports for filtered datasets
- **Export Options**: PDF and Excel export capabilities
- **Issue Tracking**: Detailed list of missing/incorrect fields

### 5. Bulk Upload Queue Workers
- **Background Processing**: Queue-based bulk imports
- **Progress Tracking**: Monitor import progress
- **Error Reporting**: Detailed error logs for failed imports
- **Retry Logic**: Automatic retry for transient failures

### 6. Performance Optimizations
- **Database Indexes**: Optimized queries for 10,000+ records
- **Full-Text Search**: MySQL full-text indexes on names and emails
- **API Caching**: 5-10 minute cache TTL for frequently accessed data
- **Eager Loading**: Optimized relationship loading
- **Pagination**: 25 records per page default

### 7. Frontend Views
- **Responsive Design**: Mobile-friendly Tailwind CSS
- **CSC-Compliant UI**: Philippine government blue theme
- **Dashboard**: Statistics, quality metrics, recent activities
- **PDS Management**: CRUD operations with search/filter
- **Print Support**: Print-friendly layouts

### 8. Search & Filtering
- **Advanced Filters**: Department, position, status, date range
- **Full-Text Search**: Search by name, email across all fields
- **Sortable Columns**: Sort by date, name, quality score
- **Export Filtered Results**: Export search results to PDF/Excel

### 9. Export Reports
- **CSC Form 212**: Official government format PDF
- **Excel Export**: Bulk export to Excel/CSV
- **Quality Reports**: Exportable quality analysis
- **Batch Export**: Export multiple PDS records

### 10. CSC-Compliant UI/UX
- **Form 212 Layout**: Official CSC form structure
- **Accessible Forms**: WCAG compliant
- **Print Layouts**: Government-standard formatting
- **Mobile Responsive**: Works on all devices

## Installation

### Prerequisites
```bash
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & npm
- Redis (optional, for caching)
- Tesseract OCR (for OCR functionality)
```

### Setup Steps

1. **Install PHP Dependencies**
```bash
composer install
```

2. **Install JavaScript Dependencies**
```bash
npm install
npm run build
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure Database**
Update `.env` with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pds_management
DB_USERNAME=root
DB_PASSWORD=
```

5. **Configure Cache** (Optional but recommended)
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

6. **Run Migrations**
```bash
php artisan migrate
```

7. **Seed Database** (Optional)
```bash
php artisan db:seed
```

8. **Start Queue Worker**
```bash
php artisan queue:work --tries=3
```

9. **Start Development Server**
```bash
php artisan serve
```

## Tesseract OCR Setup

### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install tesseract-ocr
sudo apt-get install tesseract-ocr-eng  # English language pack
```

### macOS
```bash
brew install tesseract
```

### Windows
Download installer from: https://github.com/UB-Mannheim/tesseract/wiki

## API Documentation

### Authentication
All API endpoints require Sanctum authentication:
```bash
Authorization: Bearer {your-token}
```

### Endpoints

#### PDS Management
- `GET /api/personal-data-sheets` - List all PDS (paginated, filterable)
- `POST /api/personal-data-sheets` - Create new PDS
- `GET /api/personal-data-sheets/{id}` - Get PDS details
- `PUT /api/personal-data-sheets/{id}` - Update PDS
- `DELETE /api/personal-data-sheets/{id}` - Delete PDS

#### Quality Reports
- `GET /api/personal-data-sheets/{id}/quality-report` - Get quality report for single PDS
- `GET /api/personal-data-sheets/reports/bulk-quality` - Get bulk quality reports
- `GET /api/personal-data-sheets/reports/statistics` - Get quality statistics

#### Document Upload
- `POST /api/personal-data-sheets/{id}/documents` - Upload document
- `POST /api/documents/import-excel` - Bulk import from Excel

#### Filtering & Search
```bash
# Filter by department
GET /api/personal-data-sheets?filter[department]=HR

# Filter by status
GET /api/personal-data-sheets?filter[status]=approved

# Search by name
GET /api/personal-data-sheets?filter[search]=Juan

# Sort by name
GET /api/personal-data-sheets?sort=surname

# Pagination
GET /api/personal-data-sheets?per_page=50&page=2
```

## Web Routes

- `/dashboard` - Main dashboard
- `/dashboard/analytics` - Analytics dashboard
- `/pds` - PDS list
- `/pds/create` - Create new PDS
- `/pds/{id}` - View PDS
- `/pds/{id}/edit` - Edit PDS

## Queue Jobs

### AnalyzeDataQuality
Analyzes PDS data quality and generates quality scores.
```php
AnalyzeDataQuality::dispatch($personalDataSheet);
```

### ProcessOCR
Processes uploaded documents with OCR.
```php
ProcessOCR::dispatch($documentUpload);
```

### ProcessBulkImport
Processes bulk Excel imports in background.
```php
ProcessBulkImport::dispatch($filePath, $user);
```

## Configuration

### Quality Score Thresholds
Quality scores are categorized as:
- **Excellent**: ≥ 90%
- **Good**: 75-89%
- **Fair**: 60-74%
- **Poor**: 40-59%
- **Critical**: < 40%

### Cache Configuration
Default cache TTLs:
- Index lists: 5 minutes
- Individual PDS: 10 minutes
- Statistics: 10 minutes

### Pagination
Default pagination: 25 records per page
Maximum per page: 100 records

## Testing

Run tests:
```bash
php artisan test
```

Run specific test suite:
```bash
php artisan test --testsuite=Feature
```

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up Redis for caching and queues
- [ ] Configure queue worker as systemd service
- [ ] Set up SSL certificate
- [ ] Configure backup strategy
- [ ] Set up monitoring (Laravel Telescope, Sentry, etc.)
- [ ] Enable API rate limiting
- [ ] Configure CORS if needed

### Queue Worker Service (systemd)
Create `/etc/systemd/system/pds-queue-worker.service`:
```ini
[Unit]
Description=PDS Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/pds/artisan queue:work --tries=3 --timeout=300

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable pds-queue-worker
sudo systemctl start pds-queue-worker
```

## Troubleshooting

### Queue Jobs Not Processing
```bash
# Check queue worker is running
php artisan queue:work

# Clear failed jobs
php artisan queue:flush

# Restart queue worker
php artisan queue:restart
```

### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### OCR Not Working
1. Verify Tesseract is installed: `tesseract --version`
2. Check PHP can execute Tesseract
3. Verify file permissions on uploaded documents
4. Check queue worker logs

## Security Considerations

1. **File Upload Validation**: 
   - Validates file signatures (not just extensions)
   - Maximum file size: 10MB (configurable)
   - Allowed types: PDF, JPG, PNG

2. **SQL Injection Prevention**: 
   - Uses Laravel's query builder and Eloquent ORM
   - All user inputs are parameterized

3. **XSS Prevention**: 
   - Blade templates auto-escape output
   - CSP headers recommended

4. **CSRF Protection**: 
   - Enabled on all forms
   - API uses Sanctum token authentication

5. **Data Privacy**:
   - Soft deletes for PDS records
   - Audit logging for all changes
   - Version history maintained

## License

This project is licensed under the MIT License.

## Support

For issues and questions, please use the GitHub issue tracker.
