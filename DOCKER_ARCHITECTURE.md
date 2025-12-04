# Docker Architecture Overview

## Visual Structure

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     Easy Banking System - Docker Setup                   │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│  1. INTEGRATED DEVELOPMENT (make dev)                                     │
│  File: docker-compose.dev.yaml                                            │
│  Network: dev-network                                                     │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────┐   ┌──────────┐   ┌───────────┐   ┌────────────────┐  │
│  │ PostgreSQL  │◄──│ PHP-FPM  │◄──│  Nginx    │   │   Frontend     │  │
│  │ :54322      │   │ (EBS)    │   │  :8080    │   │   (Vite)       │  │
│  │ (external)  │   │          │   │ (external)│   │   :3000        │  │
│  └─────────────┘   └──────────┘   └───────────┘   └────────────────┘  │
│       ▲                  ▲               ▲               ▲              │
│       └──────────────────┴───────────────┴───────────────┘              │
│                    All on dev-network                                    │
│                                                                           │
│  Use: Full-stack development with hot-reload                             │
└──────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│  2. BACKEND TEST (make backend-start)                                     │
│  File: backend/docker-compose.yaml                                        │
│  Network: backend-test-network                                            │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────┐   ┌──────────┐                                         │
│  │ PostgreSQL  │◄──│ PHP-FPM  │                                         │
│  │ (test DB)   │   │ (EBS)    │                                         │
│  │ (internal)  │   │          │                                         │
│  └─────────────┘   └──────────┘                                         │
│       ▲                  ▲                                                │
│       └──────────────────┘                                                │
│      backend-test-network                                                 │
│                                                                           │
│  Use: PHPUnit tests, isolated backend testing                            │
└──────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│  3. FRONTEND TEST (make frontend-start)                                   │
│  File: frontend/docker-compose.test.yaml                                  │
│  Network: frontend-test-network                                           │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌────────────────┐                                                      │
│  │   Frontend     │                                                      │
│  │   (Vite)       │                                                      │
│  │   :3001        │                                                      │
│  │   (external)   │                                                      │
│  └────────────────┘                                                      │
│         ▲                                                                 │
│         └─ frontend-test-network                                          │
│                                                                           │
│  Use: Frontend development/testing without backend                        │
└──────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│  4. E2E TEST (make e2e-start)                                             │
│  File: docker-compose.e2e.yaml                                            │
│  Network: e2e-network                                                     │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────┐   ┌──────────┐   ┌───────────┐   ┌────────────────┐  │
│  │ PostgreSQL  │◄──│ PHP-FPM  │◄──│  Nginx    │   │   Frontend     │  │
│  │ (e2e DB)    │   │ (EBS)    │   │  :8081    │   │   (Vite)       │  │
│  │ (internal)  │   │          │   │ (external)│   │   :3000        │  │
│  └─────────────┘   └──────────┘   └───────────┘   └────────────────┘  │
│       ▲                  ▲               ▲               ▲              │
│       └──────────────────┴───────────────┴───────────────┘              │
│                    All on e2e-network                                    │
│                                                                           │
│  Use: Playwright E2E tests                                                │
└──────────────────────────────────────────────────────────────────────────┘
```

## Network Isolation

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Docker Networks                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌─────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │  dev-network    │  │ backend-test-    │  │ frontend-test-   │  │
│  │                 │  │     network      │  │     network      │  │
│  │  • ebs-dev      │  │                  │  │                  │  │
│  │  • nginx-dev    │  │  • ebs-test      │  │  • frontend-test │  │
│  │  • postgres-dev │  │  • postgres-test │  │                  │  │
│  │  • frontend-dev │  │                  │  │                  │  │
│  └─────────────────┘  └──────────────────┘  └──────────────────┘  │
│                                                                      │
│  ┌─────────────────┐                                                │
│  │  e2e-network    │                                                │
│  │                 │                                                │
│  │  • ebs-e2e      │                                                │
│  │  • nginx-e2e    │                                                │
│  │  • postgres-e2e │                                                │
│  │  • frontend-e2e │                                                │
│  └─────────────────┘                                                │
│                                                                      │
│  ✓ All networks are isolated from each other                        │
│  ✓ No communication between environments                            │
│  ✓ Can run all environments simultaneously                          │
└─────────────────────────────────────────────────────────────────────┘
```

## Port Mapping

```
Environment       │ Backend API │ Frontend │ Database │ Status
──────────────────┼─────────────┼──────────┼──────────┼──────────
Development       │   :8080     │  :3000   │  :54322  │ Public
Backend Test      │   none      │  none    │  none    │ Internal
Frontend Test     │   none      │  :3001   │  none    │ Public
E2E               │   :8081     │  :3000   │  none    │ Public
```

## Command Flow

### Development Workflow
```
make dev
  ↓
  ├─→ Build backend (PHP 8.4 + Xdebug)
  ├─→ Build frontend (Node 20 + Vite)
  ├─→ Start PostgreSQL
  ├─→ Start PHP-FPM
  ├─→ Start Nginx
  ├─→ Start Vite dev server
  └─→ Run database migrations & fixtures
```

### Test Workflow
```
make backend-start                make frontend-start
  ↓                                 ↓
  ├─→ Build backend                 └─→ Build frontend
  ├─→ Start PostgreSQL                  ├─→ Start Vite
  ├─→ Start PHP-FPM                     └─→ Expose on :3001
  └─→ Run migrations
```

### E2E Workflow
```
make e2e-start
  ↓
  ├─→ Build backend + frontend
  ├─→ Start all services on e2e-network
  └─→ Setup database with test data
       ↓
     make e2e
       ↓
       └─→ Run Playwright tests
```

## Container Dependencies

```
Development Environment:
  postgres-dev (healthy)
    ↓
  ebs-dev
    ↓
  nginx-dev ←→ frontend-dev
  
Backend Test:
  postgres-test (healthy)
    ↓
  ebs-test

Frontend Test:
  frontend-test (standalone)

E2E Environment:
  postgres-e2e (healthy)
    ↓
  ebs-e2e
    ↓
  nginx-e2e ←→ frontend-e2e
```

## Volume Strategy

```
┌────────────────────────────────────────────────────────────┐
│ Source Code Volumes (Hot-reload)                           │
├────────────────────────────────────────────────────────────┤
│ Development:                                               │
│   ./backend → /app (in ebs-dev, nginx-dev)                 │
│   ./frontend → /app (in frontend-dev)                      │
│                                                            │
│ Backend Test:                                              │
│   ./backend → /app (in ebs-test)                           │
│                                                            │
│ Frontend Test:                                             │
│   ./frontend → /app (in frontend-test)                     │
│                                                            │
│ E2E:                                                       │
│   ./backend → /app (in ebs-e2e, nginx-e2e)                 │
│   ./frontend → /app (in frontend-e2e)                      │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ Persistent Data Volumes                                     │
├────────────────────────────────────────────────────────────┤
│ postgres_dev_data    (Development database)                │
│ postgres_e2e_data    (E2E database)                        │
│                                                            │
│ Note: Test databases are ephemeral (no volume)             │
└────────────────────────────────────────────────────────────┘
```

## Quick Decision Tree

```
What do you want to do?
│
├─ Work on full-stack feature
│  └─→ make dev (Port 8080 + 3000)
│
├─ Write/run backend tests
│  └─→ make backend-start → make test
│
├─ Work on frontend UI
│  ├─→ Option 1: make frontend-start (Port 3001, no backend)
│  └─→ Option 2: make dev (Port 3000, with backend)
│
├─ Run E2E tests
│  └─→ make e2e-start → make e2e
│
└─ Test specific scenario
   ├─→ Backend + Frontend isolated: backend-start + frontend-start
   ├─→ Backend integration: backend-start + make test
   └─→ Frontend mocked API: frontend-start
```

## Summary

| ✅ Can Do | Command |
|-----------|---------|
| Run all environments simultaneously | Yes, all 4 can run at once |
| Hot-reload backend code | Yes (in dev, e2e) |
| Hot-reload frontend code | Yes (in all) |
| Access PostgreSQL directly | Yes (:54322 in dev) |
| Run tests without UI | Yes (backend-start) |
| Debug with Xdebug | Yes (all backend containers) |
| Isolated test databases | Yes (each has own DB) |
| Mix and match environments | Yes (independent networks) |

---

For detailed commands, see [DOCKER_COMMANDS.md](./DOCKER_COMMANDS.md)

For comprehensive guide, see [DOCKER_ENVIRONMENTS.md](./DOCKER_ENVIRONMENTS.md)
