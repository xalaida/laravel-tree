# Install the app
install: build vendor

# Build the app container
build:
	docker build -t app .

# Install app dependencies
vendor:
	docker run --rm -it -v ${PWD}:/app app composer install

# Update app dependencies
update:
	docker run --rm -it -v ${PWD}:/app app composer update

# Show outdated dependencies
outdated:
	docker run --rm -it -v ${PWD}:/app app composer outdated

# Run the testsuite
test:
	docker run --rm -it -v ${PWD}:/app app vendor/bin/phpunit

# Generate a coverage report as html
coverage.html:
	docker run --rm -it -v ${PWD}:/app app vendor/bin/phpunit --coverage-html tests/.coverage

# Generate a coverage report as text
coverage.text:
	docker run --rm -it -v ${PWD}:/app app vendor/bin/phpunit --coverage-text

# Coverage text alias
coverage: coverage.text

# Fix the code style
fix:
	docker run --rm -it -v ${PWD}:/app app vendor/bin/php-cs-fixer fix
