# Environment Variables Guide

## Overview

The frontend application uses environment variables to configure the backend API URL for different environments. Vite's environment variable system is used with the `VITE_` prefix.

## Environment Files

### `.env.development`
Used automatically when running `npm run dev` or in Docker development containers.

```env
VITE_API_URL=http://localhost:8080
```

### `.env.production`
Used automatically when running `npm run build`.

```env
VITE_API_URL=
```

**Note**: In production, the frontend is served by the backend (Symfony), so API calls are relative to the same origin (empty string).

### `.env.local` (Optional, gitignored)
For local overrides. Create this file if you need custom settings for your local development.

Copy from example:
```bash
cp .env.local.example .env.local
```

## Environment Variables

### `VITE_API_URL`

**Purpose**: Base URL of the backend API server.

**Values by Environment**:
- **Development (both Docker and non-Docker)**: `http://localhost:8080`
  - Frontend dev server runs on port 3000 (accessible at http://localhost:3000)
  - Browser makes API requests to http://localhost:8080 (mapped to nginx container)
  - **Important**: The URL must be `localhost:8080` (not `nginx:80`) because:
    - `VITE_API_URL` is used by the **browser**, not the Node.js server
    - The browser runs on the user's machine, outside Docker
    - `localhost:8080` is the exposed port that maps to the nginx container
  
- **Production**: `` (empty string)
  - Frontend is built and served by backend
  - API calls are relative (e.g., `/api/auth/login`)
  - No CORS issues since same origin

## How It Works

### API Client (`src/api/client.js`)

The API client reads the environment variable and constructs the base URL:

```javascript
const apiUrl = import.meta.env.VITE_API_URL || '';
const baseURL = apiUrl ? `${apiUrl}/api` : '/api';
```

**Examples**:
- Dev: `VITE_API_URL=http://localhost:8080` → `baseURL=http://localhost:8080/api`
- Prod: `VITE_API_URL=` → `baseURL=/api`

### Vite Config (`vite.config.js`)

The Vite development server uses a proxy for API requests:

```javascript
proxy: {
  '/api': {
    target: env.VITE_API_URL || 'http://localhost:8080',
    changeOrigin: true,
  },
}
```

This allows the frontend to make requests to `/api/*` which get proxied to the backend.

## Usage in Different Scenarios

### 1. Local Development (without Docker)

**Terminal 1** - Backend:
```bash
cd backend
make dev
# Backend runs on http://localhost:8080
```

**Terminal 2** - Frontend:
```bash
cd frontend
npm run dev
# Frontend runs on http://localhost:3000
# Uses .env.development automatically
```

Frontend makes requests to `http://localhost:8080/api` via Vite proxy.

### 2. Docker Development

```bash
make dev  # From root directory
```

- Backend: `http://localhost:8080` (nginx container)
- Frontend: `http://localhost:3000` (frontend container)
- Frontend uses `VITE_API_URL=http://nginx:80` set in docker-compose.dev.yaml
- Containers communicate via Docker network

### 3. Production Build

```bash
cd frontend
npm run build
```

- Uses `.env.production` automatically
- Builds static assets to `backend/public/build/`
- Backend serves the frontend
- API calls are relative (same origin)

## Testing Environment Variables

To test with different API URLs:

### Option 1: Create `.env.local`
```bash
cd frontend
cp .env.local.example .env.local
# Edit .env.local with your custom URL
```

### Option 2: Command-line override
```bash
VITE_API_URL=http://my-backend:9000 npm run dev
```

### Option 3: Docker Compose override
```bash
# In docker-compose.override.yml
services:
  frontend:
    environment:
      - VITE_API_URL=http://custom-backend:8080
```

## Accessing Environment Variables

In your React components or modules:

```javascript
// Accessing the API URL
const apiUrl = import.meta.env.VITE_API_URL;

// Checking the mode
const isDev = import.meta.env.DEV;
const isProd = import.meta.env.PROD;
const mode = import.meta.env.MODE; // 'development' or 'production'
```

**Important**: Only variables prefixed with `VITE_` are exposed to your Vite-processed code.

## Troubleshooting

### CORS Errors in Development

If you see CORS errors:
1. Check that `VITE_API_URL` points to the correct backend
2. Verify backend is running
3. Check Vite proxy configuration

### API Calls Failing in Production

1. Ensure `VITE_API_URL` is empty in production
2. Verify frontend build is in `backend/public/build/`
3. Check backend routing serves the frontend correctly

### Wrong API URL Being Used

1. Check which env file is being loaded
2. Look for `.env.local` which overrides other files
3. Verify environment variable prefix is `VITE_`
4. Restart dev server after changing env files

### API Requests Hit `http://nginx` Instead of `localhost:8080`

**Problem**: Browser console shows errors like "Failed to fetch http://nginx:80/api/..."

**Cause**: The `VITE_API_URL` environment variable in Docker is set to `http://nginx:80`

**Solution**: 
1. Verify `docker-compose.dev.yaml` sets: `VITE_API_URL=http://localhost:8080`
2. Recreate the frontend container: `docker compose -f docker-compose.dev.yaml up -d --force-recreate frontend`
3. Verify: `docker compose -f docker-compose.dev.yaml exec frontend printenv | grep VITE_API_URL`

**Explanation**: 
- `VITE_API_URL` is used by the browser (client-side), not by Node.js
- The browser runs on your machine, not inside Docker
- Docker internal hostnames like `nginx` are not accessible from the browser
- Use `localhost:8080` which is the exposed port mapped to the nginx container

## File Priority

Vite loads environment files in this order (higher priority first):

1. `.env.{mode}.local` (e.g., `.env.development.local`)
2. `.env.{mode}` (e.g., `.env.development`)
3. `.env.local`
4. `.env`

Variables in higher priority files override lower priority files.

## Security Notes

- Never commit `.env.local` or `.env.*.local` files (gitignored)
- Don't put sensitive data in environment variables that start with `VITE_`
- All `VITE_` variables are embedded in the frontend bundle (visible to users)
- For secrets, use backend environment variables instead

## References

- [Vite Environment Variables Documentation](https://vitejs.dev/guide/env-and-mode.html)
- [Docker Compose Environment Variables](https://docs.docker.com/compose/environment-variables/)
