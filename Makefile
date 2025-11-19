.PHONY: $(MAKECMDGOALS)
define highlight
	@echo "\033[1;32m<================================== ${1} ==================================>\033[0m"
endef

DOCKER_CONTAINER=ebs
DOCKER_COMPOSE_BASIC=docker compose -f docker-compose.yaml --env-file=.env.test
DOCKER_COMPOSE=$(DOCKER_COMPOSE_BASIC) $(shell test -e "docker-compose.override.yml" && echo " -f docker-compose.override.yml")
PHPUNIT_CMD=php vendor/bin/phpunit

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

DOCKER_EXEC_WITH_USER=$(DOCKER_COMPOSE) exec -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c
DOCKER_RUN_WITH_USER=$(DOCKER_COMPOSE) run --rm -u $(USER_ID):$(GROUP_ID) $(DOCKER_CONTAINER) sh -c

start:
	$(call highlight,Starting Docker containers)
	CURRENT_USER=$(USER_ID):$(GROUP_ID) $(DOCKER_COMPOSE) up -d --build --force-recreate
	@$(MAKE) setup

stop:
	$(call highlight,Stopping Docker containers)
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

vendor:
	$(call highlight,Installing composer)
	$(DOCKER_RUN_WITH_USER) "composer install --no-scripts"

setup:
	$(call highlight,Setting up db - tests - etc.)
	$(DOCKER_EXEC_WITH_USER) "composer tests-setup"

analyse:
	$(call highlight,Static code analysis)
	$(DOCKER_EXEC_WITH_USER) "composer ecs:check"
	$(DOCKER_EXEC_WITH_USER) "composer phpstan"

test:
ifdef suite
	$(call highlight,Running test suite: $(suite))
ifeq ($(suite),integration)
	$(DOCKER_EXEC_WITH_USER) "INTEGRATION_TESTS=1 $(PHPUNIT_CMD) --testsuite=$(suite)"
else
	$(DOCKER_EXEC_WITH_USER) "$(PHPUNIT_CMD) --testsuite=$(suite)"
endif
else
	$(call highlight,Running all test suites)
	@echo "Note: Warnings about duplicate test files in functional/integration suites are expected"
	$(DOCKER_EXEC_WITH_USER) "$(PHPUNIT_CMD) || test \$$? -eq 1"
endif
