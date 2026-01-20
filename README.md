# Personal Data Sheet (PDS) Management System

A comprehensive Laravel-based Personal Data Sheet (PDS) management system compliant with Civil Service Commission (CSC) Form 212 standards.

[![Tests](https://github.com/vince052666/personaldatasheet/workflows/tests/badge.svg)](https://github.com/vince052666/personaldatasheet/actions)
[![License](https://img.shields.io/github/license/vince052666/personaldatasheet)](LICENSE)

## Features

### Core Functionality
- ✅ **Complete PDS Management** - Full CRUD operations for Personal Data Sheets (CS Form 212)
- ✅ **User Roles & Permissions** - Admin, HR Manager, and Employee roles with granular permissions
- ✅ **Audit Logging** - Comprehensive tracking of all system changes with user attribution
- ✅ **Version Control** - Automatic versioning of PDS records with rollback capability
- ✅ **Document Management** - Upload and parse PDF/Word documents with OCR support
- ✅ **Excel Import/Export** - Bulk import employee data and export reports
- ✅ **PDF Generation** - Generate CSC Form 212 compliant PDF documents
- ✅ **RESTful API** - Complete API for integration with other systems

### Security & Compliance
- Role-based access control (RBAC)
- Permission-based authorization
- Audit trail for compliance
- Data versioning and history
- Secure file uploads
- Input validation and sanitization

## Technology Stack

- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL/MariaDB or PostgreSQL
- **Authentication**: Laravel Sanctum
- **Frontend**: Blade Templates with Vite
- **Testing**: PHPUnit

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/MariaDB or PostgreSQL
- Git

### Quick Start

```bash
# Clone the repository
git clone https://github.com/vince052666/personaldatasheet.git
cd personaldatasheet

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# Then run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolePermissionSeeder

# Build assets
npm run build

# Start development server
php artisan serve
```

The application will be available at `http://localhost:8000`

## Configuration

### Environment Variables

Key configuration variables in `.env`:

```env
# Application
APP_NAME="PDS Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pds_system
DB_USERNAME=your_username
DB_PASSWORD=your_password

# PDS Settings
PDS_VERSION_RETENTION=10
PDS_ALLOW_MULTIPLE_ACTIVE=false
PDS_REQUIRE_APPROVAL=false
PDS_MAX_UPLOAD_SIZE=10240

# Document Parser
PARSER_OCR_ENABLED=false
PARSER_OCR_SERVICE=tesseract

# PDF Generator
PDF_GENERATOR=dompdf
PDF_PAPER_SIZE=legal
PDF_ORIENTATION=portrait
```

## Usage

### API Endpoints

#### Authentication
```bash
# Login (get API token)
POST /api/login

# Logout
POST /api/logout
```

#### Personal Data Sheets
```bash
# List all PDS
GET /api/personal-data-sheets

# Create new PDS
POST /api/personal-data-sheets

# Get specific PDS
GET /api/personal-data-sheets/{id}

# Update PDS
PUT /api/personal-data-sheets/{id}

# Delete PDS
DELETE /api/personal-data-sheets/{id}

# Get PDS versions
GET /api/personal-data-sheets/{id}/versions

# Export PDS to PDF
POST /api/personal-data-sheets/{id}/export-pdf
```

#### Document Management
```bash
# Upload document
POST /api/personal-data-sheets/{id}/documents

# Import from Excel
POST /api/documents/import-excel

# Download import template
GET /api/documents/import-template
```

#### Audit Logs
```bash
# Get audit logs
GET /api/audit-logs

# Get specific audit log
GET /api/audit-logs/{id}
```

## User Roles

### Administrator
- Full system access
- Manage all users and PDS records
- View and manage roles and permissions
- Access audit logs
- Import/export data

### HR Manager
- View all employee PDS records
- Approve PDS submissions
- Export reports
- Import employee data
- View audit logs

### Employee
- View and edit own PDS
- Upload supporting documents
- Export own PDS to PDF
- View own history

## Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Code Style

```bash
# Run Laravel Pint (PHP CS Fixer)
vendor/bin/pint

# Check without fixing
vendor/bin/pint --test
```

## Deployment

### Production Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions for IONOS and other hosting providers.

Quick deployment:

```bash
# Make deployment script executable
chmod +x deploy-ionos.sh

# Run deployment
./deploy-ionos.sh
```

### CI/CD

GitHub Actions workflow is configured for:
- Automated testing on push/PR
- Code linting
- Security audits
- Deployment to production

## Database Schema

The system includes the following main tables:

- `users` - System users
- `roles` - User roles (Admin, HR, Employee)
- `permissions` - System permissions
- `personal_data_sheets` - Main PDS records
- `work_experiences` - Employment history
- `educational_backgrounds` - Education records
- `civil_service_eligibilities` - Government exam eligibilities
- `trainings` - Training and seminars
- `voluntary_works` - Volunteer work
- `other_information` - Skills, recognitions, memberships
- `pds_versions` - Version history
- `audit_logs` - Audit trail
- `document_uploads` - Uploaded documents

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

If you discover a security vulnerability, please email security@your-domain.com. All security vulnerabilities will be promptly addressed.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [Wiki](https://github.com/vince052666/personaldatasheet/wiki)
- **Issues**: [GitHub Issues](https://github.com/vince052666/personaldatasheet/issues)
- **Email**: support@your-domain.com

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- Follows CSC Form 212 standards
- Designed for Philippine government agencies and organizations

---

**Note**: This system is designed to comply with Philippine Civil Service Commission Form 212 (Revised 2017) standards for Personal Data Sheets.
