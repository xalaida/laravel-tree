# Specify the image.
FROM php:7.3-cli

# Update dependencies.
RUN apt-get update \
# Install Curl.
    && apt-get install -y libcurl3-dev curl \
    && docker-php-ext-install curl \
# Install Zip.
    && apt-get install -y libzip-dev zip \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip \
# Install PostgreSQL.
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
# Install BC Math extension.
    && docker-php-ext-install bcmath \
# Install PCOV.
    && pecl install pcov && docker-php-ext-enable pcov

# Install Composer.
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Specify the working directory.
WORKDIR /app
