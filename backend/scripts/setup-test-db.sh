#!/bin/bash

# Script to ensure test database is properly set up
echo "Setting up test database..."

# Check if we're in a Docker container
if [ -f /.dockerenv ]; then
    echo "Running in Docker container"
    
    # Wait for PostgreSQL to be ready
    echo "Waiting for PostgreSQL to be ready..."
    while ! php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
        echo "PostgreSQL not ready, waiting..."
        sleep 2
    done
    
    # Ensure test database exists
    echo "Ensuring test database exists..."
    php artisan tinker --execute="
        try {
            DB::connection('pgsql')->getPdo()->exec('CREATE DATABASE IF NOT EXISTS acme_csr_test OWNER acme_user');
            echo 'Test database ready\n';
        } catch (Exception \$e) {
            echo 'Test database already exists or error: ' . \$e->getMessage() . '\n';
        }
    "
    
    # Run migrations on test database
    echo "Running migrations on test database..."
    php artisan migrate --database=pgsql --env=testing
    
    echo "Test database setup complete!"
else
    echo "This script should be run inside the Docker container"
    echo "Use: docker-compose exec backend bash scripts/setup-test-db.sh"
    exit 1
fi
