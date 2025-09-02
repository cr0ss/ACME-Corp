# ACME CSR Platform - Development Commands
# =====================================
# This Makefile provides convenient shortcuts for common development tasks.
# All commands run inside Docker containers for consistency.

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
	docker-compose exec backend bash

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker-compose exec backend php artisan $(cmd)

# Database Commands
migrate: ## Run database migrations
	docker-compose exec backend php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	docker-compose exec backend php artisan migrate:fresh

migrate-seed: ## Run migrations and seed database
	docker-compose exec backend php artisan migrate:fresh --seed

seed: ## Seed the database
	docker-compose exec backend php artisan db:seed

# =============================================================================
# Testing & Quality Assurance
# =============================================================================

test: ## Run all tests
	docker-compose exec backend php artisan test

test-unit: ## Run unit tests only
	docker-compose exec backend php artisan test --testsuite=Unit

test-feature: ## Run feature tests only
	docker-compose exec backend php artisan test --testsuite=Feature

test-filter: ## Run specific test (usage: make test-filter filter="AdminTest")
	docker-compose exec backend php artisan test --filter=$(filter)

test-coverage: ## Run tests with coverage report
	docker-compose exec backend php artisan test --coverage

# Static Analysis
phpstan: ## Run PHPStan static analysis
	docker-compose exec backend ./vendor/bin/phpstan analyse

phpstan-baseline: ## Generate PHPStan baseline
	docker-compose exec backend ./vendor/bin/phpstan analyse --generate-baseline

# Code Formatting
format: ## Format code with Laravel Pint
	docker-compose exec backend ./vendor/bin/pint

format-check: ## Check code formatting without making changes
	docker-compose exec backend ./vendor/bin/pint --test

# Security
security: ## Run security checks
	docker-compose exec backend php artisan audit:security

# =============================================================================
# Cache Management
# =============================================================================

cache-clear: ## Clear all caches
	docker-compose exec backend php artisan cache:clear

config-clear: ## Clear configuration cache
	docker-compose exec backend php artisan config:clear

route-clear: ## Clear route cache
	docker-compose exec backend php artisan route:clear

view-clear: ## Clear view cache
	docker-compose exec backend php artisan view:clear

clear-all: config-clear route-clear view-clear cache-clear ## Clear all caches

# =============================================================================
# Frontend Development
# =============================================================================

frontend-shell: ## Open shell in frontend container
	docker-compose exec frontend sh

npm: ## Run npm command (usage: make npm cmd="install")
	docker-compose exec frontend npm $(cmd)

npm-install: ## Install frontend dependencies
	docker-compose exec frontend npm install

npm-dev: ## Run frontend in development mode
	docker-compose exec frontend npm run dev

npm-build: ## Build frontend for production
	docker-compose exec frontend npm run build

npm-test: ## Run frontend tests (run once and exit)
	docker-compose exec frontend npm run test:unit -- --run

npm-test-watch: ## Run frontend tests in watch mode
	docker-compose exec frontend npm run test:unit

npm-test-coverage: ## Run frontend tests with coverage
	docker-compose exec frontend npm run test:unit -- --run --coverage

npm-lint: ## Run frontend linting
	docker-compose exec frontend npm run lint

npm-format: ## Format frontend code
	docker-compose exec frontend npm run format

# =============================================================================
# Development Workflow
# =============================================================================

install: up migrate-seed ## Full installation: start services, run migrations, seed data
	@echo "🎉 Installation complete! Your ACME CSR Platform is ready."
	@echo "📱 Frontend: http://localhost:3000"
	@echo "🔧 Backend API: http://localhost:8000"

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

qa: test npm-test phpstan npm-lint format-check ## Run all quality assurance checks

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
	docker-compose exec postgres pg_dump -U acme_user acme_csr_platform > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "💾 Database backup created!"

# =============================================================================
# Logs & Monitoring
# =============================================================================

tail-backend: ## Tail backend application logs
	docker-compose exec backend tail -f storage/logs/laravel.log

tail-nginx: ## Tail nginx access logs
	docker-compose logs -f nginx

monitor: ## Show real-time service status
	watch docker-compose ps

# =============================================================================
# Cleanup
# =============================================================================

clean: ## Clean up Docker resources (containers, images, volumes)
	docker-compose down -v
	docker system prune -f
	docker volume prune -f
	@echo "🧹 Cleanup complete!"

clean-all: ## Nuclear cleanup (WARNING: removes all Docker data)
	docker-compose down -v
	docker system prune -af
	docker volume prune -f
	@echo "💥 Nuclear cleanup complete!"

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
