# Image
FROM php:7.2-cli

# Update dependencies
RUN apt-get update

# Set up curl
RUN apt-get install -y libcurl3-dev curl \
    && docker-php-ext-install curl

# Set up zip
RUN apt-get install -y libzip-dev zip \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip

# Set up BC Math extension
RUN docker-php-ext-install bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install PCOV
RUN pecl install pcov && docker-php-ext-enable pcov

# Set up working directory
WORKDIR /app
