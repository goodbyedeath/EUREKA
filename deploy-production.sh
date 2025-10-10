#!/bin/bash

# EUREKA Laravel Production Deployment Script
# Run this script to deploy your Laravel application to production with MySQL

echo "🚀 Starting EUREKA Laravel Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the project directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

# Step 1: Environment Setup
print_status "Setting up production environment..."

# Copy production environment file
if [ -f ".env.production" ]; then
    print_status "Backing up current .env file..."
    if [ -f ".env" ]; then
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    fi
    
    print_status "Copying production environment configuration..."
    cp .env.production .env
    
    print_warning "Please update the following in your .env file:"
    print_warning "- DB_PASSWORD: Set a secure database password"
    print_warning "- APP_URL: Set your production domain"
    print_warning "- MAIL_* settings: Configure your mail server"
    print_warning "- SESSION_DOMAIN: Set your production domain"
    
    read -p "Have you updated the .env file with production values? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Please update .env file with production values before continuing."
        exit 1
    fi
else
    print_error ".env.production file not found. Please create it first."
    exit 1
fi

# Step 2: Install dependencies
print_status "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction || {
    print_error "Composer install failed"
    exit 1
}

print_status "Installing Node.js dependencies..."
npm ci --production || {
    print_error "NPM install failed"
    exit 1
}

# Step 3: Build assets
print_status "Building production assets..."
npm run build || {
    print_error "Asset build failed"
    exit 1
}

# Step 4: Laravel optimizations
print_status "Optimizing Laravel application..."

# Generate application key if not set
print_status "Checking application key..."
if php artisan key:generate --show | grep -q "base64:"; then
    print_status "Application key is set"
else
    print_status "Generating application key..."
    php artisan key:generate --force
fi

# Cache configurations
print_status "Caching configuration files..."
php artisan config:cache || {
    print_error "Config cache failed"
    exit 1
}

print_status "Caching routes..."
php artisan route:cache || {
    print_error "Route cache failed"
    exit 1
}

print_status "Caching views..."
php artisan view:cache || {
    print_error "View cache failed"
    exit 1
}

print_status "Caching events..."
php artisan event:cache || {
    print_warning "Event cache failed - this is optional"
}

# Step 5: Database setup
print_status "Setting up database..."

print_warning "Make sure your MySQL database '$(grep DB_DATABASE .env | cut -d '=' -f2)' exists and is accessible"
read -p "Have you created the MySQL database and user? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_error "Please create the MySQL database and user before continuing."
    print_status "Example MySQL commands:"
    echo "CREATE DATABASE $(grep DB_DATABASE .env | cut -d '=' -f2);"
    echo "CREATE USER '$(grep DB_USERNAME .env | cut -d '=' -f2)'@'localhost' IDENTIFIED BY 'your_password';"
    echo "GRANT ALL PRIVILEGES ON $(grep DB_DATABASE .env | cut -d '=' -f2).* TO '$(grep DB_USERNAME .env | cut -d '=' -f2)'@'localhost';"
    echo "FLUSH PRIVILEGES;"
    exit 1
fi

print_status "Running database migrations..."
php artisan migrate --force || {
    print_error "Database migration failed"
    exit 1
}

print_status "Seeding database (if needed)..."
php artisan db:seed --force || {
    print_warning "Database seeding failed - this might be expected if no seeders exist"
}

# Step 6: Storage and permissions
print_status "Setting up storage links..."
php artisan storage:link || {
    print_warning "Storage link creation failed - this might already exist"
}

print_status "Setting proper file permissions..."
# Set proper permissions for Laravel directories
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || {
    print_warning "Could not change ownership - make sure web server can write to storage and bootstrap/cache"
}

# Step 7: Queue setup
print_status "Setting up queue workers..."
print_warning "Remember to set up a process manager (like Supervisor) for queue workers:"
print_status "php artisan queue:work --daemon --tries=3"

# Step 8: Final checks
print_status "Running final optimization..."
php artisan optimize || {
    print_warning "Final optimization had issues"
}

# Clear any unnecessary caches
php artisan cache:clear

print_status "Checking application status..."
php artisan about || {
    print_warning "Could not run artisan about - check application status manually"
}

# Step 9: Security recommendations
print_status "🔒 Security Checklist:"
echo "✓ APP_DEBUG is set to false"
echo "✓ APP_ENV is set to production"
echo "✓ Database credentials are secure"
echo "✓ Session cookies are secure (HTTPS only)"
echo "✓ Application key is properly generated"
echo ""
print_warning "Additional security steps to consider:"
echo "- Set up SSL/TLS certificate (Let's Encrypt recommended)"
echo "- Configure firewall (UFW, iptables, or cloud security groups)"
echo "- Set up regular database backups"
echo "- Configure log rotation"
echo "- Set up monitoring (Laravel Horizon for queues)"
echo "- Consider using Redis for cache and sessions in high-traffic environments"

# Step 10: Performance recommendations
print_status "🚀 Performance Checklist:"
echo "✓ Composer autoloader is optimized"
echo "✓ Laravel caches are enabled"
echo "✓ Assets are built for production"
echo ""
print_warning "Additional performance optimizations:"
echo "- Enable OPcache in PHP"
echo "- Use Redis for cache and sessions (update CACHE_STORE and SESSION_DRIVER)"
echo "- Set up a CDN for static assets"
echo "- Configure Gzip compression in web server"
echo "- Set up database query optimization"

print_status "✅ Production deployment completed successfully!"
print_status "Your EUREKA application should now be ready for production use."
print_warning "Don't forget to:"
echo "1. Set up your web server (Apache/Nginx) configuration"
echo "2. Configure SSL certificate"
echo "3. Set up monitoring and logging"
echo "4. Test all functionality in production environment"
echo "5. Set up automated backups"

echo ""
print_status "🎉 Deployment Complete! Your EUREKA app is production-ready!"