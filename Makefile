# ACME CSR Platform - Development Commands
# =====================================
# This Makefile provides convenient shortcuts for common development tasks.
# All commands run inside Docker containers for consistency.

.PHONY: help

# Default target
help: ## Show this help message
	@echo "ACME CSR Platform - Available Commands:"
	@echo "======================================"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# =============================================================================
# Core Development Commands
# =============================================================================

launch: ## Complete initial setup and launch (for new developers)
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

# =============================================================================
# Docker Management
# =============================================================================

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

build: ## Build all Docker images
	docker-compose build

rebuild: ## Rebuild all Docker images from scratch
	docker-compose build --no-cache

status: ## Show status of all containers
	docker-compose ps

logs: ## Show logs from all services
	docker-compose logs -f

cleanup: ## Clean up ACME project Docker resources only
	docker-compose down -v
	docker image prune -f --filter "label=com.docker.compose.project=acme-corp"
	docker container prune -f --filter "label=com.docker.compose.project=acme-corp"
	@echo "✅ ACME project cleanup completed"

reset: ## Reset everything (stop, remove, rebuild)
	@echo "⚠️  This will stop all services, remove containers, and rebuild images."
	@read -p "Are you sure? (y/N): " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo ""; \
		echo "🔄 Resetting everything..."; \
		docker-compose down -v --remove-orphans; \
		docker-compose build --no-cache; \
		echo "✅ Reset completed. Run 'make up' to start fresh."; \
	else \
		echo ""; \
		echo "❌ Reset cancelled."; \
	fi

# =============================================================================
# Backend Development
# =============================================================================

shell: ## Open shell in backend container
	docker-compose exec -T backend bash

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker-compose exec -T backend php artisan $(cmd)

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

test: ## Run all tests (using test database)
	docker-compose exec -T backend php artisan test --env=testing

test-pest: ## Run all Pest tests (recommended)
	docker-compose exec -T backend sh -c "APP_ENV=testing ./vendor/bin/pest tests/Pest/"

test-filter: ## Run specific test (usage: make test-filter filter="AdminTest")
	docker-compose exec -T backend php artisan test --filter=$(filter) --env=testing

phpstan: ## Run PHPStan static analysis
	docker-compose exec -T backend ./vendor/bin/phpstan analyse

format: ## Format code with Laravel Pint
	docker-compose exec -T backend ./vendor/bin/pint

format-check: ## Check code formatting without making changes
	docker-compose exec -T backend ./vendor/bin/pint --test

qa: test test-pest phpstan format-check ## Run all quality assurance checks

deploy-check: qa ## Check if code is ready for deployment
	@echo "✅ All quality checks passed! Code is ready for deployment."

# =============================================================================
# Frontend Development
# =============================================================================

npm: ## Run npm command (usage: make npm cmd="install")
	docker-compose exec -T frontend npm $(cmd)

npm-install: ## Install frontend dependencies
	docker-compose exec -T frontend npm install

npm-build: ## Build frontend for production
	docker-compose exec -T frontend npm run build

npm-test: ## Run frontend tests
	docker-compose exec -T frontend npm run test:unit -- --run

npm-lint: ## Run frontend linting
	docker-compose exec -T frontend npm run lint

# =============================================================================
# Pre-commit Hooks
# =============================================================================

pre-commit-install: ## Install pre-commit hooks
	pre-commit install

pre-commit-run: ## Run pre-commit hooks on all files
	pre-commit run --all-files

pre-commit-update: ## Update pre-commit hooks to latest versions
	pre-commit autoupdate

# =============================================================================
# Utility Commands
# =============================================================================

cache-clear: ## Clear all Laravel caches
	docker-compose exec -T backend php artisan cache:clear
	docker-compose exec -T backend php artisan config:clear
	docker-compose exec -T backend php artisan route:clear
	docker-compose exec -T backend php artisan view:clear
	@echo "✅ All caches cleared"

fix: ## Auto-fix common issues
	@echo "🔧 Auto-fixing code issues..."
	@make format
	@make cache-clear
	@echo "✅ Auto-fix complete!"
