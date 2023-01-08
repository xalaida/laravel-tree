# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests.

## Pull Requests

- **Add tests** - Your patch won't be accepted if it doesn't have tests.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/).

- **Document any change in behaviour** - Make sure the [README.md](../README.md) and any other relevant documentation are kept up-to-date.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## Setup

The project setup is based upon [docker](https://docs.docker.com/engine/install).

For convenience, common tasks are wrapped up in the [Makefile](../Makefile) for usage with [GNU make](https://www.gnu.org/software/make/).

## Installation

Fork and clone the project:

```bash
git clone https://github.com/nevadskiy/laravel-tree.git
```

Build docker containers:

```bash
docker compose build
```

Install the composer dependencies:

```bash
docker compose run --rm composer install
```

## Running Tests

To run tests, execute the following command:

```bash
docker compose run --rm phpunit
```

## Code Style

Formatting is automated through [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)

```bash
docker compose run --rm php-cs-fixer fix
```

**Happy coding**!
