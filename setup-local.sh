#!/bin/bash
# CaptiveWiFi Platform - Local Setup Script
# Run this in your Mac terminal from the project directory

set -e  # Exit on any error

echo "=========================================="
echo "CaptiveWiFi Platform - Local Setup"
echo "=========================================="
echo ""

# Step 1: Start Docker Services
echo "📦 Step 1: Starting Docker services..."
docker compose up -d

echo ""
echo "⏳ Waiting for services to be healthy (15 seconds)..."
sleep 15

echo ""
echo "✅ Checking service status..."
docker compose ps

echo ""
echo "=========================================="

# Step 2: Clear Laravel Cache
echo "🧹 Step 2: Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "=========================================="

# Step 3: Run Database Migrations
echo "🗄️  Step 3: Running database migrations..."
php artisan migrate --force

echo ""
echo "✅ Migration status:"
php artisan migrate:status

echo ""
echo "=========================================="

# Step 4: Verify Database Connection
echo "🔍 Step 4: Testing database connection..."
php artisan tinker --execute="echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;"

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Create a test tenant (I'll provide commands)"
echo "2. Add sample data (optional)"
echo "3. Start dev server: php artisan serve"
echo "4. Access dashboard in browser"
echo ""
echo "Report back the results and we'll continue!"
echo "=========================================="
