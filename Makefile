# Install the app
install: build composer.install

# Start docker containers
up:
	docker compose up -d

# Stop docker containers
down:
	docker compose down --remove-orphans

# Build docker containers
build:
	docker compose build

# Install composer dependencies
composer.install:
	docker compose run --rm composer install

# Update composer dependencies
composer.update:
	docker compose run --rm composer update

# Downgrade composer dependencies to lowest versions
composer.lowest:
	docker compose run --rm composer update --prefer-lowest --prefer-stable

# Uninstall composer dependencies
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock
	sudo rm -rf .cache

# Run PHPUnit
phpunit:
	docker compose run --rm phpunit --stop-on-failure

# Alias to run PHPUnit
test: phpunit

# Run PHPUnit with a coverage analysis using an HTML output
phpunit.coverage.html:
	docker compose run --rm phpunit --coverage-html tests/.report

# Run PHPUnit with a coverage analysis using a plain text output
phpunit.coverage.text:
	docker compose run --rm phpunit --coverage-text

# Run PHPUnit with a coverage analysis using a Clover's XML output
phpunit.coverage.clover:
	docker compose run --rm phpunit --coverage-clover tests/.report/clover.xml

# Run PHPUnit with a coverage analysis
phpunit.coverage: phpunit.coverage.text

# Fix the code style
php.cs.fix:
	docker compose run --rm php-cs-fixer fix

# Check the code style
php.cs.check:
	docker compose run --rm php-cs-fixer fix --dry-run

# Remove installation files
uninstall: down composer.uninstall
