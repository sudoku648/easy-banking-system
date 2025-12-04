.PHONY: $(MAKECMDGOALS)
define highlight
	@echo "\033[1;32m<================================== ${1} ==================================>\033[0m"
endef

DOCKER_CONTAINER=ebs
DOCKER_COMPOSE_BACKEND_TEST=docker compose -f backend/docker-compose.yaml --env-file=backend/.env.test
DOCKER_COMPOSE_BACKEND_DEV=docker compose -f backend/docker-compose.dev.yaml --env-file=backend/.env.dev
DOCKER_COMPOSE_DEV=docker compose -f docker-compose.dev.yaml
DOCKER_COMPOSE_E2E=docker compose -f docker-compose.e2e.yaml --env-file=backend/.env.dev
PHPUNIT_CMD=php vendor/bin/phpunit

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

DOCKER_EXEC_WITH_USER_TEST=$(DOCKER_COMPOSE_BACKEND_TEST) exec -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c
DOCKER_RUN_WITH_USER_TEST=$(DOCKER_COMPOSE_BACKEND_TEST) run --rm -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c

DOCKER_EXEC_WITH_USER_DEV=$(DOCKER_COMPOSE_DEV) exec -u $(USER_ID):$(GROUP_ID) ebs sh -c
DOCKER_RUN_WITH_USER_DEV=$(DOCKER_COMPOSE_DEV) run --rm -u $(USER_ID):$(GROUP_ID) ebs sh -c

# Integrated Development Environment (Backend + Frontend)
dev:
	$(call highlight,Starting integrated development environment)
	USER_ID=$(USER_ID) GROUP_ID=$(GROUP_ID) $(DOCKER_COMPOSE_DEV) up -d --build
	@$(MAKE) dev-setup
	@echo ""
	@echo "Development environment is ready!"
	@echo "Backend API: http://localhost:8080"
	@echo "Frontend: http://localhost:3000"
	@echo "Database: localhost:54322 (postgres/postgres)"
	@echo ""

dev-stop:
	$(call highlight,Stopping integrated development environment)
	$(DOCKER_COMPOSE_DEV) down --volumes --remove-orphans

dev-setup:
	$(call highlight,Setting up database and fixtures)
	@sleep 5
	$(DOCKER_COMPOSE_DEV) exec -u $(USER_ID):$(GROUP_ID) ebs sh -c "composer dev-setup"

fixtures:
	$(call highlight,Loading fixtures into development database)
	$(DOCKER_COMPOSE_DEV) exec ebs php bin/console app:fixtures:load --purge --no-interaction
	@echo ""
	@echo "Fixtures loaded successfully!"
	@echo "Default password for all users: password123"
	@echo ""

# Backend Test Environment
backend-start:
	$(call highlight,Starting backend test environment)
	USER_ID=$(USER_ID) GROUP_ID=$(GROUP_ID) $(DOCKER_COMPOSE_BACKEND_TEST) up -d --build --force-recreate
	@$(MAKE) backend-setup

backend-stop:
	$(call highlight,Stopping backend test environment)
	$(DOCKER_COMPOSE_BACKEND_TEST) down --volumes --remove-orphans

backend-setup:
	$(call highlight,Setting up backend test database)
	$(DOCKER_EXEC_WITH_USER_TEST) "composer tests-setup"

# Legacy aliases for backward compatibility
start: backend-start
stop: backend-stop

vendor:
	$(call highlight,Installing composer)
	$(DOCKER_RUN_WITH_USER_TEST) "composer install --no-scripts"

setup:
	$(call highlight,Setting up db - tests - etc.)
	$(DOCKER_EXEC_WITH_USER_TEST) "composer tests-setup"

analyse:
	$(call highlight,Static code analysis)
	$(DOCKER_EXEC_WITH_USER_TEST) "composer ecs:check"
	$(DOCKER_EXEC_WITH_USER_TEST) "composer phpstan"

test:
ifdef suite
	$(call highlight,Running test suite: $(suite))
	$(DOCKER_EXEC_WITH_USER_TEST) "$(PHPUNIT_CMD) --testsuite=$(suite)"
else
	$(call highlight,Running all test suites)
	$(DOCKER_EXEC_WITH_USER_TEST) "$(PHPUNIT_CMD) || test \$$? -eq 1"
endif

# Frontend Test Environment
DOCKER_COMPOSE_FRONTEND_TEST=docker compose -f frontend/docker-compose.test.yaml

frontend-start:
	$(call highlight,Starting frontend test environment)
	$(DOCKER_COMPOSE_FRONTEND_TEST) up -d --build
	@echo ""
	@echo "Frontend test environment is ready!"
	@echo "Application: http://localhost:3001"
	@echo ""

frontend-stop:
	$(call highlight,Stopping frontend test environment)
	$(DOCKER_COMPOSE_FRONTEND_TEST) down --volumes --remove-orphans

frontend-logs:
	$(call highlight,Showing frontend logs)
	$(DOCKER_COMPOSE_FRONTEND_TEST) logs -f

frontend-restart:
	$(call highlight,Restarting frontend)
	$(DOCKER_COMPOSE_FRONTEND_TEST) restart

# Frontend local development (without Docker)
frontend-install:
	$(call highlight,Installing frontend dependencies)
	cd frontend && npm install

frontend-dev:
	$(call highlight,Starting frontend development server)
	cd frontend && npm run dev

frontend-build:
	$(call highlight,Building frontend for production)
	cd frontend && npm run build

frontend-preview:
	$(call highlight,Preview production build)
	cd frontend && npm run preview

# E2E Test Environment (Backend + Frontend integrated)
e2e-start:
	$(call highlight,Starting E2E test environment)
	USER_ID=$(USER_ID) GROUP_ID=$(GROUP_ID) $(DOCKER_COMPOSE_E2E) up -d --build
	@$(MAKE) e2e-setup
	@echo ""
	@echo "E2E environment is ready!"
	@echo "Backend API: http://localhost:8081"
	@echo "Frontend: http://localhost:3000"
	@echo ""

e2e-stop:
	$(call highlight,Stopping E2E test environment)
	$(DOCKER_COMPOSE_E2E) down --volumes --remove-orphans

e2e-setup:
	$(call highlight,Setting up E2E database and fixtures)
	@sleep 5
	$(DOCKER_COMPOSE_E2E) exec -u $(USER_ID):$(GROUP_ID) ebs sh -c "composer dev-setup"

e2e-logs:
	$(call highlight,Showing E2E environment logs)
	$(DOCKER_COMPOSE_E2E) logs -f

# E2E testing commands
e2e-install:
	$(call highlight,Installing Playwright browsers)
	cd e2e-tests && npx playwright install --with-deps

e2e:
	$(call highlight,Running e2e tests)
	cd e2e-tests && npm run test:e2e

e2e-ui:
	$(call highlight,Running e2e tests in UI mode)
	cd e2e-tests && npm run test:e2e:ui

e2e-headed:
	$(call highlight,Running e2e tests in headed mode)
	cd e2e-tests && npm run test:e2e:headed

e2e-debug:
	$(call highlight,Running e2e tests in debug mode)
	cd e2e-tests && npm run test:e2e:debug

e2e-report:
	$(call highlight,Showing e2e test report)
	cd e2e-tests && npm run test:e2e:report
