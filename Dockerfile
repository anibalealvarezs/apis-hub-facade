FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    libbrotli-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    sqlite3 \
    libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js and NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs


# Get the official PHP extension installer (handles PECL, dependencies, and retries robustly)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install standard PHP extensions cleanly
RUN install-php-extensions pdo_pgsql pdo_sqlite mbstring exif pcntl bcmath gd zip intl opcache

# Install Swoole and phpredis from source with full submodules (100% immune to PECL downtime and missing submodule headers)
RUN git clone --depth 1 --recurse-submodules --shallow-submodules -b v6.0.0 https://github.com/swoole/swoole-src.git /tmp/swoole \
    && cd /tmp/swoole \
    && phpize \
    && ./configure --enable-openssl \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable swoole \
    && rm -rf /tmp/swoole \
    && git clone --depth 1 --recurse-submodules --shallow-submodules -b 6.1.0 https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis


# High-Performance PHP Config
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'memory_limit=512M'; \
    echo 'session.upload_progress.enabled=0'; \
    } > /usr/local/etc/php/conf.d/docker-php-performance.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
