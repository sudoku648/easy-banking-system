# React Frontend Migration

## Overview
The application has been migrated from Twig templates to a React single-page application (SPA).

## Architecture

### Frontend (React + Vite)
- **Location**: `/frontend/src/`
- **Build Output**: `/public/build/`
- **Development Server**: Port 3000
- **Technology Stack**:
  - React 18
  - React Router v6
  - Axios for API calls
  - Vite for build tooling

### Backend (Symfony API)
- **Location**: `/src/**/Presentation/Api/`
- **API Routes**: All routes prefixed with `/api`
- **Controllers**:
  - `AuthApiController`: Authentication endpoints
  - `CustomerApiController`: Customer-specific endpoints
  - `EmployeeApiController`: Employee-specific endpoints

## Directory Structure

```
frontend/
├── src/
│   ├── api/
│   │   └── client.js              # Axios instance with interceptors
│   ├── components/
│   │   ├── Card.jsx               # Reusable card component
│   │   ├── FormField.jsx          # Form input component
│   │   ├── Header.jsx             # App header with navigation
│   │   └── ProtectedRoute.jsx    # Route guard component
│   ├── contexts/
│   │   ├── AuthContext.jsx        # Authentication state management
│   │   └── LocaleContext.jsx      # Internationalization state
│   ├── pages/
│   │   ├── customer/              # Customer pages
│   │   │   ├── Dashboard.jsx
│   │   │   ├── TransactionHistory.jsx
│   │   │   ├── TransferMoney.jsx
│   │   │   ├── ChangePassword.jsx
│   │   │   └── BlockDebitCard.jsx
│   │   ├── employee/              # Employee pages
│   │   │   ├── Dashboard.jsx
│   │   │   ├── SelectCustomer.jsx
│   │   │   ├── TransactionHistory.jsx
│   │   │   ├── DepositMoney.jsx
│   │   │   ├── OpenAccountNewCustomer.jsx
│   │   │   ├── OpenAccountExistingCustomer.jsx
│   │   │   ├── CloseAccount.jsx
│   │   │   ├── IssueDebitCard.jsx
│   │   │   └── BlockDebitCard.jsx
│   │   └── Login.jsx              # Login page
│   ├── App.jsx                    # Main app component with routes
│   ├── main.jsx                   # App entry point
│   └── index.css                  # Global styles
├── index.html                     # HTML template
└── vite.config.js                 # Vite configuration
```

## Getting Started

### 1. Install Dependencies
```bash
make frontend-install
# or
npm install
```

### 2. Development Mode
Start both backend and frontend:

**Terminal 1 - Backend:**
```bash
make dev
```

**Terminal 2 - Frontend:**
```bash
make frontend-dev
# or
npm run dev
```

Access the application:
- Frontend Dev Server: http://localhost:3000
- Backend API: http://localhost:8080/api

The Vite dev server proxies API requests to the backend automatically.

### 3. Production Build
```bash
make frontend-build
# or
npm run build
```

The production build is output to `/public/build/` and served by Symfony.

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Customer Endpoints (ROLE_CUSTOMER)
- `GET /api/customer/accounts` - Get user's bank accounts
- `GET /api/customer/accounts/{id}/transactions` - Get transactions
- `POST /api/customer/transfer` - Transfer money
- `POST /api/customer/change-password` - Change password
- `POST /api/customer/block-debit-card` - Block debit card

### Employee Endpoints (ROLE_EMPLOYEE)
- `GET /api/employee/customers/{id}` - Get customer details
- `GET /api/employee/customers/{id}/accounts` - Get customer accounts
- `GET /api/employee/accounts/{id}/transactions` - Get account transactions
- `POST /api/employee/deposit` - Deposit money
- `POST /api/employee/open-account-new-customer` - Open account for new customer
- `POST /api/employee/open-account-existing-customer` - Open account for existing customer
- `POST /api/employee/close-account` - Close account
- `POST /api/employee/issue-debit-card` - Issue debit card
- `POST /api/employee/block-debit-card` - Block debit card

## Features

### Authentication
- Session-based authentication (Symfony Security)
- JWT support can be added if needed
- Protected routes with role-based access control

### Internationalization (i18n)
- Locale switching (EN/PL)
- Locale stored in localStorage
- API accepts `Accept-Language` header

### State Management
- React Context API for global state:
  - `AuthContext`: User authentication state
  - `LocaleContext`: Language preference

### Routing
- Client-side routing with React Router
- Protected routes with role checking
- Automatic redirects based on user role

## Migration Notes

### What Changed
1. **Twig templates** → **React components**
2. **Form submissions** → **API calls with JSON**
3. **Server-side rendering** → **Client-side SPA**
4. **Symfony routing** → **React Router + API routes**

### What Stayed the Same
- Backend business logic (Commands, Queries, Handlers)
- Domain layer completely unchanged
- Database structure unchanged
- Authentication mechanism (Symfony Security)

### Removed Dependencies
You can now remove (or keep for backward compatibility):
- `symfony/twig-bundle`
- Twig templates in `/templates/`

## TODO Items

Many API controllers have placeholder implementations marked with `@TODO`. These need to be implemented by:

1. Reading the existing Twig controllers
2. Extracting the business logic
3. Adapting form handling to accept JSON
4. Returning JSON responses instead of rendering templates

Example TODO items:
- Transaction history fetching
- Money transfer implementation
- Password change logic
- Account management operations
- Debit card operations

## Production Deployment

1. Build frontend: `npm run build`
2. Ensure `/public/build/` is served by web server
3. Configure web server to route non-API requests to `/public/index.php`
4. Set `APP_ENV=prod` in `.env`

## Troubleshooting

### CORS Issues
If running frontend and backend on different ports in development, ensure CORS is properly configured in Symfony.

### API 401 Errors
Check that session cookies are being sent with API requests. Axios is configured with `withCredentials: true`.

### Build Errors
Clear Vite cache: `rm -rf node_modules/.vite`

### Hot Reload Not Working
Restart the Vite dev server: `npm run dev`
