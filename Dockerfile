FROM php:8.1-fpm-alpine
ARG SHOPIFY_API_KEY
ENV SHOPIFY_API_KEY=$SHOPIFY_API_KEY
RUN apk update && apk add --update nodejs npm \
    php-pdo_sqlite php-pdo_mysql php-pdo_pgsql php-simplexml php-fileinfo php-dom php-tokenizer php-xml php-xmlwriter php-session\
    openrc bash nginx
# RUN curl -sS https://getcomposer.org/installer | php -- --version=2.3.10 && mv composer.phar /usr/local/bin/composer
RUN docker-php-ext-install pdo
# RUN docker-php-ext-enable php-zip
COPY --chown=www-data:www-data web /app
WORKDIR /app
# Overwrite default nginx config
COPY web/nginx.conf /etc/nginx/nginx.conf
# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# RUN composer install
# RUN touch /app/storage/db.sqlite
# RUN chown www-data:www-data /app/storage/db.sqlite
# RUN cd frontend && npm install && npm run build

# RUN composer build
RUN curl -sS https://getcomposer.org/download/2.5.0/composer.phar -o composer.phar
RUN php composer.phar install --ignore-platform-req=ext-zip
RUN cp /app/.env.example /app/.env
RUN php artisan key:generate
# RUN php artisan migrate --force

RUN chmod 755 -R /app
RUN chmod -R o+w storage
RUN chmod -R o+w bootstrap
RUN chmod -R o+w public

RUN addgroup www-data nginx

RUN chmod 750 /var/lib/nginx
RUN chgrp -R www-data /var/lib/nginx


# RUN adduser subAdmin
RUN adduser -D subAdmin

RUN passwd -u subAdmin

RUN chmod 755 -R /app/frontend

RUN cd /app/frontend && npm install

RUN cd /app/frontend && SHOPIFY_API_KEY=$SHOPIFY_API_KEY npm run build

USER subAdmin
RUN cd /app

RUN php composer.phar build

USER root
RUN cd /app/storage/certs/
RUN chown www-data:www-data ca-certificate.crt
RUN cd /app
ENTRYPOINT [ "/app/entrypoint.sh" ]
