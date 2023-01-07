# Install the app
install: .env build composer.install

# Start docker containers
up:
	docker compose up -d

# Stop docker containers
down:
	docker compose down --remove-orphans

# Build docker containers
build:
	docker compose build

# Make a file with environment variables
.env:
	cp .env.example .env

# Install composer dependencies
composer.install:
	docker compose run --rm composer install

# Update composer dependencies
composer.update:
	docker compose run --rm composer update

# Display outdated composer dependencies
composer.outdated:
	docker compose run --rm composer outdated

# Dump composer autoload files
composer.autoload:
	docker compose run --rm composer dump-autoload

# Uninstall composer dependencies
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock
	sudo rm -rf .cache

# Run the testsuite
test:
	docker compose run --rm phpunit

# Run the testsuite with a coverage analysis using HTML output
test.coverage.html:
	docker compose run --rm phpunit --coverage-html tests/.coverage

# Run the testsuite with a coverage analysis using plain test output
test.coverage.text:
	docker compose run --rm phpunit --coverage-text

# Run the testsuite with a coverage analysis
test.coverage: test.coverage.text

# Fix the code style
cs.fix:
	docker compose run --rm php-cs-fixer fix

# Check the code style
cs.check:
	docker compose run --rm php-cs-fixer fix --dry-run

# Remove installation files
uninstall: composer.uninstall
	sudo rm .env
