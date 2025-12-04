# Environment Variables - Quick Reference

## TL;DR

```bash
# Development (local)
npm run dev
# Uses: VITE_API_URL=http://localhost:8080

# Production build
npm run build
# Uses: VITE_API_URL= (empty - same origin)

# Custom backend (create .env.local)
echo "VITE_API_URL=http://custom:9000" > .env.local
npm run dev
```

## Environment Files

| File | Purpose | Gitignored |
|------|---------|------------|
| `.env.development` | Development mode | ❌ No |
| `.env.production` | Production mode | ❌ No |
| `.env.test` | Test mode | ❌ No |
| `.env.local` | Local overrides | ✅ Yes |
| `.env.local.example` | Template | ❌ No |

## Variable Reference

| Variable | Development | Production | Docker Dev |
|----------|-------------|------------|------------|
| `VITE_API_URL` | `http://localhost:8080` | `` (empty) | `http://nginx:80` |

## Access in Code

```javascript
// Get the API URL
const apiUrl = import.meta.env.VITE_API_URL;

// Check environment
const isDev = import.meta.env.DEV;         // true in dev
const isProd = import.meta.env.PROD;       // true in prod
const mode = import.meta.env.MODE;         // 'development' or 'production'
```

## Common Tasks

### Use Different Backend
```bash
# Option 1: .env.local file
cp .env.local.example .env.local
# Edit VITE_API_URL in .env.local

# Option 2: Command line
VITE_API_URL=http://backend:9000 npm run dev
```

### Docker Override
```yaml
# docker-compose.override.yml
services:
  frontend:
    environment:
      - VITE_API_URL=http://my-backend:8080
```

### Debug Environment
```bash
# Check what Vite sees
npm run dev -- --debug
```

## File Priority

1. `.env.[mode].local` (highest)
2. `.env.[mode]`
3. `.env.local`
4. `.env` (lowest)

## Important Rules

- ✅ Only `VITE_` prefixed variables are exposed
- ✅ Restart dev server after changing env files
- ❌ Never commit `.env.local` or `.env.*.local`
- ❌ Don't put secrets in `VITE_` variables (visible in bundle)

## Full Documentation

See [ENVIRONMENT.md](./ENVIRONMENT.md) for complete details.
