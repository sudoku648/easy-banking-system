# Backend - Easy Banking System

This directory contains the Symfony-based backend API for the Easy Banking System.

## Structure

```
backend/
├── bin/              # Console commands and scripts
├── config/           # Symfony configuration
├── docker/           # Docker-specific files
├── migrations/       # Database migrations
├── public/           # Web root (index.php, build assets)
├── src/              # Application source code
│   ├── BankAccount/      # Bank account bounded context
│   ├── Transaction/      # Transaction bounded context
│   ├── UserManagement/   # User management bounded context
│   └── Shared/           # Shared kernel
├── tests/            # PHPUnit tests
├── translations/     # Translation files
├── var/              # Cache, logs, etc.
└── vendor/           # Composer dependencies
```

## Requirements

- Docker and Docker Compose
- Make (optional, but recommended)

## Quick Start

From the **root directory** of the project:

```bash
# Start development environment
make dev

# Load sample data
make fixtures

# Stop environment
make dev-stop
```

## Development

### Running Commands

All commands should be run from the **root directory** using the Makefile, or from within the Docker container:

```bash
# From root directory
make dev

# Or manually with docker compose
docker compose -f backend/docker-compose.dev.yaml --env-file=backend/.env.dev up -d
```

### Accessing the Container

```bash
docker compose -f backend/docker-compose.dev.yaml exec ebs bash
```

### Running Tests

```bash
# From root directory
make test                   # All tests
make test suite=unit        # Unit tests only
```

### Code Quality

```bash
make analyse               # Run PHPStan and ECS
```

### Database

The backend uses PostgreSQL running in Docker:
- **Host**: localhost
- **Port**: 54322
- **Database**: ebsdatabase_dev
- **User**: postgres
- **Password**: postgres

## Configuration

- `.env` - Production environment
- `.env.dev` - Development environment
- `.env.test` - Test environment

## Architecture

The backend follows **Hexagonal Architecture** with **Domain-Driven Design**. See the main [README.md](../README.md) for detailed architecture information.

## Documentation

- [API Endpoints](../docs/API_ENDPOINTS.md)
- [Async Commands](../docs/ASYNC_COMMANDS.md)
- [Adding Locales](../docs/ADDING_LOCALES.md)
- [Xdebug Setup](../docs/XDEBUG.md)
