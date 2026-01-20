#!/bin/bash

# IONOS Deployment Script for PDS Management System
# This script handles deployment to IONOS hosting

set -e

echo "🚀 Starting deployment to IONOS..."

# Configuration
SERVER_USER="${IONOS_USER:-your_username}"
SERVER_HOST="${IONOS_HOST:-your_server.ionos.com}"
SERVER_PATH="${IONOS_PATH:-/path/to/your/app}"
APP_ENV="${APP_ENV:-production}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Pre-deployment checks
log_info "Running pre-deployment checks..."

if [ ! -f "composer.json" ]; then
    log_error "composer.json not found. Are you in the project root?"
    exit 1
fi

# Build assets
log_info "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

log_info "Building frontend assets..."
npm install
npm run build

# Create deployment archive
log_info "Creating deployment archive..."
tar -czf deployment.tar.gz \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='.env' \
    .

# Upload to server
log_info "Uploading to IONOS server..."
scp deployment.tar.gz ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/

# Execute deployment on server
log_info "Executing deployment on server..."
ssh ${SERVER_USER}@${SERVER_HOST} << EOF
    cd ${SERVER_PATH}
    
    # Backup current version
    if [ -d "current" ]; then
        mv current backup-\$(date +%Y%m%d-%H%M%S)
    fi
    
    # Extract new version
    mkdir -p current
    tar -xzf deployment.tar.gz -C current
    cd current
    
    # Set permissions
    chmod -R 755 storage bootstrap/cache
    
    # Run migrations
    php artisan migrate --force
    
    # Seed roles and permissions
    php artisan db:seed --class=RolePermissionSeeder --force
    
    # Clear and cache config
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Restart services (adjust based on your IONOS setup)
    # php artisan queue:restart
    
    # Clean up
    cd ..
    rm deployment.tar.gz
    
    echo "✅ Deployment completed successfully!"
EOF

# Clean up local files
rm deployment.tar.gz

log_info "Deployment process completed!"
log_warning "Don't forget to:"
log_warning "  1. Update .env file on the server"
log_warning "  2. Set up cron jobs for scheduled tasks"
log_warning "  3. Configure SSL certificates"
log_warning "  4. Set up backups"

echo ""
echo "🎉 Deployment to IONOS completed successfully!"
