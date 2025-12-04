# Frontend - Easy Banking System

This directory contains the React-based frontend application for the Easy Banking System.

## Structure

```
frontend/
├── src/                # Application source code
│   ├── api/               # API client and services
│   ├── components/        # Reusable React components
│   ├── contexts/          # React contexts
│   ├── hooks/             # Custom React hooks
│   ├── i18n/              # Internationalization setup
│   ├── pages/             # Page components
│   ├── utils/             # Utility functions
│   ├── App.jsx            # Main App component
│   └── main.jsx           # Application entry point
├── index.html          # HTML template
├── package.json        # NPM dependencies and scripts
├── vite.config.js      # Vite configuration
└── .eslintrc.cjs       # ESLint configuration
```

## Requirements

- Node.js 18+ and npm
- Backend API running (see [Environment Configuration](#environment-configuration))

## Environment Configuration

The frontend uses environment variables to configure the backend API URL. See [ENVIRONMENT.md](./ENVIRONMENT.md) for detailed configuration guide.

**Quick Setup:**

```bash
# Optional: Create local environment override
cp .env.local.example .env.local
# Edit .env.local if needed (already gitignored)
```

**Environment Files:**
- `.env.development` - Development mode (used by `npm run dev`)
- `.env.production` - Production mode (used by `npm run build`)
- `.env.local` - Local overrides (gitignored, optional)

## Quick Start

From the **root directory** of the project:

```bash
# Install dependencies (first time only)
make frontend-install

# Start development server
make frontend-dev
```

The application will be available at http://localhost:3000

## Development

### Running Manually

If you prefer not to use Make:

```bash
cd frontend
npm install
npm run dev
```

### Available Scripts

From the `frontend/` directory:

```bash
npm run dev       # Start development server
npm run build     # Build for production
npm run preview   # Preview production build
npm run lint      # Run ESLint
```

### API Proxy

The Vite dev server proxies API requests to the backend configured in `VITE_API_URL`:
- Frontend: http://localhost:3000
- Backend: Configured via environment variable (default: http://localhost:8080)
- API requests to `/api/*` are proxied to the backend

See [ENVIRONMENT.md](./ENVIRONMENT.md) for more details on API configuration.

### Building for Production

```bash
# From root directory
make frontend-build
```

This builds the frontend and outputs to `backend/public/build/`, which is served by the Symfony backend in production.

## Tech Stack

- **React 18** - UI library
- **React Router v6** - Routing
- **Axios** - HTTP client
- **i18next** - Internationalization
- **Vite** - Build tool and dev server

## Features

- Customer dashboard with account overview
- Bank account management
- Money transfers (deposit, withdraw, transfer)
- Transaction history
- Employee operations (create accounts, close accounts)
- Multi-language support (Polish, English)
- Responsive design

## Configuration

### Environment Variables

The frontend uses the backend's configuration. API calls are made to `/api` which is proxied to the backend.

### Internationalization

Language files are managed in `src/i18n/locales/`. The application supports:
- English (en)
- Polish (pl)

## Development Tips

- Use React DevTools for debugging
- The Vite dev server provides Hot Module Replacement (HMR)
- API calls are automatically proxied to the backend during development
- Check the browser console for API errors

## Documentation

- [React Migration Guide](../REACT_MIGRATION.md)
- [API Endpoints](../docs/API_ENDPOINTS.md)
