# ACME Corp CSR Donation Platform

A comprehensive Corporate Social Responsibility donation platform built with Laravel backend and Vue.js frontend.

## Project Structure

```
acme-csr-platform/
├── backend/             # Laravel 12.x API
├── frontend/            # Vue.js 3 SPA ✅
├── docker/              # Docker configuration ✅
├── docs/                # Documentation
└── README.md
```

## Backend Implementation Status ✅

### 🎯 Completed Features

#### Database Architecture
- ✅ **Users Table**: Extended with employee_id, department, role, is_admin
- ✅ **Campaigns Table**: Complete campaign management with categories, targets, dates
- ✅ **Donations Table**: Full donation tracking with payment methods and status
- ✅ **Campaign Categories**: Organized donation categories
- ✅ **Payment Transactions**: Payment provider integration ready
- ✅ **System Settings**: Configurable platform settings
- ✅ **Audit Logs**: Complete action logging and audit trail

#### Authentication & Authorization
- ✅ **Laravel Sanctum**: SPA authentication implemented
- ✅ **Role-Based Access Control**: Employee and Admin roles
- ✅ **Admin Middleware**: Secure admin route protection
- ✅ **User Profile Management**: Complete profile and password management
- ✅ **Audit Logging**: All authentication actions logged

#### API Endpoints

##### Public Endpoints
- `GET /api/campaigns` - List campaigns with filtering
- `GET /api/campaigns/trending` - Get trending campaigns
- `GET /api/campaigns/ending-soon` - Get campaigns ending soon
- `GET /api/campaigns/{id}` - Get campaign details
- `GET /api/categories` - List campaign categories
- `POST /api/login` - Employee authentication

##### Protected Endpoints
- `GET /api/user` - Get user profile
- `PUT /api/profile` - Update profile
- `PUT /api/profile/password` - Change password
- `POST /api/campaigns` - Create campaign
- `PUT /api/campaigns/{id}` - Update campaign
- `DELETE /api/campaigns/{id}` - Delete campaign
- `GET /api/donations/my-donations` - User's donations
- `POST /api/donations` - Make donation
- `GET /api/donations/{id}/receipt` - Download receipt
- `POST /api/logout` - Logout user

#### Core Features
- ✅ **Campaign Management**: Create, update, delete campaigns
- ✅ **Donation Processing**: Mock payment processing with status tracking
- ✅ **Campaign Progress**: Real-time progress calculation
- ✅ **Payment Transactions**: Complete transaction logging
- ✅ **Receipt Generation**: Digital donation receipts
- ✅ **Data Validation**: Comprehensive request validation
- ✅ **Error Handling**: Proper error responses and logging

#### Payment & Services Architecture
- ✅ **Payment Providers**: Strategy pattern with Mock, Stripe, PayPal interfaces
- ✅ **Service Classes**: CampaignService, DonationService, PaymentService, NotificationService
- ✅ **Business Logic**: Separated from controllers into dedicated services
- ✅ **Configuration**: Payment provider configuration system

#### Code Quality
- ✅ **PHPStan Level 8**: Static analysis configuration (246 issues identified for improvement)
- ✅ **Eloquent Models**: All relationships properly defined
- ✅ **Database Migrations**: Complete schema with indexes
- ✅ **Seeders**: Sample data for categories, users, and campaigns

## Frontend Implementation Status ✅

### 🎯 Completed Features

#### Vue 3 Application Architecture
- ✅ **Vue 3 + TypeScript**: Modern composition API with full type safety
- ✅ **Vite Build System**: Fast development and optimized production builds
- ✅ **Vue Router 4**: Client-side routing with authentication guards
- ✅ **Pinia State Management**: Reactive state management for auth, campaigns, donations

#### UI Framework & Styling
- ✅ **TailwindCSS v3**: Utility-first CSS framework with custom component styles
- ✅ **PostCSS + Autoprefixer**: Optimized CSS processing pipeline
- ✅ **Responsive Design**: Mobile-first responsive layout
- ✅ **Component Library**: Reusable UI components (buttons, forms, cards)

#### Authentication & Security
- ✅ **Sanctum Integration**: Seamless Laravel Sanctum token authentication
- ✅ **Route Guards**: Protected routes with role-based access control
- ✅ **Token Management**: Automatic token refresh and storage
- ✅ **User Profile**: Complete profile management interface

#### Core Application Features
- ✅ **Campaign Management**: Browse, view, create, and manage campaigns
- ✅ **Donation Processing**: Complete donation flow with payment integration
- ✅ **Campaign Categories**: Category filtering and organization
- ✅ **User Dashboard**: Personal donation history and campaign tracking
- ✅ **Admin Panel Structure**: Admin dashboard, user management, reports framework

#### Developer Experience
- ✅ **TypeScript Integration**: Full type safety across the application
- ✅ **ESLint + Prettier**: Code quality and formatting tools
- ✅ **Vitest**: Testing framework setup
- ✅ **Vue DevTools**: Development debugging tools

### Frontend Application Structure
```
frontend/
├── src/
│   ├── components/           # Reusable UI components
│   │   ├── common/          # Navigation, Footer, Layout
│   │   └── campaigns/       # Campaign-specific components
│   ├── views/               # Page components
│   │   ├── auth/            # Login, Profile pages
│   │   ├── admin/           # Admin dashboard pages
│   │   └── campaigns/       # Campaign pages
│   ├── stores/              # Pinia state management
│   │   ├── auth.ts          # Authentication state
│   │   ├── campaigns.ts     # Campaign data
│   │   └── donations.ts     # Donation processing
│   ├── services/            # API integration
│   ├── router/              # Vue Router configuration
│   └── layouts/             # Application layouts
└── public/                  # Static assets
```

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- PostgreSQL
- Node.js ^20.19.0 || >=22.12.0 (for frontend development)
- Docker & Docker Compose (recommended)

### Quick Start with Docker (Recommended)

1. **Clone and Setup**
   ```bash
   git clone <repository-url>
   cd acme-csr-platform
   cp docker.env.example .env
   ```

2. **Start All Services**
   ```bash
   ./docker-dev.sh
   # or manually:
   docker-compose up -d
   ```

3. **Access the Application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000
   - PostgreSQL: localhost:5432
   - Redis: localhost:6379

4. **Run Tests**
   ```bash
   docker-compose exec backend php artisan test
   ```

### Traditional Setup (Alternative)

1. **Install Dependencies**
   ```bash
   cd backend
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   
   Update your `.env` file with:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=acme_csr_platform
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

3. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=CampaignCategorySeeder
   php artisan db:seed --class=UserSeeder
   ```

4. **Run Development Server**
   ```bash
   php artisan serve
   ```

### Frontend Setup

1. **Install Dependencies**
   ```bash
   cd frontend
   npm install
   ```

2. **Environment Configuration**
   Create `.env.local` file in frontend directory:
   ```env
   VITE_API_BASE_URL=http://localhost:8000/api
   ```

3. **Run Development Server**
   ```bash
   npm run dev
   ```

The frontend will be available at `http://localhost:5173`

### Default Users
- **Admin**: admin@acme.com / password
- **Employee**: john.doe@acme.com / password
- **Employee**: jane.smith@acme.com / password

## API Testing

You can test the API endpoints using tools like Postman or curl:

### Authentication
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@acme.com", "password": "password"}'
```

### Create Campaign (requires authentication)
```bash
curl -X POST http://localhost:8000/api/campaigns \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Help Local Community",
    "description": "Supporting our local community center",
    "target_amount": 5000,
    "start_date": "2025-01-02",
    "end_date": "2025-02-01",
    "category_id": 3
  }'
```

## Development Plan Progress

### ✅ Completed (Weeks 1-4: Full Stack Foundation)
- [x] Project setup and configuration
- [x] Database design and migrations
- [x] Eloquent models with relationships
- [x] Laravel Sanctum authentication
- [x] Role-based access control
- [x] Core API endpoints (all campaign, donation, auth endpoints)
- [x] Payment system foundation with Strategy pattern
- [x] Audit logging system
- [x] PHPStan configuration
- [x] Service layer architecture (CampaignService, DonationService, PaymentService)
- [x] Vue 3 project setup with Vite
- [x] Pinia state management
- [x] TailwindCSS UI framework
- [x] Authentication integration
- [x] Campaign management interface
- [x] Donation processing UI
- [x] Admin dashboard structure
- [x] Complete routing with guards
- [x] TypeScript integration

### 🚧 In Progress (Week 5: Advanced Features & Testing)
- [ ] Backend testing suite with PHPUnit (configured, some tests failing)
- [ ] Admin dashboard functionality implementation
- [ ] Email notification system
- [ ] Advanced payment provider integration (Stripe/PayPal)
- [ ] Report generation APIs

## 🚀 Next Development Steps

### Priority Development Tasks

1. **🔧 Polish Tests** - Fix remaining 14 test issues (quick wins)
   - Resolve category factory unique constraint violations
   - Fix payment method validation alignment
   - Standardize API response structures

2. **📝 Code Quality** - PHPStan Level 8 compliance (246 issues to fix)
   - Add return types to controllers and services
   - Specify generic types for Eloquent relationships
   - Implement null safety improvements
   - Fix array type specifications

3. **📊 Admin Dashboard** - Build advanced admin analytics & reporting
   - Campaign performance metrics
   - User engagement analytics
   - Donation trend analysis
   - Export functionality

4. **🐳 Docker Setup** - Containerized development environment ✅
   - Multi-container setup (Laravel, PostgreSQL, Redis) ✅
   - Development and production configurations ✅
   - Environment automation ✅

5. **💳 Payment Integration** - Real Stripe/PayPal implementation
   - Replace mock payment provider
   - Webhook handling for payment events
   - Refund and chargeback processing

6. **📧 Email Notifications** - Campaign updates & donation receipts
   - Campaign milestone notifications
   - Donation confirmation emails
   - Receipt generation and delivery

### 🔮 Future Enhancements
- [ ] Frontend testing with Vitest
- [ ] Real-time notifications with WebSockets
- [ ] Advanced campaign analytics
- [ ] Performance optimization
- [ ] Security hardening
- [ ] CI/CD pipeline setup
- [ ] Production deployment guide

## Code Quality

### Static Analysis with PHPStan

Run static analysis:
```bash
# With Docker (recommended)
docker-compose exec backend ./vendor/bin/phpstan analyse --memory-limit=512M

# Traditional
./vendor/bin/phpstan analyse --memory-limit=512M
```

### Current PHPStan Status: Level 8
- **Configuration**: Set to maximum strictness (Level 8)
- **Issues Found**: 246 errors identified
- **Memory Requirement**: 512M+ recommended for full analysis

### Main Issue Categories Identified:
1. **Missing Return Types** - Controllers and services need explicit return types
2. **Generic Type Specifications** - Eloquent relationships need type parameters
3. **Array Value Types** - Iterable parameters need value type specifications
4. **Null Safety** - Property access on potentially null objects
5. **Type Mismatches** - Function parameter type validation

### Code Quality Improvement Plan:
- [ ] Add return types to all controller methods
- [ ] Specify generic types for Eloquent relationships
- [ ] Add array value type specifications
- [ ] Implement null safety checks
- [ ] Fix type mismatches in function calls

## Architecture Highlights

### Infrastructure & Deployment
- **Docker Containerization**: Complete multi-service setup
- **PostgreSQL Database**: Production-ready database with testing configuration
- **Redis Integration**: Caching, sessions, and queue management
- **Nginx Reverse Proxy**: Production-ready web server configuration

### Design Patterns Used
- **Repository Pattern**: For data access abstraction
- **Strategy Pattern**: Ready for payment provider switching
- **Observer Pattern**: For audit logging
- **Factory Pattern**: For model creation and testing

### Security Features
- Laravel Sanctum SPA authentication
- CSRF protection
- SQL injection prevention
- Input validation and sanitization
- Audit logging for all sensitive operations
- Role-based access control

### Performance Optimizations
- Database indexing on frequently queried columns
- Eager loading relationships
- Pagination for large datasets
- Query optimization with proper joins

## Contributing

This project follows Laravel best practices and PSR standards. Key conventions:

- Use type hints for all parameters and return types
- Follow Laravel naming conventions
- Add comprehensive validation to all endpoints
- Log all significant user actions
- Write descriptive commit messages

## Support

For technical questions or issues:
1. Check the Laravel documentation
2. Review the development plan in `acme-csr-platform-plan.md`
3. Examine the API endpoints in `routes/api.php`

---

**Status**: Full-stack foundation completed ✅ Backend + Frontend operational. Docker infrastructure complete ✅. Ready for testing fixes, advanced features, and production deployment.
