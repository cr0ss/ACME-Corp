# ACME Corp CSR Platform - Backend API

A Laravel 12.x REST API powering the ACME Corp Corporate Social Responsibility donation platform.

## 🚀 Features

### Core Functionality
- **Employee Authentication** - Laravel Sanctum SPA authentication
- **Campaign Management** - Create, update, and track donation campaigns  
- **Donation Processing** - Multi-provider payment processing with receipts
- **Role-Based Access** - Employee and Admin role separation
- **Audit Logging** - Complete action tracking and compliance

### Payment System
- **Strategy Pattern** - Pluggable payment providers (Mock, Stripe, PayPal)
- **Transaction Tracking** - Complete payment lifecycle management
- **Receipt Generation** - Digital donation receipts
- **Refund Support** - Configurable refund policies

### Email System
- **Donation Confirmations** - Automatic emails to donors with receipt details
- **Campaign Owner Notifications** - Alerts when campaigns receive new donations
- **Laravel Mailables** - Professional email templates with Blade views
- **Development Testing** - Mailhog integration for email testing
- **Queue Support** - Asynchronous email processing (configurable)

### Admin Features
- **User Management** - Employee account administration
- **Analytics Dashboard** - Campaign performance metrics
- **Report Generation** - Donation and engagement reports
- **System Settings** - Platform configuration management

## 🏗️ Architecture

### Design Patterns
- **Repository Pattern** - Data access abstraction
- **Strategy Pattern** - Payment provider switching
- **Observer Pattern** - Audit logging
- **Service Layer** - Business logic separation

### Key Services
- `CampaignService` - Campaign business logic
- `DonationService` - Donation processing workflows  
- `PaymentService` - Payment provider orchestration
- `AuditService` - Action logging and compliance
- `NotificationService` - Email and alert management
- `ReportService` - Analytics and reporting

## 📊 Database Schema

### Core Tables
- **users** - Employee accounts with roles and departments
- **campaigns** - Donation campaigns with targets and dates
- **campaign_categories** - Organized donation categories
- **donations** - Individual donation records
- **payment_transactions** - Payment provider transactions
- **audit_logs** - System action tracking
- **system_settings** - Platform configuration

## 🔌 API Endpoints

### Public Endpoints
```
GET  /api/campaigns                  # List all campaigns
GET  /api/campaigns/trending         # Get trending campaigns
GET  /api/campaigns/ending-soon      # Get campaigns ending soon  
GET  /api/campaigns/{id}             # Get campaign details
GET  /api/categories                 # List campaign categories
POST /api/login                      # Employee authentication
```

### Protected Endpoints (requires auth)
```
GET  /api/user                       # Get user profile
PUT  /api/profile                    # Update profile
PUT  /api/profile/password           # Change password
POST /api/campaigns                  # Create campaign
PUT  /api/campaigns/{id}             # Update campaign
DELETE /api/campaigns/{id}           # Delete campaign
GET  /api/donations/my-donations     # User's donations
POST /api/donations                  # Make donation
GET  /api/donations/{id}/receipt     # Download receipt
POST /api/logout                     # Logout user
```

### Admin Endpoints (requires admin role)
```
GET  /api/admin/users                # List all users
POST /api/admin/users                # Create user
PUT  /api/admin/users/{id}           # Update user
GET  /api/admin/analytics            # Platform analytics
GET  /api/admin/reports              # Generate reports
```

## 🛠️ Setup & Development

### Prerequisites
- Docker & Docker Compose
- Git

### Installation

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd ACME-Corp
   ```

2. **Environment Setup**
   ```bash
   cp docker.env.example .env
   # Edit .env file with your configuration if needed
   ```

3. **Start Development Environment**
   ```bash
   # Start all services (backend, frontend, database, etc.)
   docker-compose up -d
   ```

4. **Install Dependencies & Setup Database**
   ```bash
   # Install PHP dependencies
   docker-compose exec backend composer install
   
   # Generate application key
   docker-compose exec backend php artisan key:generate
   
   # Run migrations and seeders
   docker-compose exec backend php artisan migrate
   docker-compose exec backend php artisan db:seed --class=CampaignCategorySeeder
   docker-compose exec backend php artisan db:seed --class=UserSeeder
   ```

The API will be available at `http://localhost:8000`

### Alternative: Local Development
For local development without Docker, you'll need PHP 8.2+, Composer, PostgreSQL 13+, and Redis.
Follow the traditional Laravel installation process with `composer install` and `php artisan serve`.

### Default Test Users
- **Admin**: admin@acme.com / password
- **Employee**: john.doe@acme.com / password  
- **Employee**: jane.smith@acme.com / password

## 🧪 Testing

### Using Make Commands (Recommended)
The project includes a comprehensive Makefile for easy development. From the project root:

```bash
# See all available commands
make help

# Development workflow
make install          # Full setup: start services, migrate, seed
make dev              # Start development environment  
make fresh            # Fresh start: rebuild, migrate, seed

# Testing
make test             # Run all tests
make test-unit        # Run unit tests only
make test-feature     # Run feature tests only
make test-filter filter="AdminTest"  # Run specific test

# Quality Assurance
make phpstan          # Run static analysis
make format           # Format code with Pint
make qa               # Run all QA checks (test + phpstan + format)

# Database
make migrate          # Run migrations
make migrate-fresh    # Fresh migration
make seed             # Seed database
make db-reset         # Reset database completely

# Docker Management
make up               # Start services
make down             # Stop services  
make shell            # Open backend shell
make logs             # View all logs
```

### Direct Docker Commands (Alternative)
If you prefer direct docker-compose commands:

```bash
# Run all tests
docker-compose exec backend php artisan test

# Run specific test suite
docker-compose exec backend php artisan test --testsuite=Feature
docker-compose exec backend php artisan test --testsuite=Unit

# Run specific test
docker-compose exec backend php artisan test --filter=test_admin_can_access_dashboard
```

### Test Database Configuration
The project uses PostgreSQL for both development and testing environments:
- **Development**: `pgsql` connection to `acme_csr` database
- **Testing**: `pgsql_testing` connection to `acme_csr_test` database
- **PHPUnit**: Automatically configured to use test database

### Static Analysis
```bash
# Using Make (recommended)
make phpstan

# Using Docker directly
docker-compose exec backend ./vendor/bin/phpstan analyse
```

### Code Formatting
```bash
# Using Make (recommended)
make format

# Using Docker directly
docker-compose exec backend ./vendor/bin/pint
```

## 💳 Payment Configuration

### Development (Mock Provider)
```env
PAYMENT_DEFAULT_PROVIDER=mock
MOCK_PAYMENT_SUCCESS_RATE=90
```

**Note**: In testing environment, the mock provider always succeeds to ensure reliable test execution.

### Production (Stripe)
```env
PAYMENT_DEFAULT_PROVIDER=stripe
STRIPE_ENABLED=true
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### Production (PayPal)
```env
PAYMENT_DEFAULT_PROVIDER=paypal
PAYPAL_ENABLED=true
PAYPAL_WEBHOOK_ID=your_webhook_id
```

## 📧 Email Configuration

### Development (Mailhog)
```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

**Mailhog Access**:
- **Web Interface**: http://localhost:8026
- **SMTP Port**: 1025 (internal), 1026 (external)

### Production (SMTP)
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="noreply@acme.com"
MAIL_FROM_NAME="ACME CSR Platform"
```

### Email Templates
- **Donation Confirmation**: `resources/views/emails/donations/confirmation.blade.php`
- **Campaign Owner Notification**: `resources/views/emails/donations/new-donation.blade.php`
- **Customizable**: All emails use Blade templates with donation data

### Email Triggers
- **Donor Confirmation**: Sent automatically when donation is completed
- **Campaign Owner Alert**: Sent to campaign creator for each new donation
- **Queue Support**: Emails can be queued for better performance

## 🐳 Docker Development

Run with Docker Compose:
```bash
# From project root
docker-compose up -d
```

This starts:
- **Laravel API** (port 8000)
- **PostgreSQL database** (port 5432)
- **Redis cache** (port 6379)
- **Nginx reverse proxy** (port 80)
- **Mailhog** (ports 1025/1026 for SMTP, 8026 for web interface)
- **Queue worker** (background job processing)
- **Scheduler** (cron job execution)

## 🔒 Security Features

- **CSRF Protection** - Laravel's built-in CSRF middleware
- **SQL Injection Prevention** - Eloquent ORM with parameter binding
- **Input Validation** - Comprehensive request validation
- **Rate Limiting** - API throttling and abuse prevention
- **Audit Logging** - All sensitive operations logged
- **Role-Based Access Control** - Employee/Admin role separation

## 📈 Performance Optimizations

- **Database Indexing** - Optimized indexes on frequently queried columns
- **Eager Loading** - N+1 query prevention with relationship loading
- **Query Optimization** - Efficient joins and pagination
- **Caching Strategy** - Redis caching for production workloads
- **Queue Processing** - Asynchronous email and notification processing

## 🤝 Contributing

### Code Standards
- Follow PSR-12 coding standards
- Use type hints for all parameters and return types
- Add comprehensive validation to all endpoints
- Log all significant user actions
- Write descriptive commit messages
- Maintain PHPStan Level 8 compliance
- Use PHPUnit attributes instead of doc-comments for tests

### Development Workflow
1. Create feature branch from `main`
2. Write tests for new functionality
3. Ensure all tests pass
4. Run static analysis
5. Submit pull request with clear description

## 📚 Documentation

- **API Documentation**: Available at `/docs/api` when running (via Scramble)
- **Architecture Docs**: See `/docs` directory
- **Database Schema**: View migrations in `/database/migrations`

## 🚨 Troubleshooting

### Common Issues

**Emails not appearing in Mailhog:**
1. Check `MAIL_PORT=1025` in backend environment
2. Verify Mailhog is running on port 1025
3. Clear config cache: `php artisan config:clear`

**Test failures:**
1. Ensure test database is properly configured
2. Check `phpunit.xml` for correct database connection
3. Run `php artisan migrate:fresh --env=testing`

**Payment processing issues:**
1. Verify payment provider is properly configured
2. Check payment validation rules in provider classes
3. Review payment service logs for errors

---

**Built with Laravel 12.x • Powered by PostgreSQL • Secured with Sanctum • Email-ready with Mailhog**