.PHONY: $(MAKECMDGOALS)
define highlight
	@echo "\033[1;32m<================================== ${1} ==================================>\033[0m"
endef

DOCKER_CONTAINER=ebs
DOCKER_COMPOSE_TEST=docker compose -f backend/docker-compose.yaml --env-file=backend/.env.test
DOCKER_COMPOSE_DEV=docker compose -f backend/docker-compose.dev.yaml --env-file=backend/.env.dev
PHPUNIT_CMD=php vendor/bin/phpunit

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

DOCKER_EXEC_WITH_USER_TEST=$(DOCKER_COMPOSE_TEST) exec -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c
DOCKER_RUN_WITH_USER_TEST=$(DOCKER_COMPOSE_TEST) run --rm -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c

DOCKER_EXEC_WITH_USER_DEV=$(DOCKER_COMPOSE_DEV) exec -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c
DOCKER_RUN_WITH_USER_DEV=$(DOCKER_COMPOSE_DEV) run --rm -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c

dev:
	$(call highlight,Starting development environment)
	CURRENT_USER=$(USER_ID):$(GROUP_ID) $(DOCKER_COMPOSE_DEV) up -d --build --force-recreate
	@$(MAKE) dev-setup
	@echo ""
	@echo "Development environment is ready!"
	@echo "Application: http://localhost:8080"
	@echo "Database: localhost:54322 (postgres/postgres)"
	@echo ""

dev-stop:
	$(call highlight,Stopping development environment)
	$(DOCKER_COMPOSE_DEV) down --volumes --remove-orphans

dev-setup:
	$(call highlight,Setting up db - fixtures - etc.)
	$(DOCKER_EXEC_WITH_USER_TEST) "composer dev-setup"

fixtures:
	$(call highlight,Loading fixtures into development database)
	$(DOCKER_COMPOSE_DEV) exec $(DOCKER_CONTAINER) php bin/console app:fixtures:load --purge --no-interaction
	@echo ""
	@echo "Fixtures loaded successfully!"
	@echo "Default password for all users: password123"
	@echo ""

start:
	$(call highlight,Starting test environment)
	CURRENT_USER=$(USER_ID):$(GROUP_ID) $(DOCKER_COMPOSE_TEST) up -d --build --force-recreate
	@$(MAKE) setup

stop:
	$(call highlight,Stopping test environment)
	$(DOCKER_COMPOSE_TEST) down --volumes --remove-orphans

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

# Frontend commands
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
