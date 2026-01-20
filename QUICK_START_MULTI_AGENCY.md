# 🎯 Multi-Agency PDS System - Quick Start Guide

## What Was Built

You now have a **production-ready multi-agency PDS management system** with enterprise features specifically designed for Philippine government deployment.

## 🚀 Quick Deployment (5 Steps)

### Step 1: Install Dependencies
```bash
cd /path/to/personaldatasheet
composer install
npm install
```

### Step 2: Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pds_multiagency
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

This creates:
- ✅ `agencies` table (with SUPER agency)
- ✅ `import_batches`, `import_staging`, `import_errors` tables
- ✅ `ai_console_logs` table
- ✅ Adds `agency_id` to all relevant tables

### Step 4: Seed Test Data (Optional)
```bash
php artisan db:seed --class=MultiAgencySeeder
```

This creates:
- 5 government agencies (DILG, DOH, DepEd, DA, DSWD)
- Admin user for each agency
- 1 super admin account

**Super Admin Credentials:**
- Email: `superadmin@pds.gov.ph`
- Password: `SuperAdmin123!` ⚠️ **CHANGE IN PRODUCTION!**

**Agency Admin Credentials:**
- Email: `{agency-code}-admin@gov.ph` (e.g., `dilg-admin@gov.ph`)
- Password: `password` ⚠️ **CHANGE IN PRODUCTION!**

### Step 5: Start the Server
```bash
php artisan serve
npm run dev
```

Visit: `http://localhost:8000`

## 📋 Common Tasks

### Create a New Agency
```bash
php artisan agency:create DBM "Department of Budget and Management" \
  --email=dbm@gov.ph \
  --phone="+63-2-1234-5678"
```

### Import PDS Data
```bash
# Prepare CSV file with columns:
# surname, first_name, middle_name, date_of_birth, email, etc.

php artisan pds:import DILG /path/to/employees.csv
```

### Check Data Quality
```bash
php artisan pds:quality-report DILG
```

### List All Agencies
```bash
php artisan agency:list
```

## 🔑 Key API Endpoints

### Agency Management
```
GET    /api/v1/agencies              - List all agencies
POST   /api/v1/agencies              - Create agency
GET    /api/v1/agencies/{id}         - Get agency details
GET    /api/v1/agencies/{id}/dashboard - Agency dashboard
```

### AI Console
```
POST   /api/v1/ai-console/analyze-inconsistencies  - Analyze data
POST   /api/v1/ai-console/detect-duplicates        - Find duplicates
GET    /api/v1/ai-console/review-queue             - Pending reviews
```

### PDS Management (Existing)
```
GET    /api/v1/personal-data-sheets       - List PDS (agency-scoped)
POST   /api/v1/personal-data-sheets       - Create PDS
GET    /api/v1/personal-data-sheets/{id}  - Get PDS details
```

All endpoints require authentication via Sanctum:
```bash
Authorization: Bearer {token}
```

## 🎨 Agency Customization

### Upload Agency Logo
```php
POST /api/v1/agencies/{id}/settings
Content-Type: multipart/form-data

logo: [file]
```

### Set Brand Colors
```php
POST /api/v1/agencies/{id}/settings
Content-Type: application/json

{
  "branding": {
    "colors": {
      "primary": "#0066CC",
      "secondary": "#FF9900"
    }
  }
}
```

## 🔒 Security Features

### Data Isolation
- ✅ Users only see their agency's data
- ✅ Super admin sees all agencies
- ✅ Automatic query scoping
- ✅ Isolated cache keys

### Audit Trail
- All API calls logged
- User actions tracked
- AI suggestions audited
- Import operations recorded

## 📊 Dashboard Metrics

Each agency dashboard shows:
- Total users (active/inactive)
- Total PDS records
- Pending approvals
- Recent imports (30 days)
- **Data quality score** (0-100%)

## 🤖 AI Console Usage

### Find Inconsistencies
```bash
POST /api/v1/ai-console/analyze-inconsistencies
{
  "pds_ids": [1, 2, 3]  // Optional: specific records
}
```

### Validate Data
```bash
POST /api/v1/ai-console/validate-data
{
  "pds_id": 123
}
```

### Review AI Suggestions
```bash
# Get pending reviews
GET /api/v1/ai-console/review-queue

# Approve/Reject
POST /api/v1/ai-console/logs/{id}/review
{
  "status": "approved",
  "notes": "Data correction verified",
  "actions": ["updated_field_email"]
}
```

## 📦 PDF Bundle Generation

Generate complete document package:
```php
use App\Services\PdfBundleService;

$service = app(PdfBundleService::class);
$zipPath = $service->generateCompletePackage($pds);
```

Package includes:
- PDS (CSC Form 212)
- Work Experience Sheet (if applicable)
- All attachments
- Certification document with hash

## 🔍 Data Quality Scoring

Quality score based on:
1. **Completeness** (33.3%) - Required fields filled
2. **Accuracy** (33.3%) - Valid formats (email, phone)
3. **Consistency** (33.3%) - No duplicates, unique IDs

**Grades:**
- A: 90-100%
- B: 80-89%
- C: 70-79%
- D: 60-69%
- F: <60%

## 🚨 Troubleshooting

### Can't see other agencies' data
✅ **This is correct!** Data is isolated by agency. Only super admin sees all agencies.

### Import fails with duplicates
✅ **This is correct!** System prevents duplicates. Check `import_errors` table for details.

### AI suggestions not applying
✅ **This is correct!** All AI suggestions require human review before applying.

### Cache showing wrong agency data
Run: `php artisan cache:clear`

## 📚 Documentation Files

1. **MULTI_AGENCY_GUIDE.md** - Complete feature documentation
2. **IMPLEMENTATION_COMPLETE_V2.md** - Deployment summary
3. **DEPLOYMENT.md** - Production deployment guide
4. **README.md** - Project overview

## 🎯 Production Checklist

Before going live:

- [ ] Change all default passwords
- [ ] Configure production database
- [ ] Set up SSL/TLS certificates
- [ ] Enable Redis cache
- [ ] Configure queue workers
- [ ] Set up automated backups
- [ ] Configure error tracking (Sentry)
- [ ] Enable monitoring
- [ ] Review security settings
- [ ] Test data isolation
- [ ] Test import functionality
- [ ] Train agency admins

## 💡 Tips

1. **Start Small**: Create 2-3 agencies first, test thoroughly
2. **Test Imports**: Use small CSV files (10-20 records) initially
3. **Review AI Console**: Check AI suggestions before approving
4. **Monitor Quality**: Run quality reports weekly
5. **Backup Regularly**: Automated daily backups recommended

## 🆘 Support

**Issues?** Check these files:
- Logs: `storage/logs/laravel.log`
- Queue jobs: `php artisan queue:failed`
- Database errors: Check migration status

**Common Commands:**
```bash
# Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart queue workers
php artisan queue:restart

# Check migration status
php artisan migrate:status
```

## 🎉 You're Ready!

Your multi-agency PDS system is ready for deployment. The system has:

✅ **8 new database tables**
✅ **5 new models**  
✅ **4 enterprise services**
✅ **2 API controllers**
✅ **5 artisan commands**
✅ **Complete data isolation**
✅ **AI-assisted validation**
✅ **Enterprise import pipelines**
✅ **PDF bundling**
✅ **Quality reporting**

Start by running the seeders, creating your agencies, and importing your first batch of data.

Good luck! 🚀
