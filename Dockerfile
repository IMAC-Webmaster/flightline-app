FROM composer AS composer

# copying the source directory and install the dependencies with composer
COPY . /app
# run composer install to install the dependencies
RUN composer install \
  --optimize-autoloader \
  --no-interaction \
  --no-progress

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
FROM trafex/php-nginx
COPY --chown=nobody --from=composer /app /var/www/html
#RUN chown -R nobody:nobody /var/www/html
USER root
RUN apk add sqlite
RUN apk add php81-sqlite3
RUN sqlite3 /var/www/html/db/flightline.db < /var/www/html/include/dbCreate_v3.sql
RUN chown -R nobody:nobody /var/www/html/db/flightline.db
USER nobody
COPY nginx-server.conf /etc/nginx/conf.d/default.conf