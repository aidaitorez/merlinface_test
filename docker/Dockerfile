FROM php:8.0-fpm

# Установка зависимостей и расширений PHP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install sockets zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer global require ext-sockets --prefer-dist --no-progress --no-suggest --classmap-authoritative --no-interaction

# Копирование файлов проекта в контейнер
COPY . /var/www/html

# Установка PHP веб-сервера
RUN apt-get install -y nginx
COPY ./docker/nginx/nginx.conf /etc/nginx/sites-available/default

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --ignore-platform-reqs

EXPOSE 8000
CMD service nginx start && php-fpm