FROM php:8.4-apache

# Build-time configuration
ARG APP_ENV=prod
ARG ENABLE_XDEBUG=0
ENV APP_ENV=$APP_ENV
ENV APP_DEBUG=0

# Set the working directory inside the container
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
        git \
        cron \
        nano \
        supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_pgsql \
    zip \
    pdo \
    pdo_mysql \
    opcache

# Install APCU and conditionally install XDEBUG for dev builds
RUN pecl install apcu && docker-php-ext-enable apcu
RUN if [ "$ENABLE_XDEBUG" = "1" ]; then pecl install xdebug && docker-php-ext-enable xdebug; fi

# Install symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Install Composer CLI
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application source and configs
COPY . /var/www/html
COPY .docker/virtualhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup crontab
COPY .docker/crontab /var/www/html/.docker/crontab
RUN crontab /var/www/html/.docker/crontab

# You can add your own aliases here
RUN echo 'alias console="php /var/www/html/bin/console"' >> ~/.bashrc
RUN echo 'alias phpstan="/var/www/html/vendor/phpstan/phpstan/phpstan"' >> ~/.bashrc
RUN echo 'alias dsu="/var/www/html/bin/console d:s:u --force --complete"' >> ~/.bashrc

RUN echo "umask 0000" >> /root/.bashrc

# Install dependencies (optimize for production) and warm cache when APP_ENV=prod
RUN if [ "$APP_ENV" = "prod" ]; then \
    COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; \
  else \
    COMPOSER_MEMORY_LIMIT=-1 composer install --prefer-dist --no-interaction; \
  fi

# Start Supervisor, which will run cron -f by default.
CMD ["/usr/bin/supervisord"]
