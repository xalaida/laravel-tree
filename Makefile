# Include file with environment variables if exists
-include .env

# Define default values for environment variables
DB_CONNECTION ?= pgsql
COMPOSE ?= -f docker-compose.yml -f docker-compose.${DB_CONNECTION}.yml
PHPUNIT_RUN ?= phpunit -c ${PHPUNIT_CONFIG_FILE}

# Install the app
install: env build composer.install

# Copy file with environment variables
env:
	cp .env.dist .env

# Start docker containers
up:
	docker compose ${COMPOSE} up -d

# Stop docker containers
down:
	docker compose ${COMPOSE} down --remove-orphans

# Build docker containers
build:
	docker compose ${COMPOSE} build

# Install composer dependencies
composer.install:
	docker compose ${COMPOSE} run --rm composer install

# Update composer dependencies
composer.update:
	docker compose ${COMPOSE} run --rm composer update

# Downgrade composer dependencies to lowest versions
composer.lowest:
	docker compose ${COMPOSE} run --rm composer update --prefer-lowest --prefer-stable

# Uninstall composer dependencies
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock
	sudo rm -rf .cache

# Run PHPUnit
phpunit:
	docker compose ${COMPOSE} run --rm ${PHPUNIT_RUN}

# Alias to run PHPUnit
test: phpunit

# Run PHPUnit with MySQL service
test.mysql:
	docker compose -f docker-compose.yml -f docker-compose.mysql.yml run --rm ${PHPUNIT_RUN}

# Run PHPUnit with PostgreSQL service
test.pgsql:
	docker compose -f docker-compose.yml -f docker-compose.pgsql.yml run --rm ${PHPUNIT_RUN}

# Run PHPUnit with SQLite service
test.sqlite:
	docker compose -f docker-compose.yml -f docker-compose.sqlite.yml run --rm ${PHPUNIT_RUN}

# Run PHPUnit with a coverage analysis using an HTML output
phpunit.coverage.html:
	docker compose ${COMPOSE} run --rm ${PHPUNIT_RUN} --coverage-html tests/.report

# Run PHPUnit with a coverage analysis using a plain text output
phpunit.coverage.text:
	docker compose ${COMPOSE} run --rm ${PHPUNIT_RUN} --coverage-text

# Run PHPUnit with a coverage analysis using a Clover's XML output
phpunit.coverage.clover:
	docker compose ${COMPOSE} run --rm ${PHPUNIT_RUN} --coverage-clover tests/.report/clover.xml

# Run PHPUnit with a coverage analysis
phpunit.coverage: phpunit.coverage.text

# Fix the code style
php.cs.fix:
	docker compose ${COMPOSE} run --rm php-cs-fixer fix

# Check the code style
php.cs.check:
	docker compose ${COMPOSE} run --rm php-cs-fixer fix --dry-run

# Remove installation files
uninstall: down composer.uninstall
