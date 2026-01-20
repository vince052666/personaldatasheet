# Multi-Agency PDS Management System - Enterprise Features

This document describes the multi-agency and enterprise features added to the PDS Management System for deployment across Philippine government agencies.

## Table of Contents

1. [Multi-Tenancy / Multi-Agency Support](#multi-tenancy--multi-agency-support)
2. [Enterprise Data Migration Pipelines](#enterprise-data-migration-pipelines)
3. [AI Console for Internal Analysis](#ai-console-for-internal-analysis)
4. [Enhanced PDF Generation & Bundling](#enhanced-pdf-generation--bundling)
5. [Reconciliation and Quality Reports](#reconciliation-and-quality-reports)
6. [Agency Administration](#agency-administration)
7. [API Endpoints](#api-endpoints)
8. [Artisan Commands](#artisan-commands)

---

## Multi-Tenancy / Multi-Agency Support

### Overview
The system now supports multiple government agencies with complete data isolation. Each agency has its own users, PDS records, and settings.

### Database Schema

#### Agencies Table
- `id` - Primary key
- `code` - Unique agency code (e.g., 'DILG', 'DOH', 'DepEd')
- `name` - Full agency name
- `description` - Agency description
- `logo_path` - Path to agency logo
- `settings` - JSON field for agency-specific settings
- `branding` - JSON field for colors, fonts, etc.
- `contact_email`, `contact_phone`, `address` - Contact information
- `is_active` - Agency status

### Agency Isolation

All major tables now have an `agency_id` foreign key:
- `users`
- `personal_data_sheets`
- `document_uploads`
- `audit_logs`
- `activity_logs`
- `data_quality_scores`
- `recruitment`

### Global Scope

The `AgencyScope` automatically filters queries to show only data from the current user's agency. Super admins can see all agencies.

### Tenant Middleware

`TenantMiddleware` sets the agency context for each request and isolates cache keys by agency.

---

## Enterprise Data Migration Pipelines

### Overview
Comprehensive data import system with staging, deduplication, error handling, and resumable batch processing.

### Database Schema

#### Import Batches Table
Tracks each import operation:
- `id` - Batch ID
- `agency_id` - Agency performing import
- `user_id` - User who initiated import
- `filename` - Source file name
- `status` - pending, processing, completed, failed, partial
- `total_records`, `processed_records`, `success_count`, `error_count`, `duplicate_count`
- `started_at`, `completed_at`

#### Import Staging Table
Temporary storage for imported records:
- `import_batch_id` - Parent batch
- `row_number` - Row in source file
- `raw_data` - Original data (JSON)
- `mapped_data` - Transformed data (JSON)
- `status` - pending, validated, imported, error, duplicate
- `duplicate_key` - For deduplication
- `matched_pds_id` - Existing PDS if duplicate

#### Import Errors Table
Detailed error logging:
- `import_batch_id` - Parent batch
- `import_staging_id` - Source record
- `error_type` - validation, duplicate, system, data_quality
- `field` - Field with error
- `error_message` - Human-readable error
- `severity` - warning, error, critical
- `resolved` - Whether error was fixed
- `resolved_by`, `resolved_at`, `resolution_notes`

### DataMigrationService

Key methods:
- `createBatch()` - Initialize import batch
- `stageRecords()` - Load records into staging
- `processStaging()` - Process staged records with validation and deduplication
- `resumeBatch()` - Resume incomplete import
- `getBatchSummary()` - Get import statistics

### Deduplication

Checks for duplicates using:
1. Email address
2. Employee ID
3. SSS number
4. TIN number

### Import Process

1. Create batch
2. Stage all records
3. For each record:
   - Validate data
   - Map fields to PDS structure
   - Check for duplicates
   - Import if valid and unique
   - Log errors if issues found
4. Generate summary report

---

## AI Console for Internal Analysis

### Overview
AI-powered data analysis and validation with human-in-the-loop review system.

### Database Schema

#### AI Console Logs Table
- `session_id` - Groups related queries
- `query_type` - analysis, validation, inconsistency_check, suggestion
- `prompt` - Query sent to AI
- `response` - AI response
- `context` - Related PDS IDs, fields
- `findings` - Structured results (JSON)
- `status` - pending, reviewed, approved, rejected
- `flagged_for_review` - Requires human review
- `reviewed_by`, `reviewed_at`, `reviewer_notes`
- `actions_taken` - What was done with AI suggestion

### AIConsoleService

Key methods:
- `analyzeInconsistencies()` - Check data for anomalies
- `validateData()` - Validate specific PDS record
- `suggestCorrections()` - Generate correction suggestions
- `detectDuplicates()` - Find potential duplicates
- `reviewQuery()` - Human review of AI suggestion
- `getPendingReviews()` - Get items needing review

### Analysis Types

1. **Inconsistency Detection**
   - Missing required fields
   - Invalid date formats
   - Age anomalies
   - Format violations

2. **Data Validation**
   - Email format validation
   - Mobile number format (Philippine)
   - Required field checks

3. **Correction Suggestions**
   - Email normalization
   - Mobile number formatting
   - Field standardization

4. **Duplicate Detection**
   - Email duplicates
   - SSN duplicates
   - Name matching

### Review Queue

All AI suggestions are flagged for human review before being applied to production data. This ensures:
- Full traceability
- Reviewer accountability
- Data integrity

---

## Enhanced PDF Generation & Bundling

### PdfBundleService

Generates complete document packages including:

1. **Main PDS (CSC Form 212)**
   - Pixel-accurate layout
   - Proper fonts and spacing
   - One-page rules enforcement

2. **Work Experience Sheet (WES)**
   - Generated when >5 work experiences
   - Detailed employment history
   - Tabular format

3. **Document Certification**
   - Certification hash for verification
   - Timestamp
   - Agency information
   - Approval watermarks

4. **Attachments**
   - All uploaded documents
   - Organized by type

5. **ZIP Bundle**
   - Complete package
   - Portable format
   - Easy distribution

### Certification Hash

SHA-256 hash generated from:
- PDS ID
- Full name
- Date of birth
- Employee number
- Agency ID
- Last update timestamp

Use `verifyCertificationHash()` to verify document authenticity.

---

## Reconciliation and Quality Reports

### ReconciliationService

#### Import Reconciliation
- Batch summary statistics
- Error breakdown by type and severity
- Duplicate analysis
- Data quality issues
- Field completeness metrics

#### Duplicate Reports
- Email duplicates count
- SSN duplicates count
- Detailed duplicate lists

#### Data Quality Scoring

Calculates overall score (0-100) based on:

1. **Completeness (33.3%)**
   - Required fields filled
   - Average across all required fields

2. **Accuracy (33.3%)**
   - Valid email formats
   - Correct data types

3. **Consistency (33.3%)**
   - No duplicate emails
   - Unique identifiers

**Grades:**
- A: 90-100%
- B: 80-89%
- C: 70-79%
- D: 60-69%
- F: <60%

#### Agency Comparison
Compare multiple agencies on:
- Total PDS records
- Quality scores
- Recent import activity

---

## Agency Administration

### Agency Management

**Features:**
- Create/update/delete agencies
- Manage agency users
- Configure agency settings
- Customize branding (colors, logo)
- View agency dashboard
- Monitor data quality

### Agency Dashboard

Displays:
- Total users (active/inactive)
- Total PDS records
- Pending approvals
- Recent imports (last 30 days)
- Data quality score

### Settings & Branding

Configure:
- Primary/secondary colors
- Logo upload
- Contact information
- Custom settings (JSON)

---

## API Endpoints

### Agency Routes

```
GET    /api/v1/agencies                    - List agencies
POST   /api/v1/agencies                    - Create agency
GET    /api/v1/agencies/{id}               - Get agency details
PUT    /api/v1/agencies/{id}               - Update agency
DELETE /api/v1/agencies/{id}               - Delete agency
GET    /api/v1/agencies/{id}/users         - Get agency users
GET    /api/v1/agencies/{id}/dashboard     - Agency dashboard
GET    /api/v1/agencies/{id}/settings      - Get settings
POST   /api/v1/agencies/{id}/settings      - Update settings
```

### AI Console Routes

```
GET    /api/v1/ai-console                          - List AI logs
POST   /api/v1/ai-console/session/start            - Start new session
POST   /api/v1/ai-console/analyze-inconsistencies  - Analyze data
POST   /api/v1/ai-console/validate-data            - Validate PDS
POST   /api/v1/ai-console/suggest-corrections      - Get suggestions
POST   /api/v1/ai-console/detect-duplicates        - Find duplicates
GET    /api/v1/ai-console/review-queue             - Pending reviews
POST   /api/v1/ai-console/logs/{id}/review         - Review AI suggestion
GET    /api/v1/ai-console/logs/{id}                - Get log details
GET    /api/v1/ai-console/session/{id}/history     - Session history
```

---

## Artisan Commands

### Agency Management

```bash
# Create new agency
php artisan agency:create DILG "Department of the Interior and Local Government" \
  --email=dilg@gov.ph --phone="+63-2-1234-5678"

# List all agencies
php artisan agency:list

# List only active agencies
php artisan agency:list --active
```

### Data Import

```bash
# Import PDS data from CSV
php artisan pds:import DILG /path/to/data.csv \
  --user-id=1 --chunk=100

# Resume incomplete import
php artisan pds:import-resume 123 --chunk=100
```

### Data Quality

```bash
# Generate data quality report
php artisan pds:quality-report DILG

# Save to specific file
php artisan pds:quality-report DILG --output=/path/to/report.json
```

---

## Security Considerations

### Data Isolation

1. **Database Level**: Foreign key constraints ensure data belongs to correct agency
2. **Application Level**: Global scopes filter queries automatically
3. **Middleware Level**: Tenant context set on every request
4. **Cache Level**: Separate cache keys per agency

### Access Control

1. **Super Admin**: Cross-agency access
2. **Agency Admin**: Full access within agency
3. **Regular User**: Limited to own records

### Audit Trail

All operations logged with:
- User ID
- Agency ID
- Action performed
- Timestamp
- IP address

### Data Validation

- Input validation on all endpoints
- SQL injection prevention
- XSS protection
- CSRF tokens

---

## Deployment Checklist

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Create Super Agency** (automatically created by migration)

3. **Create Government Agencies**
   ```bash
   php artisan agency:create DILG "Department of the Interior and Local Government"
   php artisan agency:create DOH "Department of Health"
   php artisan agency:create DepEd "Department of Education"
   ```

4. **Assign Users to Agencies**
   - Update existing users with agency_id
   - Create agency admin users

5. **Configure Permissions**
   - Set up roles (super-admin, agency-admin, user)
   - Assign permissions

6. **Test Data Isolation**
   - Verify users only see their agency data
   - Test super admin access

7. **Configure Agency Branding**
   - Upload logos
   - Set colors
   - Configure settings

8. **Test Data Import**
   - Import sample data
   - Verify deduplication
   - Check error handling

9. **Test AI Console**
   - Run inconsistency checks
   - Test validation
   - Verify review queue

10. **Generate Quality Reports**
    - Run for each agency
    - Review scores
    - Address issues

---

## Support

For issues or questions, contact the development team or refer to the main README.md file.

## Version History

- **v2.0.0** - Multi-agency enterprise features
  - Multi-tenancy support
  - Data migration pipelines
  - AI console
  - Enhanced PDF generation
  - Quality reporting
