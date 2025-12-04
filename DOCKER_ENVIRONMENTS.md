# Docker Environment Guide

This guide explains the different Docker environments available in the Easy Banking System project and how to use them.

## Overview

The project provides **four independent Docker environments**:

1. **Backend Test Environment** - For running backend unit/integration tests
2. **Frontend Test Environment** - For frontend development and testing (standalone)
3. **Integrated Development Environment** - Backend + Frontend working together
4. **E2E Test Environment** - Backend + Frontend for end-to-end testing

## Quick Start

```bash
# Integrated Development (Backend + Frontend)
make dev                    # Start everything
make dev-stop               # Stop everything

# Backend Tests Only
make backend-start          # Start backend test environment
make backend-stop           # Stop backend

# Frontend Tests Only
make frontend-start         # Start frontend test environment
make frontend-stop          # Stop frontend

# E2E Testing
make e2e-start             # Start E2E environment
make e2e-stop              # Stop E2E environment
make e2e                   # Run E2E tests
```

## Environments in Detail

### 1. Backend Test Environment

**File**: `backend/docker-compose.yaml`

Isolated backend environment for running PHPUnit tests.

**Services**:
- `easy-banking-service-ebs-test` - PHP 8.4 FPM
- `easy-banking-service-postgres-test` - PostgreSQL test database

**Network**: `backend-test-network` (isolated)

**Commands**:
```bash
make backend-start         # Start backend test environment
make backend-stop          # Stop and cleanup
make test                  # Run PHPUnit tests
make analyse               # Run static analysis
```

**Ports**: None exposed (internal only)

**Use Case**: Running backend unit/integration/functional tests in isolation.

---

### 2. Frontend Test Environment

**File**: `frontend/docker-compose.test.yaml`

Standalone frontend environment for development and testing.

**Services**:
- `easy-banking-service-frontend-test` - Vite dev server

**Network**: `frontend-test-network` (isolated)

**Commands**:
```bash
make frontend-start        # Start frontend test environment
make frontend-stop         # Stop and cleanup
make frontend-logs         # View logs
make frontend-restart      # Restart container
```

**Ports**:
- Frontend: http://localhost:3001

**Environment Variables**:
- `NODE_ENV=test`
- `VITE_API_URL=http://localhost:8081`

**Use Case**: Frontend development without needing backend running.

---

### 3. Integrated Development Environment

**File**: `docker-compose.dev.yaml` (root level)

Complete development environment with backend and frontend communicating.

**Services**:
- `easy-banking-service-ebs-dev` - PHP 8.4 FPM
- `easy-banking-service-nginx-dev` - Nginx web server
- `easy-banking-service-postgres-dev` - PostgreSQL database
- `easy-banking-service-frontend-dev` - React/Vite frontend

**Network**: `dev-network` (shared between all services)

**Commands**:
```bash
make dev                   # Start integrated environment
make dev-stop              # Stop and cleanup
make fixtures              # Load database fixtures
```

**Ports**:
- Backend API: http://localhost:8080
- Frontend: http://localhost:3000
- PostgreSQL: localhost:54322

**Use Case**: Full-stack development with hot-reload on both backend and frontend.

---

### 4. E2E Test Environment

**File**: `docker-compose.e2e.yaml` (root level)

Environment specifically configured for Playwright E2E tests.

**Services**:
- `easy-banking-service-ebs-e2e` - PHP 8.4 FPM
- `easy-banking-service-nginx-e2e` - Nginx web server
- `easy-banking-service-postgres-e2e` - PostgreSQL database
- `easy-banking-service-frontend-e2e` - React/Vite frontend

**Network**: `e2e-network` (shared between all services)

**Commands**:
```bash
make e2e-start             # Start E2E environment
make e2e-stop              # Stop and cleanup
make e2e-setup             # Setup database
make e2e                   # Run E2E tests
make e2e-ui                # Run E2E tests in UI mode
make e2e-debug             # Debug E2E tests
make e2e-logs              # View logs
```

**Ports**:
- Backend API: http://localhost:8081
- Frontend: http://localhost:3000

**Use Case**: Running Playwright E2E tests against isolated environment.

---

## Network Architecture

Each environment uses its own isolated Docker network:

- **backend-test-network** - Backend test environment only
- **frontend-test-network** - Frontend test environment only
- **dev-network** - Integrated development (all services communicate)
- **e2e-network** - E2E testing (all services communicate)
- **backend-standalone-network** - Legacy standalone backend

This isolation ensures:
- ✅ Environments don't interfere with each other
- ✅ You can run multiple environments simultaneously
- ✅ Clean separation of concerns
- ✅ Each environment can be started/stopped independently

## Port Mapping

| Environment | Backend | Frontend | Database |
|-------------|---------|----------|----------|
| Backend Test | - | - | - |
| Frontend Test | - | 3001 | - |
| Development | 8080 | 3000 | 54322 |
| E2E | 8081 | 3000 | - |

## Volume Management

### Backend Test
- Source code mounted for hot-reload: `./backend:/app`

### Frontend Test
- Source code mounted: `./frontend:/app`
- Node modules excluded: `/app/node_modules`

### Development
- Backend source: `./backend:/app`
- Frontend source: `./frontend:/app`
- Persistent database: `postgres_dev_data`

### E2E
- Backend source: `./backend:/app`
- Frontend source: `./frontend:/app`
- Persistent database: `postgres_e2e_data`

## Common Workflows

### Working on Backend Features
```bash
# Terminal 1: Start backend test environment
make backend-start

# Terminal 2: Run tests as you develop
make test suite=unit
make test suite=integration
```

### Working on Frontend Features
```bash
# Terminal 1: Start frontend test environment
make frontend-start

# Terminal 2: View logs
make frontend-logs

# Access: http://localhost:3001
```

### Full-Stack Development
```bash
# Start everything
make dev

# Access backend: http://localhost:8080
# Access frontend: http://localhost:3000

# Load sample data
make fixtures

# Stop everything
make dev-stop
```

### Running E2E Tests
```bash
# Terminal 1: Start E2E environment
make e2e-start

# Terminal 2: Run tests
make e2e

# Or run in UI mode for debugging
make e2e-ui

# Stop when done
make e2e-stop
```

## Troubleshooting

### Port Already in Use

If you get port conflicts:
```bash
# Check what's using the port
lsof -i :8080
lsof -i :3000

# Stop all environments
make dev-stop
make backend-stop
make frontend-stop
make e2e-stop
```

### Network Conflicts

If networks exist from previous runs:
```bash
# List networks
docker network ls | grep banking

# Remove specific network
docker network rm dev-network

# Or remove all unused networks
docker network prune
```

### Container Already Exists

If container names conflict:
```bash
# Stop and remove all project containers
docker ps -a | grep easy-banking | awk '{print $1}' | xargs docker rm -f

# Or stop specific environment
make dev-stop
```

### Database Not Initializing

```bash
# Stop environment and remove volumes
make dev-stop  # or backend-stop, e2e-stop

# Start fresh
make dev  # or backend-start, e2e-start
```

### Frontend Can't Reach Backend

Check that you're using the integrated environment:
```bash
# Wrong - independent environments
make backend-start
make frontend-start  # Can't communicate!

# Right - integrated environment
make dev  # Both can communicate
```

## Environment Variables

### Backend (.env files)
- `.env.test` - Used by backend test environment
- `.env.dev` - Used by development and E2E environments

### Frontend
Environment variables set in docker-compose files:
- `NODE_ENV` - Environment mode
- `VITE_API_URL` - Backend API URL

## Best Practices

1. **Use the right environment for your task**
   - Backend tests → `make backend-start`
   - Frontend only → `make frontend-start`
   - Full-stack dev → `make dev`
   - E2E tests → `make e2e-start`

2. **Clean up when switching**
   ```bash
   make dev-stop
   make backend-start
   ```

3. **Check running containers**
   ```bash
   docker ps | grep easy-banking
   ```

4. **View logs for debugging**
   ```bash
   docker logs easy-banking-service-ebs-dev
   docker logs easy-banking-service-frontend-dev
   ```

5. **Use volumes for persistent data**
   - Development database uses named volume
   - E2E database uses named volume
   - Test databases are ephemeral (removed on stop)

## Migration from Old Setup

If you're migrating from the old single docker-compose setup:

**Before**:
```bash
make dev  # Started backend only
# Frontend ran separately with npm
```

**After**:
```bash
# Option 1: Integrated (recommended)
make dev  # Starts both backend and frontend

# Option 2: Separate (if needed)
make backend-start  # Backend only
make frontend-start # Frontend only
```

## Summary

| Command | What It Does | Use When |
|---------|--------------|----------|
| `make dev` | Start backend + frontend together | Full-stack development |
| `make backend-start` | Start backend test environment | Backend testing only |
| `make frontend-start` | Start frontend test environment | Frontend development only |
| `make e2e-start` | Start E2E test environment | Running E2E tests |
| `make test` | Run PHPUnit tests | Testing backend code |
| `make e2e` | Run Playwright tests | Testing user flows |

All environments are independent and can run simultaneously on different ports!
