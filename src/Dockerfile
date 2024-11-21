FROM php:8.3.11-fpm

# Update package list and install dependencies
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libxpm-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    bash \
    fcgiwrap \
    libmcrypt-dev \
    libonig-dev \
    libpq-dev \
    supervisor \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath opcache

# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application directory contents
COPY ./src /var/www/html/

# Set ownership and permissions for the /var/www/html directory to www-data
RUN chown -R www-data:www-data /var/www/html/
# Set permissions for /var/run and /var/run/crond so that www-data can write the cron PID file

RUN mkdir -p /var/run \
    && chown -R www-data:www-data /var/run \
    && chmod 755 /var/run

RUN mkdir -p /var/run/crond \
    && chown -R www-data:www-data /var/run/crond \
    && chmod 755 /var/run/crond

# Copy the wait-for-it.sh script
COPY ./src/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

# Copy supervisord config
COPY ../config/.docker/supervisord.conf /etc/supervisor/supervisord.conf

# Set permissions for the supervisor logs directory
RUN mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor \
    && chmod -R 777 /var/log/supervisor

USER www-data

EXPOSE 9000


#CMD ["php-fpm"]
CMD service cron start && php-fpm
