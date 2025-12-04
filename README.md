# Easy Banking System

[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.3-black.svg)](https://symfony.com/)
[![React](https://img.shields.io/badge/React-18-blue.svg)](https://reactjs.org/)
[![License](https://img.shields.io/badge/license-proprietary-red.svg)](LICENSE)

A modern banking system application that enables bank employees to manage customer accounts and allows customers to manage their funds through a web application.

## Project Structure

The project is organized into three main directories:

```
easy-banking-system/
├── backend/          # Symfony PHP application (API)
├── frontend/         # React application (UI)
├── e2e-tests/        # Playwright end-to-end tests
└── docs/             # Documentation
```

## Table of Contents

- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Running the Application](#running-the-application)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
  - [Features](#features)
  - [Out of Scope (MVP)](#out-of-scope-mvp)
- [Architecture](#architecture)
- [Project Status](#project-status)
- [License](#license)

## Project Description

The **Easy Banking System** is designed to streamline banking operations by providing:
- Bank employees the ability to open and close customer bank accounts
- Customers the ability to manage their funds conveniently through a web application
- Secure transaction processing with multi-currency support (PLN, EUR)
- Transaction history tracking for both customers and employees

## Tech Stack

### Frontend
- **React 18** - Modern UI library with hooks
- **React Router v6** - Client-side routing
- **Vite** - Fast build tool and dev server
- **Axios** - HTTP client for API calls

### Backend
- **PHP 8.4** - Programming language with strict types
- **Symfony 7.3** - Web application framework
  - HttpKernel
  - Routing
  - Messenger
  - Console
- **Doctrine DBAL** - Database abstraction layer (no ORM)
- **PostgreSQL** - Database
- **Symfony Validator** - Input validation
- **webmozart/assert** - Domain assertions

### Architecture
- **Hexagonal Architecture** - Clear separation of concerns
- **Domain-Driven Design (DDD)** - Business logic organization
- **SOLID Principles** - Code quality and maintainability

### Development Tools
- **Docker** - Containerization
- **PHPUnit** - Testing framework
- **PHPStan** - Static analysis
- **Easy Coding Standard** - Code style enforcement

### CI/CD
- **GitHub Actions** - Continuous Integration and Deployment pipelines

## Getting Started Locally

### Prerequisites

- **Docker** and **Docker Compose** installed on your machine
- **Make** utility (optional, but recommended)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/sudoku648/easy-banking-system.git
   cd easy-banking-system
   ```

2. **Start the integrated development environment**
   ```bash
   make dev
   ```

   This command will:
   - Build and start Docker containers (backend + frontend + database)
   - Set up the development environment with database migrations and fixtures
   - Start both backend and frontend with hot-reload

   The application will be available at:
   - **Frontend**: http://localhost:3000
   - **Backend API**: http://localhost:8080
   - **Database**: localhost:54322 (postgres/postgres)

3. **Alternative: Start environments independently**
   ```bash
   # Backend only (for testing)
   make backend-start

   # Frontend only (for UI development)
   make frontend-start    # Runs on port 3001

   # E2E testing environment
   make e2e-start
   ```

### Running the Application

#### Integrated Development Environment (`make dev`)
The recommended way for full-stack development. Runs everything in Docker:
- **PHP Application**: `easy-banking-service-ebs-dev` (with Xdebug, hot-reload)
- **Nginx Server**: `easy-banking-service-nginx-dev`
- **PostgreSQL Database**: `easy-banking-service-postgres-dev` (ebsdatabase_dev)
- **Frontend**: `easy-banking-service-frontend-dev` (Vite with hot-reload)

Access the application:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **Database**: localhost:54322 (postgres/postgres)

**Single-command workflow:**
```bash
make dev              # Start everything
make fixtures         # Load sample data (optional)
make dev-stop         # Stop everything
```

Both backend and frontend have hot-reload enabled for efficient development.

#### Backend Test Environment (`make backend-start`)
Isolated backend environment for running tests:
- **PHP Application**: `easy-banking-service-ebs-test`
- **PostgreSQL Database**: `easy-banking-service-postgres-test` (ebsdatabase_test)

Use for PHPUnit tests without frontend dependency.

#### Frontend Test Environment (`make frontend-start`)
Standalone frontend environment for UI development:
- **Frontend**: `easy-banking-service-frontend-test` (port 3001)

Use when working on UI without needing backend.

#### E2E Test Environment (`make e2e-start`)
Integrated environment for Playwright tests:
- Backend on port 8081, frontend on port 3000
- Isolated database and network for E2E testing

See [DOCKER_ENVIRONMENTS.md](./DOCKER_ENVIRONMENTS.md) for detailed information about each environment.

### Loading Development Fixtures

To populate the development database with sample data, use:
```bash
make fixtures
```

This will create:
- **3 employees** (john.smith, anna.kowalska, michael.brown)
- **10 customers** with random names
- **Bank accounts** (1-3 per customer) in PLN, EUR, USD, or GBP with random balances
- **Transaction history** for each account

**Default password for all users:** `password123`

### Creating an Employee Account Manually

To create an employee account manually:
```bash
docker compose -f backend/docker-compose.dev.yaml exec ebs php bin/console app:create-employee "First Name" "Last Name" "username" "password"
```

### Available Scripts

The project uses a Makefile (in the root directory) for common tasks. See [DOCKER_COMMANDS.md](./DOCKER_COMMANDS.md) for quick reference.

### Integrated Development (Recommended)
```bash
make dev                 # Start backend + frontend + database (all in Docker)
make dev-stop            # Stop integrated environment
make fixtures            # Load sample data into development database
```
Access: Frontend (http://localhost:3000), Backend (http://localhost:8080)

### Independent Environments
```bash
# Backend only (for testing)
make backend-start       # Start backend test environment
make backend-stop        # Stop backend
make test                # Run PHPUnit tests
make test suite=unit     # Run specific test suite

# Frontend only (for UI work)
make frontend-start      # Start frontend on port 3001
make frontend-stop       # Stop frontend

# E2E Testing
make e2e-start          # Start E2E environment (backend + frontend)
make e2e-stop           # Stop E2E environment
make e2e                # Run Playwright tests
make e2e-ui             # Run E2E tests in UI mode
```

### Frontend Local Development (without Docker)
```bash
make frontend-install    # Install dependencies (npm install)
make frontend-dev        # Start Vite dev server (port 3000)
make frontend-build      # Build for production
```

### Code Quality
```bash
make analyse            # Run static code analysis (ECS + PHPStan)
```

### Legacy Commands (backward compatible)
```bash
make start              # Alias for make backend-start
make stop               # Alias for make backend-stop
```

**Debugging**: Xdebug is pre-configured in all environments. See [docs/XDEBUG.md](docs/XDEBUG.md) for IDE setup instructions.

### Direct Composer Scripts (from backend/ directory)
```bash
cd backend
composer ecs:check  # Check code style
composer phpstan    # Run static analysis
```

### Internationalization
```bash
cd backend
./bin/generate-locale-templates.sh <locale>  # Generate translation file templates for a new locale
```

See [docs/ADDING_LOCALES.md](docs/ADDING_LOCALES.md) for detailed instructions on adding new locales.

### Asynchronous Commands (backend)
```bash
# In production, run the messenger worker to process async commands
docker compose -f backend/docker-compose.dev.yaml exec ebs php bin/console messenger:consume async

# View failed messages
docker compose -f backend/docker-compose.dev.yaml exec ebs php bin/console messenger:failed:show

# Retry failed messages
docker compose -f backend/docker-compose.dev.yaml exec ebs php bin/console messenger:failed:retry
```

See [docs/ASYNC_COMMANDS.md](docs/ASYNC_COMMANDS.md) for detailed instructions on implementing async commands.

### API Development

The project includes RESTful API endpoints with standardized response models. See [docs/API_RESPONSE_MODELS.md](docs/API_RESPONSE_MODELS.md) for detailed instructions on:
- Using common API response models (`ApiSuccessResponse`, `ApiErrorResponse`)
- Handling validation errors
- Best practices for API development

## Project Scope

### Features

1. **Bank Account Management**
   - Create bank accounts in selected currency (PLN, EUR, USD, GBP)
   - Automatic IBAN generation
   - Assign accounts to new or existing customers
   - Close bank accounts with automatic fund withdrawal

2. **Money Transfers**
   - Internal bank transfers between accounts
   - Multi-currency support (PLN, EUR, USD, GBP) with automatic conversion through PLN
   - Balance validation before transfer
   - Transaction history tracking

3. **Transaction History**
   - Customers can view their transaction history
   - Employees can view any customer's transaction history
   - Transactions sorted by date and time (descending)

4. **Authentication & Authorization**
   - Employee account creation via CLI
   - Unified login form for employees and customers
   - Role-based access control
   - Secure password storage

5. **Internationalization (i18n)**
   - Multi-language support (Polish, English)
   - User language preference persistence
   - Language selection on login page
   - Extensible locale system for easy addition of new languages
   - Domain-based translation organization

6. **Data Security & Scalability**
   - Secure data storage
   - PostgreSQL database for reliability
   - Scalable architecture design

### Out of Scope (MVP)

The following features are not included in the current version:
- ATM withdrawals
- Web interface for creating employee accounts
- Detailed permission management for specific actions
- Inter-bank transfers
- Other transaction types beyond internal transfers and cash withdrawals

## Architecture

### Project Structure

The application is divided into three main parts:

#### Backend (`backend/`)
The backend follows **Hexagonal Architecture** (Ports & Adapters) combined with **Domain-Driven Design** principles:

**Bounded Context Structure:**
- **Application/**: Commands, Queries, Handlers, Events, EventHandlers (use cases)
- **Domain/**: Entities, Value Objects, Repositories (interfaces), Domain Services, Domain Events
- **Infrastructure/**: Repository implementations, external service integrations, persistence
- **Presentation/**: API controllers, DTOs, forms, validators (entry points)
- **Symfony/**: Symfony-specific configuration (services, routes, event listeners)
- **Cli/**: Console commands

**Key Principles:**
- No direct coupling between bounded contexts
- Communication via domain events using Symfony Messenger
- Domain layer depends only on interfaces (ports), never on concrete implementations
- Strict type hints and immutability where appropriate
- Value Objects for domain concepts
- Repository pattern for data persistence

#### Frontend (`frontend/`)
Modern React application with:
- Component-based architecture
- React Router for navigation
- Axios for API communication
- i18next for internationalization
- Vite for fast development and optimized builds

#### E2E Tests (`e2e-tests/`)
Playwright-based end-to-end tests covering:
- Authentication flows
- Bank account management
- Customer transactions
- Employee operations

## Project Status

✅ **MVP Complete** - Ready for production

All core features have been successfully implemented, tested, and documented according to the Product Requirements Document (PRD). The project includes 256 passing tests with 525 assertions, comprehensive documentation, and follows hexagonal architecture with DDD principles.

## License

This project is proprietary software. All rights reserved.
