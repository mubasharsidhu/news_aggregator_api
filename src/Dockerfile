# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Set the working directory
WORKDIR /var/www/html

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    supervisor \
    default-mysql-client \
    libzip-dev \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && a2enmod rewrite

# Install Xdebug for code coverage
RUN pecl install xdebug
    #&& docker-php-ext-enable xdebug

# Enable Xdebug in PHP configuration
RUN echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Copy custom Apache config
COPY ./config/.docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy the application files to the container
COPY ./src /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy the wait-for-it.sh script
COPY ./src/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

# Copy supervisord config
COPY ../config/.docker/supervisord.conf /etc/supervisor/supervisord.conf

# Set permissions for the supervisor logs directory
RUN mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor \
    && chmod -R 777 /var/log/supervisor

# Copy and set the entrypoint script
COPY ./config/.docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

# Set the entrypoint
ENTRYPOINT ["entrypoint.sh"]

# Expose port 80 for Apache
EXPOSE 80
