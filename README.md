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

2. **Create environment configuration**
   ```bash
   cp docker-compose.override.yml.dist docker-compose.override.yml
   ```

3. **Start the application**
   ```bash
   make start
   ```
   
   This command will:
   - Build and start Docker containers
   - Install Composer dependencies
   - Set up the database

### Running the Application

The application runs in Docker containers:
- **PHP Application**: `easy-banking-service-ebs-1`
- **PostgreSQL Database**: `easy-banking-service-postgres-1`

Access the application at: `http://localhost` (port configured in docker-compose.override.yml)

### Creating an Employee Account

To create an employee account, use the CLI command:
```bash
docker compose exec ebs php bin/console app:create-employee "First Name" "Last Name" "password"
```

## Available Scripts

The project uses a Makefile for common tasks:

### Development
```bash
make start          # Start Docker containers and set up the project
make stop           # Stop Docker containers and clean up volumes
make vendor         # Install Composer dependencies
```

### Code Quality
```bash
make analyse        # Run static code analysis (ECS + PHPStan)
```

### Testing
```bash
make test                    # Run all test suites
make test suite=unit         # Run unit tests
make test suite=integration  # Run integration tests
make test suite=functional   # Run functional tests
make test suite=presentation # Run presentation tests
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

🚧 **In Development** - MVP phase

The project is actively being developed. Core features are being implemented according to the Product Requirements Document (PRD).

## License

This project is proprietary software. All rights reserved.
