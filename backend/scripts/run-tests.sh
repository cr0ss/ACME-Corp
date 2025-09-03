#!/bin/bash

# Test runner script that ensures proper environment variables are set
echo "Setting up test environment..."

# Set environment variables for testing
export APP_ENV=testing
export DB_CONNECTION=pgsql
export DB_HOST=postgres
export DB_PORT=5432
export DB_DATABASE=acme_csr_test
export DB_USERNAME=acme_user
export DB_PASSWORD=acme_password

# Clear any cached configuration
php artisan config:clear

# Run the tests with the specified arguments
echo "Running tests with test database: $DB_DATABASE"
php artisan test "$@"
