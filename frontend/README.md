# ACME Corp CSR Platform - Frontend

A Vue 3 TypeScript SPA providing the user interface for the ACME Corp Corporate Social Responsibility donation platform.

## 🚀 Features

### Modern Vue 3 Stack
- **Vue 3 Composition API** - Modern reactive framework with TypeScript
- **Vite Build System** - Fast development and optimized production builds
- **Vue Router 4** - Client-side routing with authentication guards
- **Pinia State Management** - Reactive state management for complex data flows

### UI/UX
- **TailwindCSS v3** - Utility-first CSS framework with custom components
- **Responsive Design** - Mobile-first responsive layout
- **Modern Component Library** - Reusable UI components (buttons, forms, cards)
- **Intuitive Navigation** - Clear user flows and breadcrumbs

### Authentication & Security
- **Laravel Sanctum Integration** - Seamless token-based authentication
- **Route Guards** - Protected routes with role-based access control
- **Token Management** - Automatic token refresh and secure storage
- **Session Persistence** - Remember user sessions across browser sessions

### Core Application Features
- **Campaign Browsing** - Browse and filter donation campaigns
- **Campaign Management** - Create and manage campaigns (admin)
- **Donation Processing** - Complete donation flow with payment integration
- **User Dashboard** - Personal donation history and campaign tracking
- **Admin Panel** - User management, analytics, and reporting tools
- **Receipt Downloads** - Digital donation receipt generation

## 🏗️ Application Architecture

```
src/
├── components/              # Reusable UI components
│   ├── common/             # Navigation, Footer, Layout components
│   ├── campaigns/          # Campaign-specific components
│   └── forms/              # Form components with validation
├── views/                  # Page components (route targets)
│   ├── auth/               # Login, Profile pages
│   ├── admin/              # Admin dashboard pages
│   ├── campaigns/          # Campaign browsing and management
│   └── donations/          # Donation history and processing
├── stores/                 # Pinia state management
│   ├── auth.ts             # Authentication state
│   ├── campaigns.ts        # Campaign data and operations
│   ├── donations.ts        # Donation processing state
│   └── counter.ts          # Example store (remove in production)
├── services/               # API integration layer
│   └── api.ts              # Axios configuration and API calls
├── router/                 # Vue Router configuration
│   └── index.ts            # Route definitions and guards
├── layouts/                # Application layout components
│   └── DefaultLayout.vue   # Main application layout
├── composables/            # Reusable composition functions
├── utils/                  # Utility functions and helpers
└── assets/                 # Static assets and global styles
```

## 🎨 UI Components

### Common Components
- `AppHeader.vue` - Navigation header with auth state
- `AppFooter.vue` - Footer with company information
- `LoadingSpinner.vue` - Loading indicators

### Campaign Components
- `CampaignCard.vue` - Campaign display cards
- `CampaignForm.vue` - Campaign creation/editing form
- `CampaignProgress.vue` - Progress indicators and metrics

### Form Components  
- `BaseInput.vue` - Styled input components
- `BaseButton.vue` - Consistent button styling
- `FormValidator.vue` - Form validation with Yup

## 📊 State Management (Pinia)

### Auth Store (`stores/auth.ts`)
- User authentication state
- Login/logout actions
- Token management
- Role-based permissions

### Campaigns Store (`stores/campaigns.ts`)
- Campaign data caching
- CRUD operations
- Filtering and pagination
- Real-time updates

### Donations Store (`stores/donations.ts`)
- Donation processing workflow
- Payment state management
- Receipt downloads
- User donation history

## 🛠️ Setup & Development

### Prerequisites
- Node.js 20.19+ (recommended: use nvm)
- npm or yarn package manager

### Installation

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Environment Configuration**
   Create `.env.local` file:
   ```env
   VITE_API_BASE_URL=http://localhost:8000/api
   VITE_APP_NAME="ACME CSR Platform"
   ```

3. **Development Server**
   ```bash
   npm run dev
   ```

The application will be available at `http://localhost:5173`

### Available Scripts

```bash
# Development
npm run dev              # Start Vite dev server with HMR

# Building
npm run build            # Type-check and build for production
npm run preview          # Preview production build locally

# Code Quality
npm run lint             # Run ESLint and auto-fix issues
npm run format           # Format code with Prettier
npm run type-check       # Run TypeScript type checking

# Testing
npm run test:unit        # Run unit tests with Vitest
```

## 🧪 Testing

### Unit Testing with Vitest
```bash
npm run test:unit
```

### Test Structure
```
src/__tests__/
├── components/         # Component unit tests
├── stores/            # Pinia store tests
├── utils/             # Utility function tests
└── setup.ts           # Test setup and mocks
```

### Writing Tests
- Use Vitest for unit testing
- Test components with Vue Test Utils
- Mock API calls for isolated testing
- Test Pinia stores independently

## 🔧 Configuration

### TypeScript Configuration
- `tsconfig.json` - Base TypeScript configuration
- `tsconfig.app.json` - Application-specific config
- `tsconfig.node.json` - Node.js tools configuration
- `tsconfig.vitest.json` - Testing environment config

### Build Configuration
- `vite.config.ts` - Vite build configuration
- `tailwind.config.js` - TailwindCSS customization
- `postcss.config.js` - PostCSS processing pipeline

### Code Quality
- `eslint.config.ts` - ESLint rules and plugins
- `.prettierrc` - Code formatting configuration

## 🌐 API Integration

### Axios Configuration (`services/api.ts`)
- Base URL configuration
- Request/response interceptors
- Authentication token handling
- Error handling and retry logic

### API Endpoints
```javascript
// Authentication
POST /api/login
POST /api/logout

// Campaigns
GET  /api/campaigns
POST /api/campaigns
PUT  /api/campaigns/:id
DELETE /api/campaigns/:id

// Donations
GET  /api/donations/my-donations
POST /api/donations
GET  /api/donations/:id/receipt

// User Profile
GET  /api/user
PUT  /api/profile
PUT  /api/profile/password
```

## 🎯 Routing & Navigation

### Route Structure
```javascript
/                        # Dashboard/Home
/login                   # Authentication
/campaigns               # Browse campaigns
/campaigns/:id           # Campaign details
/campaigns/create        # Create campaign (admin)
/donations               # Donation history
/profile                 # User profile
/admin                   # Admin dashboard (admin only)
/admin/users             # User management (admin only)
/admin/reports           # Analytics (admin only)
```

### Route Guards
- **Authentication Guard** - Redirects unauthenticated users
- **Admin Guard** - Restricts admin-only routes
- **Guest Guard** - Redirects authenticated users from login

## 🚀 Production Deployment

### Build for Production
```bash
npm run build
```

### Environment Variables
```env
VITE_API_BASE_URL=https://api.acme-corp.com/api
VITE_APP_NAME="ACME CSR Platform"
```

### Docker Deployment
```bash
# Build production image
docker build -t acme-csr-frontend .

# Run container
docker run -p 3000:3000 acme-csr-frontend
```

## 🔒 Security Considerations

- **XSS Prevention** - Vue's built-in template sanitization
- **CSRF Protection** - Sanctum CSRF token handling
- **Secure Token Storage** - HTTPOnly cookies for authentication
- **Input Validation** - Client-side validation with Yup
- **Route Protection** - Navigation guards for sensitive areas

## 📱 Browser Support

- **Modern Browsers** - Chrome 87+, Firefox 78+, Safari 14+
- **Mobile Support** - iOS Safari 14+, Chrome Mobile 87+
- **ES2020+ Features** - Uses modern JavaScript features
- **Progressive Enhancement** - Graceful degradation for older browsers

## 🤝 Contributing

### Development Guidelines
- Use TypeScript for all new code
- Follow Vue 3 Composition API patterns
- Implement responsive design (mobile-first)
- Write unit tests for complex components
- Use semantic commit messages

### Code Style
- Consistent component naming (PascalCase)
- Use composition functions for reusable logic
- Prefer `<script setup>` syntax for components
- Follow Vue style guide recommendations

## 🔧 IDE Setup

### Recommended Extensions (VS Code)
- **Vue - Official** (Vue Language Support)
- **TypeScript Vue Plugin (Volar)** 
- **ESLint** - Code linting
- **Prettier** - Code formatting
- **Tailwind CSS IntelliSense** - CSS class suggestions

### Configuration
- Disable Vetur if installed (conflicts with Volar)
- Enable format-on-save for consistent code style
- Configure auto-import for Vue components

---

**Built with Vue 3 • Styled with TailwindCSS • Powered by TypeScript**