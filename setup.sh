#!/bin/bash

# AndCorp Car Dealership - Setup Script
# This script will help you set up the application quickly

echo "================================================"
echo "  AndCorp Car Dealership - Setup Assistant"
echo "================================================"
echo ""

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "✓ Creating .env file from template..."
    cp .env.example .env
    echo "  .env file created successfully!"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "Database Setup"
echo "-------------"
read -p "Enter MySQL username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Enter MySQL password: " DB_PASS
echo ""

read -p "Enter database name (default: car_dealership): " DB_NAME
DB_NAME=${DB_NAME:-car_dealership}

echo ""
echo "✓ Creating database..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "  Database created successfully!"
else
    echo "  ⚠ Could not create database. It may already exist or credentials are incorrect."
fi

echo ""
echo "✓ Importing database schema..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql 2>/dev/null

if [ $? -eq 0 ]; then
    echo "  Schema imported successfully!"
else
    echo "  ✗ Error importing schema"
    exit 1
fi

echo ""
read -p "Import sample data with demo accounts? (y/n): " IMPORT_SEED
if [ "$IMPORT_SEED" = "y" ] || [ "$IMPORT_SEED" = "Y" ]; then
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/seed.sql 2>/dev/null
    echo "  Sample data imported!"
    echo ""
    echo "  Demo Accounts Created:"
    echo "  ----------------------"
    echo "  Admin:    admin@andcorp.com / admin123"
    echo "  Customer: customer@example.com / customer123"
fi

echo ""
echo "✓ Updating .env file..."
sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
rm .env.bak 2>/dev/null

echo ""
echo "✓ Creating storage directory..."
mkdir -p storage/uploads
chmod -R 755 storage/

echo ""
echo "================================================"
echo "  Setup Complete! 🎉"
echo "================================================"
echo ""
echo "To start the development server, run:"
echo "  cd public"
echo "  php -S localhost:8000"
echo ""
echo "Then open your browser to:"
echo "  http://localhost:8000"
echo ""
echo "Login with:"
echo "  Email: admin@andcorp.com"
echo "  Password: admin123"
echo ""
echo "================================================"
