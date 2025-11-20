# Easy Banking System

[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.3-black.svg)](https://symfony.com/)
[![License](https://img.shields.io/badge/license-proprietary-red.svg)](LICENSE)

A modern banking system application that enables bank employees to manage customer accounts and allows customers to manage their funds through a web application.

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
- **Twig** - Symfony templating engine
- **Bootstrap 5** - UI styling framework

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

2. **Start the development environment**
   ```bash
   make dev
   ```
   
   This command will:
   - Build and start Docker containers (nginx, app, postgres_dev)
   - Set up the development environment
   
   The development environment includes:
   - **Application**: http://localhost:8080
   - **Database**: localhost:54322 (postgres/postgres)

3. **For testing environment**
   ```bash
   make start
   ```

### Running the Application

#### Development Environment (`make dev`)
The development environment runs in separate Docker containers:
- **PHP Application**: `easy-banking-service-ebs` (with xdebug, hot-reload)
- **PostgreSQL Database**: `easy-banking-service-postgres-dev` (ebsdatabase_dev)
- **Nginx Server**: `easy-banking-service-nginx`

Access the application at: **http://localhost:8080**

#### Test Environment (`make start`)
The test environment uses minimal containers:
- **PHP Application**: `easy-banking-service-ebs-test`
- **PostgreSQL Database**: `easy-banking-service-postgres-test` (ebsdatabase_test)

### Loading Development Fixtures

To populate the development database with sample data, use:
```bash
make fixtures
```

This will create:
- **3 employees** (john.smith, anna.kowalska, michael.brown)
- **10 customers** with random names
- **Bank accounts** (1-3 per customer) in PLN or EUR with random balances
- **Transaction history** for each account

**Default password for all users:** `password123`

### Creating an Employee Account Manually

To create an employee account manually:
```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console app:create-employee "First Name" "Last Name" "username" "password"
```

## Available Scripts

The project uses a Makefile for common tasks:

### Development
```bash
make dev            # Start development environment (nginx + app + postgres_dev)
make dev-stop       # Stop development environment
make fixtures       # Load sample data into development database
```

### Testing
```bash
make start                   # Start test environment (app + postgres_test)
make stop                    # Stop test environment
make vendor                  # Install Composer dependencies
make setup                   # Setup test database
make test                    # Run all test suites
make test suite=unit         # Run unit tests
make test suite=integration  # Run integration tests
make test suite=functional   # Run functional tests
make test suite=presentation # Run presentation tests
```

**Debugging Tests**: Xdebug is pre-configured in both dev and test environments. See [docs/XDEBUG.md](docs/XDEBUG.md) for IDE setup instructions.

### Code Quality
```bash
make analyse        # Run static code analysis (ECS + PHPStan)
```

### Direct Composer Scripts
```bash
composer ecs:check  # Check code style
composer phpstan    # Run static analysis
```

## Project Scope

### Features

1. **Bank Account Management**
   - Create bank accounts in selected currency (PLN, EUR)
   - Automatic IBAN generation
   - Assign accounts to new or existing customers
   - Close bank accounts with automatic fund withdrawal

2. **Money Transfers**
   - Internal bank transfers between accounts
   - Multi-currency support with automatic conversion
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

5. **Data Security & Scalability**
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

The project follows **Hexagonal Architecture** (Ports & Adapters) combined with **Domain-Driven Design** principles:

### Bounded Context Structure
- **Application/**: Commands, Queries, Handlers, Events, EventHandlers (use cases)
- **Domain/**: Entities, Value Objects, Repositories (interfaces), Domain Services, Domain Events
- **Infrastructure/**: Repository implementations, external service integrations, persistence
- **Presentation/**: frontend controllers, DTOs, forms, validators (entry points)
- **Symfony/**: Symfony-specific configuration (services, routes, event listeners)
- **Cli/**: Console commands

### Key Principles
- No direct coupling between bounded contexts
- Communication via domain events using Symfony Messenger
- Domain layer depends only on interfaces (ports), never on concrete implementations
- Strict type hints and immutability where appropriate
- Value Objects for domain concepts
- Repository pattern for data persistence

## Project Status

✅ **MVP Complete** - Ready for production

All core features have been successfully implemented, tested, and documented according to the Product Requirements Document (PRD). The project includes 256 passing tests with 525 assertions, comprehensive documentation, and follows hexagonal architecture with DDD principles.

## License

This project is proprietary software. All rights reserved.
