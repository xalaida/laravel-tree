# Specify the image
FROM php:7.3-cli

# Update dependencies
RUN apt-get update \
# Install Zip extension
    && apt-get install -y libzip-dev zip \
    && docker-php-ext-install zip \
# Install PostgreSQL extension
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
# Install MySQL extension
  && docker-php-ext-install pdo pdo_mysql \
# Install PCOV extension
    && pecl install pcov && docker-php-ext-enable pcov

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Specify the working directory
WORKDIR /app
