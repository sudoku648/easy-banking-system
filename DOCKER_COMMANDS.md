# Docker Commands Quick Reference

## Integrated Development (Recommended for full-stack work)

Start backend + frontend together:
```bash
make dev              # Start all services
make dev-stop         # Stop all services
make fixtures         # Load test data
```

**Access:**
- Backend: http://localhost:8080
- Frontend: http://localhost:3000
- Database: localhost:54322 (postgres/postgres)

---

## Backend Test Environment (Independent)

For backend unit/integration testing only:
```bash
make backend-start    # Start backend test environment
make backend-stop     # Stop backend test environment
make test             # Run PHPUnit tests
make test suite=unit  # Run specific test suite
make analyse          # Static analysis (ECS + PHPStan)
```

**Use when:** Running backend tests without needing frontend.

---

## Frontend Test Environment (Independent)

For frontend development/testing only:
```bash
make frontend-start   # Start frontend test environment
make frontend-stop    # Stop frontend test environment
make frontend-logs    # View frontend logs
make frontend-restart # Restart frontend container
```

**Access:** http://localhost:3001

**Use when:** Working on frontend without needing backend.

---

## E2E Test Environment (Backend + Frontend for testing)

For running Playwright E2E tests:
```bash
make e2e-start        # Start E2E environment
make e2e-stop         # Stop E2E environment
make e2e              # Run E2E tests
make e2e-ui           # Run E2E tests with UI
make e2e-debug        # Debug E2E tests
make e2e-logs         # View all logs
```

**Access:**
- Backend: http://localhost:8081
- Frontend: http://localhost:3000

**Use when:** Running Playwright tests.

---

## Frontend Local Development (Without Docker)

Run frontend with Node.js directly:
```bash
make frontend-install # Install dependencies
make frontend-dev     # Start Vite dev server
make frontend-build   # Build for production
```

**Access:** http://localhost:3000

**Use when:** Prefer local Node.js over Docker.

---

## Common Scenarios

### Scenario 1: Full-Stack Development
```bash
make dev              # Start everything
# Edit code, hot-reload works automatically
make fixtures         # Load sample data if needed
make dev-stop         # When done
```

### Scenario 2: Backend Testing
```bash
make backend-start    # Start backend
make test             # Run all tests
make test suite=unit  # Run unit tests only
make backend-stop     # When done
```

### Scenario 3: Frontend Testing
```bash
make frontend-start   # Start frontend
# Access http://localhost:3001
make frontend-stop    # When done
```

### Scenario 4: E2E Testing
```bash
make e2e-start        # Start environment
make e2e              # Run tests
make e2e-stop         # When done
```

---

## Troubleshooting

### Check running containers
```bash
docker ps | grep easy-banking
```

### View logs
```bash
docker logs easy-banking-service-ebs-dev
docker logs easy-banking-service-frontend-dev
docker logs easy-banking-service-nginx-dev
```

### Stop everything
```bash
make dev-stop
make backend-stop
make frontend-stop
make e2e-stop
```

### Remove all project containers
```bash
docker ps -a | grep easy-banking | awk '{print $1}' | xargs docker rm -f
```

### Clean networks
```bash
docker network prune
```

---

## Environment Ports

| Environment | Backend | Frontend | Database |
|-------------|---------|----------|----------|
| Development | 8080    | 3000     | 54322    |
| Backend Test| -       | -        | -        |
| Frontend Test| -      | 3001     | -        |
| E2E         | 8081    | 3000     | -        |

---

## Network Isolation

Each environment has its own Docker network:
- `dev-network` - Development environment
- `backend-test-network` - Backend tests
- `frontend-test-network` - Frontend tests
- `e2e-network` - E2E tests

This means **all environments can run simultaneously without conflicts**.

---

## Which Command Should I Use?

| I want to...                           | Command                |
|----------------------------------------|------------------------|
| Develop full-stack feature             | `make dev`             |
| Run backend unit tests                 | `make backend-start` + `make test` |
| Work on frontend UI                    | `make frontend-start`  |
| Run end-to-end tests                   | `make e2e-start` + `make e2e` |
| Load sample data                       | `make fixtures`        |
| Stop everything                        | `make dev-stop`        |

---

For detailed information, see [DOCKER_ENVIRONMENTS.md](./DOCKER_ENVIRONMENTS.md)
