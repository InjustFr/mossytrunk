DC = docker compose
NO_TTY = $(if $(CI),-T)
EXEC = $(DC) exec $(NO_TTY)
RUN = $(DC) run $(NO_TTY) --rm
PHP = $(EXEC) php
CONSOLE = $(PHP) php bin/console

IMAGE ?= docker.io/injust/mossytrunk
TAG ?= $(shell git rev-parse --short=7 HEAD)
PLATFORM ?= linux/amd64
DEPLOY_HOST ?=
DEPLOY_DIR ?= mossytrunk
REMOTE_DOCKER ?= docker
BUILD = docker buildx build --platform $(PLATFORM) --target prod -t $(IMAGE):$(TAG) -t $(IMAGE):latest

.PHONY: up down build install assets assets-e2e db db-test fixtures migration test test-unit test-functional deptrac phpstan cs cs-fix e2e qa ci image push deploy deploy-files

up: ## Start the stack (app on http://localhost:8080)
	$(DC) up -d --wait php database node mailpit

down:
	$(DC) down

build:
	$(DC) build

install:
	$(PHP) composer install
	$(RUN) --no-deps node npm install

assets:
	$(RUN) --no-deps node npm run build

assets-e2e:
	$(RUN) --no-deps -e ASSETS_DIR=build-e2e node npm run build

db: ## Create and migrate the dev database
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

fixtures: db ## Reset the dev database with mock data (Foundry story)
	$(CONSOLE) doctrine:fixtures:load --no-interaction --purge-with-truncate

db-test: ## Create and migrate the test database
	$(CONSOLE) doctrine:database:create --if-not-exists --env=test
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test

migration: ## Generate a migration from mapping changes
	$(CONSOLE) doctrine:migrations:diff --no-interaction

test: db-test ## PHPUnit (unit + functional)
	$(PHP) php bin/phpunit

test-unit:
	$(PHP) php bin/phpunit --testsuite unit

test-functional: db-test
	$(PHP) php bin/phpunit --testsuite functional

deptrac: ## Check onion layer dependencies
	$(PHP) vendor/bin/deptrac analyse --no-progress

cs: ## Coding standard check (PHP-CS-Fixer, @Symfony + strict types)
	$(EXEC) -e PHP_CS_FIXER_IGNORE_ENV=1 php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Apply the coding standard
	$(EXEC) -e PHP_CS_FIXER_IGNORE_ENV=1 php vendor/bin/php-cs-fixer fix

phpstan: ## Static analysis (level in phpstan.dist.neon)
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

e2e: assets-e2e ## Playwright end-to-end tests against a dedicated app container
	$(DC) --profile e2e up -d --wait php-e2e mailpit
	$(EXEC) php-e2e php bin/console doctrine:database:drop --force --if-exists --env=test
	$(EXEC) php-e2e php bin/console doctrine:database:create --env=test
	$(EXEC) php-e2e php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
	$(EXEC) php-e2e php bin/console app:user:create e2e@mossytrunk.local --workspace=E2E --env=test
	$(EXEC) php-e2e php bin/console app:user:create e2e-reset@mossytrunk.local --workspace=E2E --env=test
	$(DC) --profile e2e run $(NO_TTY) --rm playwright sh -c "npm ci --no-audit --no-fund && ./node_modules/.bin/playwright test"

qa: cs phpstan deptrac test e2e

ci: ## Full suite from a fresh checkout, as run by GitHub Actions before publishing the image
	$(DC) up -d --wait php database mailpit
	$(PHP) composer install --no-interaction --no-progress
	$(RUN) --no-deps node npm ci --no-audit --no-fund
	$(MAKE) assets qa

image: ## Build the production image locally (IMAGE, TAG, PLATFORM)
	$(BUILD) --load .

push: qa ## Run the full suite, then build and push the production image by hand (CI does it on every push to main; run docker login first)
	$(BUILD) --push .

deploy-files: ## Copy deploy/ (compose, env template, README) to DEPLOY_HOST:DEPLOY_DIR
	@test -n "$(DEPLOY_HOST)" || { echo "Set DEPLOY_HOST=user@server"; exit 1; }
	ssh $(DEPLOY_HOST) 'mkdir -p $(DEPLOY_DIR)'
	scp deploy/compose.yaml deploy/.env.dist deploy/README.md $(DEPLOY_HOST):$(DEPLOY_DIR)/

deploy: ## Run IMAGE:TAG (published by CI) on DEPLOY_HOST: write them to .env, pull, restart
	@test -n "$(DEPLOY_HOST)" || { echo "Set DEPLOY_HOST=user@server"; exit 1; }
	@docker manifest inspect $(IMAGE):$(TAG) >/dev/null 2>&1 || { echo "$(IMAGE):$(TAG) is not published: CI only publishes it once the full test suite passes (still running, or failed?)"; exit 1; }
	ssh $(DEPLOY_HOST) 'set -e; cd $(DEPLOY_DIR); \
		test -f .env || { echo "Create $(DEPLOY_DIR)/.env from .env.dist first (see README.md)"; exit 1; }; \
		for pair in IMAGE=$(IMAGE) TAG=$(TAG); do \
			key=$${pair%%=*}; \
			if grep -q "^$$key=" .env; then sed -i "s|^$$key=.*|$$pair|" .env; else echo "$$pair" >> .env; fi; \
		done; \
		$(REMOTE_DOCKER) compose pull app; \
		$(REMOTE_DOCKER) compose up -d'
