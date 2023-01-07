# Install the app.
install: .env build composer.install

# Start docker containers.
up:
	docker compose up -d

# Stop docker containers.
down:
	docker compose down

# Build docker containers.
build:
	docker compose build

# Make a file with environment variables.
.env:
	cp .env.example .env

# Install composer dependencies.
composer.install:
	docker compose run --rm app composer install

# Update composer dependencies.
composer.update:
	docker compose run --rm app composer update

# Display outdated composer dependencies.
composer.outdated:
	docker compose run --rm app composer outdated

# Dump composer autoload files.
composer.autoload:
	docker compose run --rm app composer dump-autoload

# Uninstall composer dependencies.
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock

# Run the testsuite.
test:
	docker compose run --rm app vendor/bin/phpunit

# Run the testsuite with a coverage analysis using HTML output.
test.coverage.html:
	docker compose run --rm app vendor/bin/phpunit --coverage-html tests/.coverage

# Run the testsuite with a coverage analysis using plain test output.
test.coverage.text:
	docker compose run --rm app vendor/bin/phpunit --coverage-text

# Run the testsuite with a coverage analysis.
test.coverage: test.coverage.text

# Fix the code style.
fix:
	docker compose run --rm app vendor/bin/php-cs-fixer fix

# Check the code style
check:
	docker compose run --rm app vendor/bin/php-cs-fixer fix --dry-run --diff-format udiff

# Remove installation files.
uninstall: composer.uninstall
	sudo rm .env
