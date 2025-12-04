# Quick Start Guide - Easy Banking System

This guide will help you get the Easy Banking System up and running quickly.

## Project Structure

```
easy-banking-system/
├── backend/          # Symfony PHP application (API)
├── frontend/         # React application (UI)
├── e2e-tests/        # Playwright end-to-end tests
└── docs/             # Documentation
```

## Prerequisites

- **Docker** and **Docker Compose** installed
- **Node.js 18+** and **npm** installed
- **Make** utility (optional but recommended)

## Step-by-Step Setup

### 1. Clone the Repository

```bash
git clone https://github.com/sudoku648/easy-banking-system.git
cd easy-banking-system
```

### 2. Start the Backend

```bash
make dev
```

This will:
- Build and start Docker containers (PHP, PostgreSQL, Nginx)
- Set up the database
- Install dependencies

Wait for the backend to be ready. You should see:
```
Development environment is ready!
Application: http://localhost:8080
Database: localhost:54322 (postgres/postgres)
```

### 3. Load Sample Data (Optional)

```bash
make fixtures
```

This creates sample employees and customers with the default password: `password123`

### 4. Install and Start the Frontend

In a **new terminal**:

```bash
# Install dependencies (first time only)
make frontend-install

# Start the development server
make frontend-dev
```

The frontend will be available at: http://localhost:3000

### 5. Access the Application

Open your browser and go to: **http://localhost:3000**

#### Sample Credentials (after loading fixtures):

**Employees:**
- Username: `john.smith`, Password: `password123`
- Username: `anna.kowalska`, Password: `password123`

**Customers:**
- Check the console output after running `make fixtures` for customer usernames
- All customers use password: `password123`

## Development Workflow

### Daily Development

1. **Start backend** (if not running):
   ```bash
   make dev
   ```

2. **Start frontend** (in a separate terminal):
   ```bash
   make frontend-dev
   ```

3. Access the application at http://localhost:3000

### Stop Everything

```bash
# Stop backend
make dev-stop

# Stop frontend (Ctrl+C in the terminal running it)
```

## Running Tests

### Backend Tests

```bash
make start              # Start test environment
make test               # Run all tests
make test suite=unit    # Run specific suite
```

### E2E Tests

```bash
# Install Playwright (first time only)
make e2e-install

# Run tests
make e2e                # All tests
make e2e-ui             # Interactive UI mode
```

## Common Commands

| Command | Description |
|---------|-------------|
| `make dev` | Start backend development environment |
| `make dev-stop` | Stop backend environment |
| `make fixtures` | Load sample data |
| `make frontend-dev` | Start frontend dev server |
| `make frontend-build` | Build frontend for production |
| `make test` | Run backend tests |
| `make e2e` | Run end-to-end tests |
| `make analyse` | Run code quality checks |

## Troubleshooting

### Port Already in Use

If ports 8080 (backend) or 3000 (frontend) are already in use:

1. Stop the conflicting service, or
2. Change the port in the respective config file:
   - Backend: `backend/docker-compose.dev.yaml`
   - Frontend: `frontend/vite.config.js`

### Docker Issues

```bash
# Clean up Docker resources
make dev-stop
docker system prune -f

# Rebuild from scratch
make dev
```

### Frontend Not Connecting to Backend

Ensure:
1. Backend is running on http://localhost:8080
2. Check the proxy configuration in `frontend/vite.config.js`
3. Browser console for any CORS or network errors

### Database Issues

```bash
# Reset the database
make dev-stop
make dev
make fixtures
```

## Next Steps

- Read the main [README.md](./README.md) for detailed information
- Check the [docs/](./docs/) directory for specific guides
- Explore the API endpoints at http://localhost:8080/api

## Directory-Specific READMEs

Each main directory has its own README with detailed information:

- [Backend README](./backend/README.md)
- [Frontend README](./frontend/README.md)
- [E2E Tests README](./e2e-tests/README.md)

## Getting Help

If you encounter issues:
1. Check the logs in `backend/var/log/`
2. Check the browser console for frontend errors
3. Review the documentation in the `docs/` directory
