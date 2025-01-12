#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Log entrypoint start
echo "Starting Docker entrypoint script..."

# Ensure necessary permissions for storage and bootstrap cache
echo "Setting permissions for storage and bootstrap/cache..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Check if Composer dependencies are installed; install them if not
if [ ! -d "/var/www/vendor" ]; then
    echo "Running Composer install..."
    composer install --no-dev --optimize-autoloader
else
    echo "Composer dependencies already installed. Skipping install."
fi

# Generate the application key if it doesn't exist
if [ ! -f "/var/www/.env" ]; then
    echo "Application key generation skipped because .env file is missing."
else
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
else
    echo "Skipping database migrations. Set RUN_MIGRATIONS=true to enable."
fi

# Check if the APP_ENV is set to local or production and adjust behavior
if [ "${APP_ENV}" = "local" ]; then
    echo "APP_ENV is set to local. Starting Laravel development server..."
    php artisan serve --host=0.0.0.0 --port=9000
else
    echo "APP_ENV is set to production. Starting PHP-FPM..."
    exec php-fpm
fi
