DC = docker compose
PHP = $(DC) exec php
CONSOLE = $(PHP) php bin/console

.PHONY: up down build install assets db db-test fixtures migration test test-unit test-functional deptrac e2e qa

up: ## Start the stack (app on http://localhost:8080)
	$(DC) up -d --wait php database node mailpit

down:
	$(DC) down

build:
	$(DC) build

install:
	$(PHP) composer install
	$(DC) run --rm --no-deps node npm install

assets:
	$(DC) run --rm --no-deps node npm run build

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

e2e: assets ## Playwright end-to-end tests against a dedicated app container
	$(DC) --profile e2e up -d --wait php-e2e mailpit
	$(DC) exec php-e2e php bin/console doctrine:database:drop --force --if-exists --env=test
	$(DC) exec php-e2e php bin/console doctrine:database:create --env=test
	$(DC) exec php-e2e php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
	$(DC) exec php-e2e php bin/console app:user:create e2e@mossytrunk.local --workspace=E2E --env=test
	$(DC) exec php-e2e php bin/console app:user:create e2e-reset@mossytrunk.local --workspace=E2E --env=test
	$(DC) --profile e2e run --rm playwright sh -c "npm ci --no-audit --no-fund && ./node_modules/.bin/playwright test"

qa: deptrac test e2e
