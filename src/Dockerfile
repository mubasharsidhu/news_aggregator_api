FROM php:8.3.11-fpm

# Update package list and install dependencies
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    build-essential \
    libzip-dev \
    zip \
    unzip \
    git \
    bash \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip pcntl bcmath opcache

# Install Xdebug for code coverage
RUN pecl install xdebug
    #&& docker-php-ext-enable xdebug

# Enable Xdebug in PHP configuration
RUN echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application directory contents
COPY ./src /var/www/html/

# Set ownership and permissions for the /var/www/html directory to www-data
RUN chown -R www-data:www-data /var/www/html/

# Copy the wait-for-it.sh script
COPY ./src/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

# Copy supervisord config
COPY ../config/.docker/supervisord.conf /etc/supervisor/supervisord.conf

# Set permissions for the supervisor logs directory
RUN mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor \
    && chmod -R 777 /var/log/supervisor

# Copy the entrypoint script
COPY ../config/.docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000


CMD entrypoint.sh
