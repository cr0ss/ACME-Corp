# ACME CSR Platform - Development Commands
# =====================================
# This Makefile provides convenient shortcuts for common development tasks.
# All commands run inside Docker containers for consistency.
# Note: All docker-compose exec commands use -T flag for non-interactive execution
# to ensure compatibility with CI/CD, git hooks, and automated scripts.

.PHONY: help up down restart logs shell

# Default target
help: ## Show this help message
	@echo "ACME CSR Platform - Available Commands:"
	@echo "======================================"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# =============================================================================
# Docker Management
# =============================================================================

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs from all services
	docker-compose logs -f

logs-backend: ## Show logs from backend service only
	docker-compose logs -f backend

logs-frontend: ## Show logs from frontend service only
	docker-compose logs -f frontend

# =============================================================================
# Backend Development
# =============================================================================

shell: ## Open shell in backend container
	docker-compose exec -T backend bash

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker-compose exec -T backend php artisan $(cmd)

# Database Commands
migrate: ## Run database migrations
	docker-compose exec -T backend php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	docker-compose exec -T backend php artisan migrate:fresh

migrate-seed: ## Run migrations and seed database
	docker-compose exec -T backend php artisan migrate:fresh --seed

seed: ## Seed the database
	docker-compose exec -T backend php artisan db:seed

# =============================================================================
# Testing & Quality Assurance
# =============================================================================

# Pre-commit hooks management
pre-commit-install: ## Install pre-commit hooks
	pre-commit install

pre-commit-uninstall: ## Uninstall pre-commit hooks
	pre-commit uninstall

pre-commit-run: ## Run pre-commit hooks on all files
	pre-commit run --all-files

pre-commit-update: ## Update pre-commit hooks to latest versions
	pre-commit autoupdate

test: ## Run all tests (using test database)
	docker-compose exec -T backend php artisan test --env=testing

test-unit: ## Run unit tests only (using test database)
	docker-compose exec -T backend php artisan test --testsuite=Unit --env=testing

test-feature: ## Run feature tests only (using test database)
	docker-compose exec -T backend php artisan test --testsuite=Feature --env=testing

test-filter: ## Run specific test (usage: make test-filter filter="AdminTest")
	docker-compose exec -T backend php artisan test --filter=$(filter) --env=testing

test-coverage: ## Run tests with coverage report (using test database)
	docker-compose exec -T backend php artisan test --coverage --env=testing

# Pest Testing (Modern PHP Testing Framework)
test-pest: ## Run all Pest tests natively (using test database)
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/"

test-pest-unit: ## Run Pest unit tests only (User and Campaign models)
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/UserTest.php tests/Pest/CampaignTest.php"

test-pest-feature: ## Run Pest feature tests only (API tests)
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/CampaignApiTest.php"

test-pest-api: ## Run Pest API tests only
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/ --filter=\"api\""

test-pest-filter: ## Run specific Pest test (usage: make test-pest-filter filter="UserTest")
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/ --filter=$(filter)"

test-pest-file: ## Run specific Pest test file (usage: make test-pest-file file="UserTest.php")
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/$(file)"

# Static Analysis
phpstan: ## Run PHPStan static analysis
	docker-compose exec -T backend ./vendor/bin/phpstan analyse

phpstan-baseline: ## Generate PHPStan baseline
	docker-compose exec -T backend ./vendor/bin/phpstan analyse --generate-baseline

# Code Formatting
format: ## Format code with Laravel Pint
	docker-compose exec -T backend ./vendor/bin/pint

format-check: ## Check code formatting without making changes
	docker-compose exec -T backend ./vendor/bin/pint --test

# Security
security: ## Run security checks
	docker-compose exec -T backend php artisan audit:security

# =============================================================================
# Cache Management
# =============================================================================

cache-clear: ## Clear all caches
	docker-compose exec -T backend php artisan cache:clear

config-clear: ## Clear configuration cache
	docker-compose exec -T backend php artisan config:clear

route-clear: ## Clear route cache
	docker-compose exec -T backend php artisan route:clear

view-clear: ## Clear view cache
	docker-compose exec -T backend php artisan view:clear

clear-all: config-clear route-clear view-clear cache-clear ## Clear all caches

# =============================================================================
# Frontend Development
# =============================================================================

frontend-shell: ## Open shell in frontend container
	docker-compose exec -T frontend sh

npm: ## Run npm command (usage: make npm cmd="install")
	docker-compose exec -T frontend npm $(cmd)

npm-install: ## Install frontend dependencies
	docker-compose exec -T frontend npm install

npm-dev: ## Run frontend in development mode
	docker-compose exec -T frontend npm run dev

npm-build: ## Build frontend for production
	docker-compose exec -T frontend npm run build

npm-test: ## Run frontend tests (run once and exit)
	docker-compose exec -T frontend npm run test:unit -- --run

npm-test-watch: ## Run frontend tests in watch mode
	docker-compose exec -T frontend npm run test:unit

npm-test-coverage: ## Run frontend tests with coverage
	docker-compose exec -T frontend npm run test:unit -- --run --coverage

npm-lint: ## Run frontend linting
	docker-compose exec -T frontend npm run lint

npm-format: ## Format frontend code
	docker-compose exec -T frontend npm run format

# =============================================================================
# Development Workflow
# =============================================================================

install: up migrate-seed ## Full installation: start services, run migrations, seed data
	@echo "🎉 Installation complete! Your ACME CSR Platform is ready."
	@echo "📱 Frontend: http://localhost:3000"
	@echo "🔧 Backend API: http://localhost:8000"

launch: ## Complete initial launch: setup, install, and start development
	@echo "🚀 Launching ACME CSR Platform for the first time..."
	@echo "📋 Prerequisites check..."
	@command -v docker >/dev/null 2>&1 || { echo "❌ Docker is not installed. Please install Docker first."; exit 1; }
	@command -v docker-compose >/dev/null 2>&1 || { echo "❌ Docker Compose is not installed. Please install Docker Compose first."; exit 1; }
	@echo "✅ Prerequisites check passed!"
	@echo ""
	@echo "🔧 Setting up environment..."
	@if [ ! -f .env ]; then \
		if [ -f docker.env.example ]; then \
			cp docker.env.example .env; \
			echo "✅ Environment file created from docker.env.example"; \
		else \
			echo "⚠️  No .env file found and no docker.env.example available"; \
			echo "   Please create a .env file manually"; \
		fi; \
	else \
		echo "✅ Environment file already exists"; \
	fi
	@echo ""
	@echo "🐳 Starting Docker services..."
	@make up
	@echo ""
	@echo "⏳ Waiting for services to be ready..."
	@sleep 15
	@echo "🔍 Checking if PostgreSQL container is running..."
	@docker-compose ps postgres | grep -q "Up" || { echo "❌ PostgreSQL container is not running"; echo "   Check Docker logs with: make logs"; exit 1; }
	@echo "✅ PostgreSQL container is running"
	@echo ""
	@echo "📦 Installing backend dependencies..."
	@docker-compose exec -T backend composer install --no-interaction --optimize-autoloader
	@echo ""
	@echo "🔍 Checking database connectivity..."
	@echo "⏳ Waiting for database to be ready..."
	@for i in 1 2 3 4 5 6; do \
		if docker-compose exec -T backend php artisan tinker --execute="echo 'Database connection successful';" 2>/dev/null >/dev/null; then \
			echo "✅ Database is ready!"; \
			break; \
		else \
			echo "⏳ Attempt $$i/6: Database not ready yet..."; \
			if [ $$i -eq 6 ]; then \
				echo "❌ Database failed to become ready after 6 attempts"; \
				echo "   Check Docker logs with: make logs"; \
				exit 1; \
			fi; \
			sleep 10; \
		fi; \
	done
	@echo ""
	@echo "🔑 Generating application key..."
	@docker-compose exec -T backend php artisan key:generate --force
	@echo ""
	@echo "🗄️ Setting up database..."
	@echo "   Running migrations..."
	@docker-compose exec -T backend php artisan migrate:fresh --force || { echo "❌ Migration failed"; exit 1; }
	@echo "   Seeding database..."
	@docker-compose exec -T backend php artisan db:seed --force || { echo "❌ Seeding failed"; exit 1; }
	@echo "   Verifying database seeding..."
	@docker-compose exec -T backend php artisan tinker --execute="echo 'Users created: ' . App\Models\User::count();" || { echo "❌ Database verification failed"; exit 1; }
	@echo "✅ Database setup complete!"
	@echo ""
	@echo "📱 Installing frontend dependencies..."
	@make npm-install
	@echo ""
	@echo "🏗️ Building frontend assets..."
	@make npm-build
	@echo ""
	@echo "🎉 Launch complete! Your ACME CSR Platform is ready."
	@echo ""
	@echo "🌐 Access your application:"
	@echo "   📱 Frontend: http://localhost:3000"
	@echo "   🔧 Backend API: http://localhost:8000"
	@echo "   📊 Database: PostgreSQL (accessible via Docker)"
	@echo ""
	@echo "🛠️ Useful commands:"
	@echo "   make help          - Show all available commands"
	@echo "   make logs          - View application logs"
	@echo "   make test          - Run tests"
	@echo "   make dev           - Start development mode"
	@echo "   make down          - Stop all services"
	@echo ""
	@echo "🔑 Login Credentials:"
	@echo "   - Admin: admin@acme.com / password"
	@echo "   - Employee: john.doe@acme.com / password"
	@echo ""
	@echo "✨ Happy coding!"

dev: ## Start development environment
	@echo "🚀 Starting development environment..."
	@make up
	@echo "✅ Services started!"
	@echo "📱 Frontend: http://localhost:3000"
	@echo "🔧 Backend API: http://localhost:8000"

fresh: ## Fresh start: rebuild, migrate, seed
	@echo "🔄 Fresh development setup..."
	@make down
	@make up
	@make migrate-fresh
	@make seed
	@echo "✨ Fresh environment ready!"

qa: test test-pest npm-test phpstan npm-lint ## Run all quality assurance checks

deploy-check: qa ## Check if code is ready for deployment
	@echo "✅ All quality checks passed! Code is ready for deployment."

# =============================================================================
# Database Utilities
# =============================================================================

db-reset: ## Reset database (fresh migration + seed)
	@make migrate-fresh
	@make seed
	@echo "🗄️ Database reset complete!"

db-backup: ## Create database backup
	docker-compose exec -T postgres pg_dump -U acme_user acme_csr_platform > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "💾 Database backup created!"

# =============================================================================
# Logs & Monitoring
# =============================================================================

tail-backend: ## Tail backend application logs
	docker-compose exec -T backend tail -f storage/logs/laravel.log

tail-nginx: ## Tail nginx access logs
	docker-compose logs -f nginx

monitor: ## Show real-time service status
	watch docker-compose ps

# =============================================================================
# Cleanup
# =============================================================================

clean: ## Clean up ACME project Docker resources only
	docker-compose down -v
	@echo "🧹 ACME project cleanup complete!"

clean-all: ## Clean up ACME project and remove unused Docker resources
	docker-compose down -v
	@echo "🗑️  Removing unused Docker images and containers..."
	docker image prune -f --filter "label=com.docker.compose.project=acme-corp"
	docker container prune -f --filter "label=com.docker.compose.project=acme-corp"
	@echo "💥 ACME project cleanup complete!"

# =============================================================================
# Quick Development Tasks
# =============================================================================

quick-test: ## Quick test run (specific to current changes)
	@echo "🏃 Running quick tests..."
	@make test-feature

quick-check: ## Quick quality check
	@echo "⚡ Quick quality check..."
	@make phpstan
	@make format-check

fix: ## Auto-fix common issues
	@echo "🔧 Auto-fixing code issues..."
	@make format
	@make cache-clear
	@echo "✅ Auto-fix complete!"
