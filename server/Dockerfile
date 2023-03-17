FROM php:8.2-fpm

WORKDIR /var/www

RUN docker-php-ext-install bcmath mbstring intl opcache pdo_mysql

RUN curl --silent --show-error https://getcomposer.org/installer | php && \
	mv composer.phar /usr/local/bin/composer && \
	chmod +x /usr/local/bin/composer

COPY --chown=www-data:www-data . .
USER www-data:www-data
RUN composer install --no-dev

EXPOSE 9000
CMD ["php-fpm"]