-- PostgreSQL initialization script for ACME CSR Platform

-- Create test database (using direct SQL instead of PL/pgSQL function)
-- Note: This must be run as a superuser (postgres)
CREATE DATABASE acme_csr_test OWNER acme_user;

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Set default timezone
SET timezone = 'UTC';

-- Basic performance tuning for development
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET work_mem = '4MB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';

-- Reload configuration
SELECT pg_reload_conf();
