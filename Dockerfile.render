FROM php:8.2-fpm-alpine

# Install system dependencies, PostgreSQL dev libraries, Ghostscript, qpdf & Nginx
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    ghostscript \
    qpdf \
    nginx

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        gd \
        bcmath \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Salin source code sistem ke dalam image
COPY . /var/www/html

# Salin konfigurasi Nginx dan script entrypoint
COPY docker/render/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/render/entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

# Pasang dependensi PHP tanpa dev dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]

