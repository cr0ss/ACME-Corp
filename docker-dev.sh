#!/bin/bash

# ACME CSR Platform - Development Docker Management Script

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
    if [[ ! -f "docker-compose.yml" ]]; then
        print_error "docker-compose.yml not found. Are you in the project root?"
        exit 1
    fi
}

# Function to show help
show_help() {
    echo "ACME CSR Platform - Development Docker Management"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  up        Start all services in development mode"
    echo "  down      Stop all services"
    echo "  restart   Restart all services"
    echo "  build     Build all images"
    echo "  rebuild   Rebuild all images from scratch"
    echo "  logs      Show logs for all services"
    echo "  shell     Open shell in backend container"
    echo "  artisan   Run Laravel Artisan commands"
    echo "  npm       Run npm commands in frontend container"
    echo "  test      Run backend tests"
    echo "  migrate   Run database migrations"
    echo "  seed      Run database seeders"
    echo "  fresh     Fresh database (migrate + seed)"
    echo "  status    Show status of all containers"
    echo "  cleanup   Remove unused Docker resources"
    echo "  reset     Reset everything (stop, remove, rebuild)"
    echo "  help      Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 up                    # Start development environment"
    echo "  $0 artisan migrate       # Run migrations"
    echo "  $0 npm run build         # Build frontend"
    echo "  $0 logs backend          # Show backend logs"
    echo "  $0 shell                 # Open backend shell"
}

# Function to start services
start_services() {
    print_status "Starting ACME CSR Platform development environment..."
    docker-compose up -d
    
    print_status "Waiting for services to be ready..."
    sleep 10
    
    # Check if backend is responding
    if curl -s http://localhost:8000/health > /dev/null; then
        print_success "Backend is running at http://localhost:8000"
    else
        print_warning "Backend might still be starting up..."
    fi
    
    # Check if frontend is responding
    if curl -s http://localhost:3000 > /dev/null; then
        print_success "Frontend is running at http://localhost:3000"
    else
        print_warning "Frontend might still be starting up..."
    fi
    
    print_success "Services started successfully!"
    print_status "MailHog is available at http://localhost:8025"
    print_status "PostgreSQL is available at localhost:5432"
    print_status "Redis is available at localhost:6379"
}

# Function to stop services
stop_services() {
    print_status "Stopping all services..."
    docker-compose down
    print_success "All services stopped."
}

# Function to restart services
restart_services() {
    print_status "Restarting all services..."
    docker-compose restart
    print_success "All services restarted."
}

# Function to build images
build_images() {
    print_status "Building Docker images..."
    docker-compose build
    print_success "Images built successfully."
}

# Function to rebuild images
rebuild_images() {
    print_status "Rebuilding Docker images from scratch..."
    docker-compose build --no-cache
    print_success "Images rebuilt successfully."
}

# Function to show logs
show_logs() {
    if [[ -n "$1" ]]; then
        print_status "Showing logs for $1..."
        docker-compose logs -f "$1"
    else
        print_status "Showing logs for all services..."
        docker-compose logs -f
    fi
}

# Function to open shell
open_shell() {
    print_status "Opening shell in backend container..."
    docker-compose exec backend sh
}

# Function to run artisan commands
run_artisan() {
    print_status "Running artisan command: ${*}"
    docker-compose exec backend php artisan "${@}"
}

# Function to run npm commands
run_npm() {
    print_status "Running npm command: ${*}"
    docker-compose exec frontend npm "${@}"
}

# Function to run tests
run_tests() {
    print_status "Running backend tests..."
    docker-compose exec backend ./vendor/bin/phpunit
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    docker-compose exec backend php artisan migrate
    print_success "Migrations completed."
}

# Function to run seeders
run_seeders() {
    print_status "Running database seeders..."
    docker-compose exec backend php artisan db:seed
    print_success "Seeding completed."
}

# Function to fresh database
fresh_database() {
    print_status "Refreshing database (migrate + seed)..."
    docker-compose exec backend php artisan migrate:fresh --seed
    print_success "Database refreshed."
}

# Function to show status
show_status() {
    print_status "Container status:"
    docker-compose ps
    echo ""
    print_status "Service health:"
    echo "Backend API: $(curl -s http://localhost:8000/health 2>/dev/null || echo 'Not responding')"
    echo "Frontend: $(curl -s -o /dev/null -w '%{http_code}' http://localhost:3000 2>/dev/null || echo 'Not responding')"
    echo "PostgreSQL: $(docker-compose exec postgres pg_isready -U acme_user 2>/dev/null || echo 'Not responding')"
    echo "Redis: $(docker-compose exec redis redis-cli ping 2>/dev/null || echo 'Not responding')"
}

# Function to cleanup
cleanup() {
    print_status "Cleaning up unused Docker resources..."
    docker system prune -f
    docker volume prune -f
    print_success "Cleanup completed."
}

# Function to reset everything
reset_everything() {
    print_warning "This will stop all services, remove containers, and rebuild images."
    read -p "Are you sure? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Resetting everything..."
        docker-compose down -v --remove-orphans
        docker-compose build --no-cache
        print_success "Reset completed. Run '$0 up' to start fresh."
    else
        print_status "Reset cancelled."
    fi
}

# Main script logic
main() {
    check_docker
    check_files
    
    case "${1:-help}" in
        up)
            start_services
            ;;
        down)
            stop_services
            ;;
        restart)
            restart_services
            ;;
        build)
            build_images
            ;;
        rebuild)
            rebuild_images
            ;;
        logs)
            show_logs "$2"
            ;;
        shell)
            open_shell
            ;;
        artisan)
            shift
            run_artisan "$@"
            ;;
        npm)
            shift
            run_npm "$@"
            ;;
        test)
            run_tests
            ;;
        migrate)
            run_migrations
            ;;
        seed)
            run_seeders
            ;;
        fresh)
            fresh_database
            ;;
        status)
            show_status
            ;;
        cleanup)
            cleanup
            ;;
        reset)
            reset_everything
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
