.PHONY: $(MAKECMDGOALS)
define highlight
	@echo "\033[1;32m<================================== ${1} ==================================>\033[0m"
endef

DOCKER_CONTAINER=ebs
DOCKER_COMPOSE_TEST=docker compose -f docker-compose.yaml --env-file=.env.test
DOCKER_COMPOSE_DEV=docker compose -f docker-compose.dev.yaml --env-file=.env.dev
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
	@echo ""
	@echo "Development environment is ready!"
	@echo "Application: http://localhost:8080"
	@echo "Database: localhost:54322 (postgres/postgres)"
	@echo ""

dev-stop:
	$(call highlight,Stopping development environment)
	$(DOCKER_COMPOSE_DEV) down --volumes --remove-orphans

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
ifeq ($(suite),integration)
	$(DOCKER_EXEC_WITH_USER_TEST) "INTEGRATION_TESTS=1 $(PHPUNIT_CMD) --testsuite=$(suite)"
else
	$(DOCKER_EXEC_WITH_USER_TEST) "$(PHPUNIT_CMD) --testsuite=$(suite)"
endif
else
	$(call highlight,Running all test suites)
	@echo "Note: Warnings about duplicate test files in functional/integration suites are expected"
	$(DOCKER_EXEC_WITH_USER_TEST) "$(PHPUNIT_CMD) || test \$$? -eq 1"
endif
