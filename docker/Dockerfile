ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-cli

# Install extensions
RUN apt-get update && \
    apt-get install -y \
    autoconf \
    pkg-config \
    libssl-dev \
    git \
    unzip \
    libzip-dev \
    libxml2-dev \
    zlib1g-dev && \
    pecl install xdebug && docker-php-ext-enable xdebug && \
    docker-php-ext-install -j$(nproc) zip

# Install extensions
RUN docker-php-ext-install xml zip soap && \
    docker-php-ext-enable xml zip soap

# Create php.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer