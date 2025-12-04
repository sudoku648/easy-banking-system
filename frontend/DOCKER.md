# Frontend Docker Configuration

This directory contains Docker configuration for the Easy Banking System frontend application.

## Structure

```
frontend/
├── Dockerfile                    # Multi-stage Dockerfile for dev and production
├── docker-compose.yaml           # Development environment
├── docker-compose.prod.yaml      # Production environment
├── .dockerignore                 # Files to exclude from Docker build
└── docker/
    └── nginx/
        └── default.conf          # Nginx configuration for production
```

## Docker Images

The Dockerfile uses multi-stage builds with the following targets:

- **base**: Common base with Node.js 20 Alpine
- **dependencies**: Installed npm dependencies
- **dev**: Development environment with hot-reload
- **builder**: Build stage for production assets
- **production**: Production-ready image with Nginx

## Development Environment

### Using Docker

Start the frontend in Docker development mode:

```bash
make frontend-docker-dev
```

This will:
- Build the Docker image with the `dev` target
- Mount the source code for hot-reload
- Expose port 3000
- Connect to the `easy-banking-network` to communicate with backend

Access the application at: http://localhost:3000

### Commands

```bash
# Start frontend in Docker
make frontend-docker-dev

# Stop frontend Docker container
make frontend-docker-stop

# View logs
make frontend-docker-logs

# Restart container
make frontend-docker-restart
```

### Using Local Node.js

Alternatively, run without Docker:

```bash
# Install dependencies
make frontend-install

# Start dev server
make frontend-dev
```

## Production Environment

Build and run the production image:

```bash
# Build production image
docker compose -f frontend/docker-compose.prod.yaml build

# Run production container
docker compose -f frontend/docker-compose.prod.yaml up -d
```

The production build:
- Uses Nginx to serve static files
- Includes optimized React build
- Proxies API requests to backend
- Includes security headers and gzip compression
- Exposes port 80

## Environment Variables

### Development

- `NODE_ENV`: Set to `development`
- `VITE_API_URL`: Backend API URL (default: http://localhost:8080)

### Production

- `NODE_ENV`: Set to `production`

## Volumes

### Development
- Source code mounted at `/app`
- `node_modules` excluded to use container's version

### Production
- No volumes - all assets built into image

## Networking

Both development and production containers connect to the `easy-banking-network` bridge network, allowing communication with:
- Backend API (`easy-banking-service-nginx-dev`)
- PostgreSQL database (via backend)

## Ports

- **Development**: 3000
- **Production**: 80

## Build Arguments

The Dockerfile uses `BUILDPLATFORM` for cross-platform builds, supporting both ARM and x86 architectures.

## Nginx Configuration

The production build uses a custom Nginx configuration (`docker/nginx/default.conf`) that:
- Serves static assets with caching
- Handles React Router with fallback to index.html
- Proxies `/api` requests to backend
- Includes security headers
- Enables gzip compression
- Provides `/health` endpoint for health checks

## Troubleshooting

### Container won't start

Check if the network exists:
```bash
docker network ls | grep easy-banking-network
```

If not, start the backend first:
```bash
make dev
```

### Hot-reload not working

Ensure the source code is properly mounted:
```bash
docker compose -f frontend/docker-compose.yaml logs
```

### Production build fails

Clear node_modules and rebuild:
```bash
rm -rf frontend/node_modules
docker compose -f frontend/docker-compose.yaml build --no-cache
```

## Integration with Backend

The frontend expects the backend API to be available at:
- Development: http://localhost:8080 (via Vite proxy)
- Production: Proxied through Nginx to `easy-banking-service-nginx-dev`

Ensure the backend is running before starting the frontend.
