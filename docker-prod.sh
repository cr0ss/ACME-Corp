#!/bin/bash

# ACME CSR Platform - Production Docker Management Script

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project name
PROJECT_NAME="acme-csr"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker first."
        exit 1
    fi
}

# Function to check if required files exist
check_files() {
    if [[ ! -f "docker-compose.yml" ]] || [[ ! -f "docker-compose.prod.yml" ]]; then
        print_error "Required Docker compose files not found. Are you in the project root?"
        exit 1
    fi
    
    if [[ ! -f ".env.production" ]]; then
        print_warning ".env.production not found. Please create it with production settings."
    fi
}

# Function to show help
show_help() {
    echo "ACME CSR Platform - Production Docker Management"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  deploy       Deploy to production"
    echo "  update       Update production deployment"
    echo "  down         Stop production services"
    echo "  restart      Restart production services"
    echo "  build        Build production images"
    echo "  logs         Show production logs"
    echo "  status       Show production status"
    echo "  backup       Backup production database"
    echo "  restore      Restore database from backup"
    echo "  migrate      Run production migrations"
    echo "  scale        Scale services"
    echo "  monitor      Show resource usage"
    echo "  cleanup      Clean up old images and containers"
    echo "  rollback     Rollback to previous version"
    echo "  help         Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 deploy                # Deploy to production"
    echo "  $0 scale backend=3       # Scale backend to 3 replicas"
    echo "  $0 backup               # Backup database"
    echo "  $0 logs backend         # Show backend logs"
}

# Function to deploy to production
deploy_production() {
    print_status "Deploying ACME CSR Platform to production..."
    
    # Load production environment
    if [[ -f ".env.production" ]]; then
        export $(cat .env.production | grep -v '^#' | xargs)
    fi
    
    # Build and deploy
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml build
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
    
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Run migrations
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan migrate --force
    
    print_success "Production deployment completed!"
    print_status "Application is available at your configured domain"
}

# Function to update production
update_production() {
    print_status "Updating production deployment..."
    
    # Pull latest images
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml pull
    
    # Rolling update
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps backend
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps frontend
    
    print_success "Production update completed!"
}

# Function to stop production
stop_production() {
    print_warning "Stopping production services..."
    read -p "Are you sure you want to stop production? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
        print_success "Production services stopped."
    else
        print_status "Operation cancelled."
    fi
}

# Function to restart production
restart_production() {
    print_status "Restarting production services..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart
    print_success "Production services restarted."
}

# Function to build production images
build_production() {
    print_status "Building production images..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
    print_success "Production images built successfully."
}

# Function to show production logs
show_production_logs() {
    if [[ -n "$1" ]]; then
        print_status "Showing production logs for $1..."
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f "$1"
    else
        print_status "Showing production logs for all services..."
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f
    fi
}

# Function to show production status
show_production_status() {
    print_status "Production container status:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
    echo ""
    print_status "Resource usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.NetIO}}\t{{.BlockIO}}"
}

# Function to backup database
backup_database() {
    print_status "Creating database backup..."
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec postgres pg_dump -U acme_user acme_csr > "backups/$BACKUP_FILE"
    print_success "Database backup created: backups/$BACKUP_FILE"
}

# Function to restore database
restore_database() {
    if [[ -z "$1" ]]; then
        print_error "Please specify backup file: $0 restore backup_file.sql"
        exit 1
    fi
    
    if [[ ! -f "backups/$1" ]]; then
        print_error "Backup file not found: backups/$1"
        exit 1
    fi
    
    print_warning "This will restore database from backup: $1"
    read -p "Are you sure? This will overwrite current data. (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Restoring database from backup..."
        cat "backups/$1" | docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T postgres psql -U acme_user acme_csr
        print_success "Database restored from backup."
    else
        print_status "Restore cancelled."
    fi
}

# Function to run production migrations
run_production_migrations() {
    print_status "Running production migrations..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan migrate --force
    print_success "Production migrations completed."
}

# Function to scale services
scale_services() {
    if [[ -z "$1" ]]; then
        print_error "Please specify service and scale: $0 scale backend=3"
        exit 1
    fi
    
    print_status "Scaling services: $1"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --scale "$1"
    print_success "Services scaled successfully."
}

# Function to monitor resources
monitor_resources() {
    print_status "Monitoring resource usage (Press Ctrl+C to stop)..."
    docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.NetIO}}\t{{.BlockIO}}"
}

# Function to cleanup
cleanup_production() {
    print_status "Cleaning up old Docker resources..."
    docker system prune -f
    docker image prune -a -f
    print_success "Cleanup completed."
}

# Function to rollback
rollback_production() {
    print_warning "Rollback functionality requires image tags. Please implement according to your CI/CD pipeline."
    print_status "Suggested steps:"
    echo "1. docker-compose -f docker-compose.yml -f docker-compose.prod.yml down"
    echo "2. Change image tags to previous version"
    echo "3. docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d"
}

# Main script logic
main() {
    check_docker
    check_files
    
    # Create backups directory if it doesn't exist
    mkdir -p backups
    
    case "${1:-help}" in
        deploy)
            deploy_production
            ;;
        update)
            update_production
            ;;
        down)
            stop_production
            ;;
        restart)
            restart_production
            ;;
        build)
            build_production
            ;;
        logs)
            show_production_logs "$2"
            ;;
        status)
            show_production_status
            ;;
        backup)
            backup_database
            ;;
        restore)
            restore_database "$2"
            ;;
        migrate)
            run_production_migrations
            ;;
        scale)
            scale_services "$2"
            ;;
        monitor)
            monitor_resources
            ;;
        cleanup)
            cleanup_production
            ;;
        rollback)
            rollback_production
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
