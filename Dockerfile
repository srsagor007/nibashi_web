# Use the official PHP image as base
FROM php:8.3-fpm

ARG user
ARG uid

# Set environment variables
# ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_ENABLE_CLI=0
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV PHP_OPCACHE_REVALIDATE_FREQ=0
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=10000
ENV PHP_OPCACHE_MAX_WASTED_PERCENTAGE=10

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libjpeg62-turbo-dev \
    locales \
    jpegoptim optipng pngquant gifsicle \
    libonig-dev \
    libgd-dev \
    curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-external-gd --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip mbstring exif pcntl opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#Installing node 18.x
RUN curl -sL https://deb.nodesource.com/setup_18.x| bash -
RUN apt-get install -y nodejs

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user


# Add user for laravel application
# RUN groupadd -g 1000 www
# RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy application files
COPY . .

# Install application dependencies
RUN composer install

# Copy existing application directory permissions
COPY --chown=$user:www-data . /var/www/html
RUN chown -R $user:www-data storage
RUN chown -R $user:www-data bootstrap/cache
RUN chmod -R 775 storage
RUN chmod -R 775 bootstrap/cache

# Change current user to www
USER $user


# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
